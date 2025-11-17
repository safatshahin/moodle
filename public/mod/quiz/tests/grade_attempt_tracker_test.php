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
