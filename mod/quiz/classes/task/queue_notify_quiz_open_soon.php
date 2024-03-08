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

use core\task\scheduled_task;

/**
 * Scheduled task to queue tasks for notifying about quizzes with an approaching open date.
 *
 * @package    mod_quiz
 * @copyright  2024 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_notify_quiz_open_soon extends scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('sendnotificationopendatesoon', 'mod_quiz');
    }

    /**
     * Run the scheduled task to create the ad-hoc task for each quiz.
     */
    public function execute(): void {
        $quizzes = quiz_notification_helper::get_quizzes_within_date_threshold();
        foreach ($quizzes as $quiz) {
            $task = new notify_quiz_open_soon();
            $task->set_custom_data($quiz);
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
