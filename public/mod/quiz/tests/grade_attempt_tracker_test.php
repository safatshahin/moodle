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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Unit tests for best attempt tracking.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2026 Jay Oswald <jayoswald@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_quiz\grade_attempt_tracker
 */
final class grade_attempt_tracker_test extends \advanced_testcase {
    /**
     * Test that calculate_quiz correctly updates the best attempt columns for a quiz.
     *
     * @param array $attemptrows
     * @param int $expectedfirstattempt
     * @param int $expectedlastattempt
     * @param int|null $expectedhighestattempt
     * @dataProvider calculate_quiz_provider
     */
    public function test_calculate_quiz_updates_best_attempt_columns(
        array $attemptrows,
        int $expectedfirstattempt,
        int $expectedlastattempt,
        ?int $expectedhighestattempt,
    ): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $uniqueid = 1000;
        foreach ($attemptrows as $attemptrow) {
            $attempt = (object) [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attempt' => $attemptrow['attempt'],
                'state' => $attemptrow['state'],
                'sumgrades' => $attemptrow['sumgrades'],
                'uniqueid' => $uniqueid++,
                'layout' => '',
            ];
            $DB->insert_record('quiz_attempts', $attempt);
        }

        grade_attempt_tracker::calculate_quiz($quiz->id);

        $first = $DB->get_record('quiz_attempts', [
            'quiz' => $quiz->id,
            'userid' => $user->id,
            'attemptfirst' => 1,
        ], '*', MUST_EXIST);
        $this->assertEquals($expectedfirstattempt, (int)$first->attempt);

        $last = $DB->get_record('quiz_attempts', [
            'quiz' => $quiz->id,
            'userid' => $user->id,
            'attemptlast' => 1,
        ], '*', MUST_EXIST);
        $this->assertEquals($expectedlastattempt, (int)$last->attempt);

        $highest = $DB->get_record('quiz_attempts', [
            'quiz' => $quiz->id,
            'userid' => $user->id,
            'gradehighest' => 1,
        ]);

