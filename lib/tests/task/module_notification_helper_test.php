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

namespace core\task;

use core\task\module_notification_helper;

/**
 * Test class for module_notification_helper.
 *
 * @package    core
 * @category   test
 * @copyright  2024 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core\task\module_notification_helper
 */
class module_notification_helper_test extends \advanced_testcase {
    /**
     * Test a date is within the threshold. The default threshold is 48 hours.
     *
     * @covers ::is_date_within_threshold
     * @covers ::get_date_threshold
     */
    public function test_is_date_within_threshold(): void {
        $this->resetAfterTest();

        // Check our default date threshold of 48 hours from now.
        $expectedthreshold = time() + (DAYSECS * 2);
        $this->assertEquals($expectedthreshold, module_notification_helper::get_date_threshold());

        // One day from now should fall within the threshold of 48 hours from now.
        $date = time() + DAYSECS;
        $result = module_notification_helper::is_date_within_threshold($date);
        $this->assertTrue($result);
    }

    /**
     * Test a user has a notification record using matching customdata.
     *
     * @covers ::has_user_been_sent_a_notification_already
     */
    public function test_has_user_been_sent_a_notification_already(): void {
        $this->resetAfterTest();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // Prepare some data that will be used in the message and the matching of the notification.
        $customdata = [
            'quizid' => 1,
            'timeopen' => time(),
            'overridetype' => 'none',
        ];

        // Send a message to the user.
        $message = new \core\message\message();
        $message->component = 'mod_quiz';
        $message->name = 'quiz_open_soon';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = \core_user::get_user($user->id);
        $message->subject = 'testsubject';
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessage = 'testmessage';
        $message->notification = 1;
        $message->customdata = $customdata;

        message_send($message);

        // Check that a match is found using the customdata.
        $result = module_notification_helper::has_user_been_sent_a_notification_already($user->id, json_encode($customdata));
        $this->assertTrue($result);
    }

    /**
     * Test we can update a user record with the override dates.
     *
     * @covers ::update_user_with_date_overrides
     */
    public function test_update_user_with_date_overrides(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        /** @var \mod_quiz_generator $quizgenerator */
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');

        // Create a quiz within and enrol the user.
        $course = $generator->create_course();
        $user1 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id, 'student');
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);

        // Create a 'timeopen' override for the user.
        $quizgenerator->create_override([
            'quiz' => $quiz->id,
            'userid' => $user1->id,
            'timeopen' => time() + DAYSECS,
        ]);

        // Check the override type has been applied to the user.
        $overrides = $DB->get_records('quiz_overrides', ['quiz' => $quiz->id]);
        $modulecontext = \context_module::instance($quiz->cmid);
        $users = get_enrolled_users($modulecontext, 'mod/quiz:attempt');
        module_notification_helper::update_user_with_date_overrides($overrides, $users[$user1->id], 'timeopen');
        $this->assertEquals('user', $users[$user1->id]->overridetype);

        // Enrol two more users.
        $user2 = $generator->create_user();
        $generator->enrol_user($user2->id, $course->id, 'student');
        $user3 = $generator->create_user();
        $generator->enrol_user($user3->id, $course->id, 'student');

        // Assign the new useres to a group with a 'timeopen' override.
        $grouptimeopen = time() + (HOURSECS * 2);
        $group = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $user2->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $user3->id]);
        $quizgenerator->create_override([
            'quiz' => $quiz->id,
            'groupid' => $group->id,
            'timeopen' => $grouptimeopen,
        ]);

        // Check the override type has been applied to the new users.
        $overrides = $DB->get_records('quiz_overrides', ['quiz' => $quiz->id]);
        $modulecontext = \context_module::instance($quiz->cmid);
        $users = get_enrolled_users($modulecontext, 'mod/quiz:attempt');
        module_notification_helper::update_user_with_date_overrides($overrides, $users[$user2->id], 'timeopen');
        module_notification_helper::update_user_with_date_overrides($overrides, $users[$user3->id], 'timeopen');
        $this->assertEquals('group', $users[$user2->id]->overridetype);
        $this->assertEquals('group', $users[$user3->id]->overridetype);
    }
}
