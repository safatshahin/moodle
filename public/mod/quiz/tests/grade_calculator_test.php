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
 * Unit tests for the grade calculator.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2026 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_quiz\grade_calculator
 */
final class grade_calculator_test extends \advanced_testcase {
    /**
     * Create a quiz worth 100 marks with two finished attempts (40 and 70) for a user.
     *
     * @param string $grademethod one of the QUIZ_* grade method constants.
     * @return array [quiz record, user record]
     */
    private function create_quiz_with_attempts(string $grademethod): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'grade' => 100,
            'grademethod' => $grademethod,
        ]);
        $DB->set_field('quiz', 'sumgrades', 100, ['id' => $quiz->id]);
        $user = $this->getDataGenerator()->create_user();

        $uniqueid = 5000;
        foreach ([40, 70] as $i => $sumgrades) {
            $DB->insert_record('quiz_attempts', (object) [
                'quiz' => $quiz->id,
                'userid' => $user->id,
                'attempt' => $i + 1,
                'state' => quiz_attempt::FINISHED,
                'sumgrades' => $sumgrades,
                'uniqueid' => $uniqueid++,
                'layout' => '',
                'preview' => 0,
            ]);
        }

        return [$quiz, $user];
    }

    /**
     * Test that final grades are computed correctly whether grademethod is a string or an int.
     *
     * @param string $grademethod
     * @param float $expectedgrade
     * @dataProvider grademethod_provider
     */
    public function test_recompute_final_grade_with_int_grademethod(string $grademethod, float $expectedgrade): void {
        global $DB;

        $this->resetAfterTest();
        [$quiz, $user] = $this->create_quiz_with_attempts($grademethod);

        $quizobj = quiz_settings::create($quiz->id);
        // Simulate the quiz record having an int grademethod, which happens on some code paths (see MDL-88180).
        $quizobj->get_quiz()->grademethod = (int) $grademethod;

        $quizobj->get_grade_calculator()->recompute_final_grade($user->id);
        $this->assertEquals($expectedgrade, $DB->get_field('quiz_grades', 'grade', ['quiz' => $quiz->id, 'userid' => $user->id]));

        $DB->delete_records('quiz_grades', ['quiz' => $quiz->id]);
        $quizobj->get_grade_calculator()->recompute_all_final_grades();
        $this->assertEquals($expectedgrade, $DB->get_field('quiz_grades', 'grade', ['quiz' => $quiz->id, 'userid' => $user->id]));
    }

    /**
     * Data provider for test_recompute_final_grade_with_int_grademethod.
     *
     * @return array[]
     */
    public static function grademethod_provider(): array {
        return [
            'highest' => [QUIZ_GRADEHIGHEST, 70],
            'average' => [QUIZ_GRADEAVERAGE, 55],
            'first' => [QUIZ_ATTEMPTFIRST, 40],
            'last' => [QUIZ_ATTEMPTLAST, 70],
        ];
    }
}
