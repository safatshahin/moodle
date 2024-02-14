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

namespace mod_quiz\task;

use core\task\send_module_notifications_task;
use core_user;
use mod_quiz\quiz_settings;
use stdClass;

/**
 * Ad-hoc task to send upcoming quiz open notifications.
 *
 * @package    mod_quiz
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_quiz_open_notification extends send_module_notifications_task {

    public function execute() {
        global $DB;
        // Initialize the custom data.
        $data = $this->get_custom_data();

        $quizobj = quiz_settings::create($data->quizid);
        $quiz = $quizobj->get_quiz();

        $userids = $data->userids ?? $this->get_users($quiz->course);
        $usercount = count($userids);

        // The user count is more than 1000, we will send the notification in chunks.
        if ($usercount > self::USER_CHUNK) {
            $chunks = array_chunk($userids, self::USER_CHUNK);

            $users = reset($chunks);
            $this->send_notifications($quiz, $users);

            $chunks = array_shift($chunks);
            foreach ($chunks as $chunk) {
                self::queue_for_users($quiz, $chunk);
            }
        } else {
            $this->send_notifications($quiz, $userids);
        }
        $DB->set_field('quiz', 'timeopennotificationsent', time(), ['id' => $quiz->id]);
    }

    public function send_notifications(
        stdClass $module,
        array $userids,
    ): void {
        foreach ($userids as $userid) {
            $userto = core_user::get_user($userid);
            $url = new \moodle_url('/mod/quiz/view.php', ['q' => $module->id]);

            $eventdata = new \core\message\message();
            $eventdata->component = 'mod_quiz';
            $eventdata->name = 'upcoming_quiz_open';
            $eventdata->userfrom = core_user::get_noreply_user();
            $eventdata->userto = $userto;
            $eventdata->subject = "Sample subject";
            $eventdata->fullmessage = "Sample full msg";
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = '';
            $eventdata->notification = 1;
            $eventdata->contexturl = $url;
            $eventdata->contexturlname = format_text($module->name);

            // Send the message.
            message_send($eventdata);
        }
    }

    public static function queue(
        stdClass $quiz,
    ): void {
        $task = new self();
        $task->set_custom_data([
            'quizid' => $quiz->id,
        ]);

        // $nextruntime = $quiz->timeopen - self::THRESHOLD;
        // // If the next run time is not the past, set it to the specific time.
        // // The threshold is 48 hours before the quiz open time.
        // if (time() < $nextruntime) {
        //     $task->set_next_run_time($nextruntime);
        // }

        // Queue the task.
        \core\task\manager::queue_adhoc_task($task);
    }

    public static function queue_for_users(
        stdClass $quiz,
        array $userids,
    ): void {
        $task = new self();
        $task->set_custom_data([
            'quizid' => $quiz->id,
            'userids' => $userids,
        ]);

        // Queue the task.
        \core\task\manager::queue_adhoc_task($task);
    }
}