        if ($expectedhighestattempt === null) {
            $this->assertFalse($highest);
        } else {
            $this->assertNotFalse($highest);
            $this->assertEquals($expectedhighestattempt, (int)$highest->attempt);
        }
    }

    /**
     * Data provider for test_calculate_quiz_updates_best_attempt_columns.
     *
     * @return array[]
     */
    public static function calculate_quiz_provider(): array {
        return [
            'submitted then finished attempts' => [
                [
                    ['attempt' => 1, 'state' => quiz_attempt::SUBMITTED, 'sumgrades' => null],
                    ['attempt' => 2, 'state' => quiz_attempt::FINISHED, 'sumgrades' => 30],
                    ['attempt' => 3, 'state' => quiz_attempt::FINISHED, 'sumgrades' => 45],
                ],
                1,
                3,
                3,
            ],
            'highest tie prefers earlier attempt' => [
                [
                    ['attempt' => 1, 'state' => quiz_attempt::FINISHED, 'sumgrades' => 50],
                    ['attempt' => 2, 'state' => quiz_attempt::FINISHED, 'sumgrades' => 50],
                    ['attempt' => 3, 'state' => quiz_attempt::SUBMITTED, 'sumgrades' => null],
                ],
                1,
                3,
                1,
            ],
            'no finished attempts means no highest' => [
                [
                    ['attempt' => 1, 'state' => quiz_attempt::SUBMITTED, 'sumgrades' => null],
                    ['attempt' => 2, 'state' => quiz_attempt::SUBMITTED, 'sumgrades' => null],
                ],
                1,
                2,
                null,
            ],
        ];
    }

    /**
     * Create a quiz worth 100 marks and insert finished attempts for a user, without going through the API.
     *
     * @param array $sumgrades sumgrades for each attempt, in attempt order.
     * @return array [quiz record, user record, attempt records keyed by attempt number]
     */
    private function create_quiz_with_finished_attempts(array $sumgrades): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'grade' => 100]);
        $DB->set_field('quiz', 'sumgrades', 100, ['id' => $quiz->id]);
        $quiz->sumgrades = 100;
        $user = $this->getDataGenerator()->create_user();

        $attempts = [];
        $uniqueid = 3000;
        foreach ($sumgrades as $i => $sumgrade) {
            $attempt = (object) [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attempt' => $i + 1,
                'state' => quiz_attempt::FINISHED,
                'sumgrades' => $sumgrade,
                'uniqueid' => $uniqueid++,
                'layout' => '',
                'preview' => 0,
            ];
            $attempt->id = $DB->insert_record('quiz_attempts', $attempt);
            $attempts[$i + 1] = $attempt;
        }

        return [$quiz, $user, $attempts];
    }

    /**
     * Test that preview attempts are ignored and never flagged as first, last or highest.
     */
    public function test_preview_attempts_are_ignored(): void {
        global $DB;

        $this->resetAfterTest();
        [$quiz, $user, $attempts] = $this->create_quiz_with_finished_attempts([40]);
        $preview = (object) [
            'quiz' => $quiz->id,
            'userid' => $user->id,
            'attempt' => 2,
            'state' => quiz_attempt::FINISHED,
            'sumgrades' => 90,
            'uniqueid' => 4000,
            'layout' => '',
            'preview' => 1,
        ];
        $preview->id = $DB->insert_record('quiz_attempts', $preview);

        grade_attempt_tracker::calculate_quiz($quiz->id);

        $real = $DB->get_record('quiz_attempts', ['id' => $attempts[1]->id], '*', MUST_EXIST);
        $this->assertEquals(1, $real->gradehighest);
        $this->assertEquals(1, $real->attemptfirst);
        $this->assertEquals(1, $real->attemptlast);
        $previewrecord = $DB->get_record('quiz_attempts', ['id' => $preview->id], '*', MUST_EXIST);
        $this->assertEquals(0, $previewrecord->gradehighest);
        $this->assertEquals(0, $previewrecord->attemptfirst);
        $this->assertEquals(0, $previewrecord->attemptlast);
    }

    /**
     * Test that recompute_final_grade brings the best-attempt flags up to date before computing the grade.
     */
    public function test_recompute_final_grade_updates_flags(): void {
        global $DB;

        $this->resetAfterTest();
        [$quiz, $user, $attempts] = $this->create_quiz_with_finished_attempts([40, 70, 55]);

        // Flags are all stale (0) because the attempts were inserted directly.
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_final_grade($user->id);

        $this->assertEquals(1, $DB->get_field('quiz_attempts', 'gradehighest', ['id' => $attempts[2]->id]));
        $this->assertEquals(1, $DB->get_field('quiz_attempts', 'attemptfirst', ['id' => $attempts[1]->id]));
        $this->assertEquals(1, $DB->get_field('quiz_attempts', 'attemptlast', ['id' => $attempts[3]->id]));
        $this->assertEquals(70, $DB->get_field('quiz_grades', 'grade', ['quiz' => $quiz->id, 'userid' => $user->id]));
    }

    /**
     * Test that deleting the attempt that provides the grade moves the flags and keeps the user's grade.
     */
    public function test_delete_best_attempt_recalculates_flags_and_grade(): void {
        global $DB;

        $this->resetAfterTest();
        [$quiz, $user, $attempts] = $this->create_quiz_with_finished_attempts([50, 80]);

        grade_attempt_tracker::calculate_quiz($quiz->id);
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_final_grade($user->id);
        $this->assertEquals(1, $DB->get_field('quiz_attempts', 'gradehighest', ['id' => $attempts[2]->id]));
        $this->assertEquals(80, $DB->get_field('quiz_grades', 'grade', ['quiz' => $quiz->id, 'userid' => $user->id]));

        quiz_delete_attempt($attempts[2]->id, $quiz);

        $remaining = $DB->get_record('quiz_attempts', ['id' => $attempts[1]->id], '*', MUST_EXIST);
        $this->assertEquals(1, $remaining->gradehighest);
        $this->assertEquals(1, $remaining->attemptfirst);
        $this->assertEquals(1, $remaining->attemptlast);
        $this->assertEquals(50, $DB->get_field('quiz_grades', 'grade', ['quiz' => $quiz->id, 'userid' => $user->id]));
    }

    /**
     * Test that quiz_has_pending_calculation correctly identifies whether a quiz has a pending calculation task.
     *
     * @param bool $queuefortargetquiz
     * @param bool $queueforotherquiz
     * @param bool $expected
     * @dataProvider quiz_has_pending_calculation_provider
     */
    public function test_quiz_has_pending_calculation(
        bool $queuefortargetquiz,
        bool $queueforotherquiz,
        bool $expected,
    ): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $otherquiz = $quizgenerator->create_instance(['course' => $course->id]);

        if ($queuefortargetquiz) {
            grade_attempt_tracker::queue_quiz_calculation($quiz->id);
        }
        if ($queueforotherquiz) {
            grade_attempt_tracker::queue_quiz_calculation($otherquiz->id);
        }

        $this->assertSame($expected, grade_attempt_tracker::quiz_has_pending_calculation($quiz->id));
    }

    /**
     * Data provider for test_quiz_has_pending_calculation.
     *
     * @return array[]
     */
    public static function quiz_has_pending_calculation_provider(): array {
        return [
            'no queued task' => [
                false,
                false,
                false,
            ],
            'queued task for target quiz' => [
                true,
                false,
                true,
            ],
            'queued task for different quiz only' => [
                false,
                true,
                false,
            ],
            'queued tasks for both quizzes' => [
                true,
                true,
                true,
            ],
        ];
    }

    /**
     * Test that shutdown check throws when quizzes are pending.
     *
     * @param bool $registershutdown
     * @param bool $expectexception
     * @dataProvider shutdown_check_provider
     */
    public function test_shutdown_check_handles_pending_quiz(bool $registershutdown, bool $expectexception): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $attempts = [
            ['attempt' => 1, 'state' => quiz_attempt::SUBMITTED, 'sumgrades' => null],
            ['attempt' => 2, 'state' => quiz_attempt::FINISHED, 'sumgrades' => 80],
        ];

        $uniqueid = 2000;
        foreach ($attempts as $attemptrow) {
            $attempt = (object) [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attempt' => $attemptrow['attempt'],
                'state' => $attemptrow['state'],
                'sumgrades' => $attemptrow['sumgrades'],
                'uniqueid' => $uniqueid++,
                'layout' => '',
            ];
            $DB->insert_record('quiz_attempts', $attempt);
        }

        if ($registershutdown) {
            grade_attempt_tracker::ensure_quiz_calculated_before_shutdown($quiz->id);
        }

        if ($expectexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage((string) $quiz->id);
        }

        try {
            grade_attempt_tracker::check_shutdown_recalculation();
        } finally {
            // The shutdown check does not recalculate. Ensure no best-attempt flags were changed.
            $this->assertEquals(0, $DB->count_records('quiz_attempts', [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attemptfirst' => 1,
            ]));
            $this->assertEquals(0, $DB->count_records('quiz_attempts', [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attemptlast' => 1,
            ]));
            $this->assertEquals(0, $DB->count_records('quiz_attempts', [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'gradehighest' => 1,
            ]));
        }
    }

    /**
     * Data provider for test_shutdown_check_handles_pending_quiz.
     *
     * @return array[]
     */
    public static function shutdown_check_provider(): array {
        return [
            'no pending quiz registered' => [
                false,
                false,
            ],
            'pending quiz registered' => [
                true,
                true,
            ],
        ];
    }
}
