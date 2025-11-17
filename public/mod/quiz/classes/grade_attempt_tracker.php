<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_quiz;

use mod_quiz\task\quiz_calculate_best_attempts_for_quiz;
use core\task\manager;
use core\output\progress_trace;
use core\output\progress_trace\null_progress_trace;

/**
 * Controls all logic to calculate, store and retrieve best attempts for quizzes.
 *
 * @package    mod_quiz
 * @copyright  2026 Jay Oswald <jayoswald@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_attempt_tracker {
    /**
     * The states that indicate attempts that should be considered for best attempt calculations.
     */
    public const STATES = [
        quiz_attempt::FINISHED,
        quiz_attempt::SUBMITTED,
    ];

    /** @var bool[] quizid => true for quizzes pending fallback recalculation at shutdown. */
    private static array $shutdownpendingquizids = [];

    /** @var bool Whether the shutdown handler has been registered. */
    private static bool $shutdownregistered = false;

    /**
     * Output a progress message to the given trace, or discard it if no trace was given.
     *
     * @param string $message
     * @param ?progress_trace $trace The trace to output to, or null to use a null trace.
     * @return void
     */
    private static function trace(string $message, ?progress_trace $trace = null): void {
        if ($trace === null) {
            $trace = new null_progress_trace();
        }
        $trace->output($message);
    }

    /**
     * Queue an adhoc task to calculate best attempts for a given quiz.
     *
     * @param int $quizid
     * @return void
     */
    public static function queue_quiz_calculation($quizid): void {
        $task = new quiz_calculate_best_attempts_for_quiz();
        $task->set_custom_data([
            'id' => $quizid,
        ]);
        manager::queue_adhoc_task($task);
    }

    /**
     * Calculate best attempts for all users for a given quiz.
     *
     * @param int $quizid
     * @param ?progress_trace $trace The trace to output to, or null to use a null trace.
     * @return void
     */
    public static function calculate_quiz($quizid, ?progress_trace $trace = null): void {
        global $DB;

        [$statesql, $stateparams] = $DB->get_in_or_equal(self::STATES, SQL_PARAMS_NAMED, 'state');

        $sql = "SELECT DISTINCT qa.userid
                  FROM {quiz_attempts} qa
                 WHERE qa.state $statesql
                   AND qa.preview = 0
                   AND qa.quiz = :quiz";

        $params = array_merge($stateparams, ['quiz' => $quizid]);

        $users = $DB->get_fieldset_sql($sql, $params);

        $totalusers = count($users);
        self::trace("Calculating grades for quiz $quizid for $totalusers users", $trace);

        $count = 0;
        foreach ($users as $user) {
            if (++$count % 1000 === 0) {
                self::trace('calculating for user ' . $count . '/' . $totalusers, $trace);
            }
            self::calculate_quiz_user_attempts($quizid, $user);
        }

        self::trace("Finished calculating grades for quiz $quizid for $totalusers users", $trace);

        unset(self::$shutdownpendingquizids[$quizid]);
    }

    /**
     * Register a fallback to recalculate best attempts at shutdown if not already recalculated.
     *
     * @param int $quizid
     * @return void
     */
    public static function ensure_quiz_calculated_before_shutdown($quizid): void {
        if (!self::$shutdownregistered) {
            \core\shutdown_manager::register_function([self::class, 'check_shutdown_recalculation']);
            self::$shutdownregistered = true;
        }

        self::$shutdownpendingquizids[$quizid] = true;
    }

    /**
     * Error if any quizzes have not been recalculated at shutdown.
     *
     * @return void
     */
    public static function check_shutdown_recalculation(): void {
        if (count(self::$shutdownpendingquizids) > 0) {
            $ids = implode(',', array_keys(self::$shutdownpendingquizids));
            throw new \coding_exception('Some quizzes have not been recalculated before shutdown. Quiz IDs: ' . $ids);
        }
    }

    /**
     * Calculate best attempts for a given user for a given quiz.
     *
     * @param int $quizid
     * @param int $userid
     */
    public static function calculate_quiz_user_attempts($quizid, $userid): void {
        global $DB;

        [$statesql, $stateparams] = $DB->get_in_or_equal(self::STATES, SQL_PARAMS_NAMED, 'state');
        $params = array_merge($stateparams, [
            'quiz' => $quizid,
            'userid' => $userid,
        ]);
        $attempts = $DB->get_records_select(
            'quiz_attempts',
            "quiz = :quiz AND userid = :userid AND preview = 0 AND state $statesql",
            $params,
            'attempt ASC'
        );

        if (!$attempts) {
            return;
        }

        $firstid = reset($attempts)->id;
        $lastid = end($attempts)->id;

        $highestid = null;
        $maxgrade = null;
        foreach ($attempts as $attempt) {
            if ($attempt->state != quiz_attempt::FINISHED) {
                continue;
            }
            if (
                $highestid === null ||
                $attempt->sumgrades > $maxgrade ||
                ($attempt->sumgrades == $maxgrade && $attempt->attempt < $attempts[$highestid]->attempt)
            ) {
                $highestid = $attempt->id;
                $maxgrade = $attempt->sumgrades;
            }
        }
        $updatesql = 'UPDATE {quiz_attempts}
                         SET gradehighest = CASE WHEN id = :highestid THEN 1 ELSE 0 END,
                             attemptfirst = CASE WHEN id = :firstid   THEN 1 ELSE 0 END,
                             attemptlast  = CASE WHEN id = :lastid    THEN 1 ELSE 0 END
                       WHERE quiz   = :quizid
                         AND userid = :userid';

        $DB->execute($updatesql, [
            'highestid' => $highestid,
            'firstid' => $firstid,
            'lastid' => $lastid,
            'quizid' => $quizid,
            'userid' => $userid,
        ]);
    }

    /**
     * Get all quizzes that have attempts in a state that requires best attempt calculations.
     *
     * @param ?progress_trace $trace The trace to output to, or null to use a null trace.
     * @return int[] The quiz IDs that need to be calculated.
     */
    private static function get_all_to_calculate(?progress_trace $trace = null): array {
        global $DB;
        [$statesql, $stateparams] = $DB->get_in_or_equal(self::STATES, SQL_PARAMS_NAMED, 'state');

        $sql = "SELECT DISTINCT qa.quiz
                  FROM {quiz_attempts} qa
                 WHERE qa.state $statesql
                   AND qa.preview = 0";

        $quizids = $DB->get_fieldset_sql($sql, $stateparams);

        self::trace('Total Quizs that need calculating: ' . count($quizids), $trace);

        return $quizids;
    }

    /**
     * Queue tasks to calculate best attempts.
     *
     * @param ?progress_trace $trace The trace to output to, or null to use a null trace.
     * @return void
     */
    public static function calculate_all_queued(?progress_trace $trace = null): void {
        $quizids = self::get_all_to_calculate($trace);
        $count = 0;
        foreach ($quizids as $quizid) {
            if (++$count % 1000 === 0) {
                self::trace('Processing quiz ' . $count . '/' . count($quizids), $trace);
            }
            self::queue_quiz_calculation($quizid);
        }
        self::trace('Finished processing all ' . count($quizids) . ' quizzes', $trace);
    }

    /**
     * Calculate all best attempts inline.
     *
     * @param ?progress_trace $trace The trace to output to, or null to use a null trace.
     * @return void
     */
    public static function calculate_all_now(?progress_trace $trace = null): void {
        $quizids = self::get_all_to_calculate($trace);
        $count = 0;
        foreach ($quizids as $quizid) {
            if (++$count % 1000 === 0) {
                self::trace('Processing quiz ' . $count . '/' . count($quizids), $trace);
            }
            self::calculate_quiz($quizid);
        }
        self::trace('Finished processing all ' . count($quizids) . ' quizzes', $trace);
    }

    /**
     * Get the column name for the best attempt based on grading method.
     *
     * @param string $grademethod
     * @return string
     */
    public static function get_best_attempt_column($grademethod): string {
        // Not ideal, sometimes this is string, sometimes its an int, see MDL-88180.
        if (is_numeric($grademethod)) {
            $grademethod = (string)$grademethod;
        }

        return match ($grademethod) {
            QUIZ_ATTEMPTFIRST => 'attemptfirst',
            QUIZ_ATTEMPTLAST => 'attemptlast',
            QUIZ_GRADEHIGHEST => 'gradehighest',
            default => throw new \coding_exception('Invalid grademethod ' . $grademethod),
        };
    }

    /**
     * Get the best attempt for a given user for a given quiz.
     *
     * @param object|int $quiz The quiz object or quiz id.
     * @param int $userid The user id.
     * @return object|null The best attempt record, or null if none found.
     * @throws \moodle_exception if the quiz object is missing grademethod.
     */
    public static function get_user_best_attempt($quiz, $userid) {
        global $DB;

        // If $quiz is an ID, fetch the quiz object.
        if (is_numeric($quiz)) {
            $quiz = $DB->get_record('quiz', ['id' => $quiz], '*', MUST_EXIST);
        }

        // Get grademethod from quiz object.
        if (!isset($quiz->grademethod)) {
            throw new \moodle_exception('Quiz object missing grademethod');
        }
        $bestattemptcol = self::get_best_attempt_column($quiz->grademethod);

        $conditions = [
            'quiz' => $quiz->id,
            'userid' => $userid,
            $bestattemptcol => 1,
        ];
        return $DB->get_record('quiz_attempts', $conditions);
    }

    /**
     * Check if a quiz has pending best attempt calculations.
     *
     * @param int $quizid
     * @return bool
     */
    public static function quiz_has_pending_calculation($quizid): bool {
        $task = new quiz_calculate_best_attempts_for_quiz();
        $task->set_custom_data(['id' => $quizid]);
        return (bool) manager::get_queued_adhoc_task_record($task);
    }
}
