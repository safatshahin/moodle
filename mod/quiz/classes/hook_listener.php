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

use core_enrol\hook\after_user_enrolled;
use mod_quiz\task\send_quiz_open_notification;

/**
 * Hook listener for quiz.
 *
 * @package    mod_quiz
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Send the quiz open notification after the user is enrolled in the course.
     *
     * @param after_user_enrolled $hook The hook instance.
     */
    public static function send_quiz_open_notification(
        after_user_enrolled $hook,
    ): void {
        // First get the quiz module for the course.
        $quizmodules = get_coursemodules_in_course('quiz', $hook->get_instance()->courseid);
        // Now go through each quiz and check which notifications are send and trigger notification for the user for them.
        foreach ($quizmodules as $quizmodule) {
            // check if the notification is sent before the user was enrolled.
            if ($quizmodule->timeopennotificationsent !== 0 && $quizmodule->timeopennotificationsent < $hook->get_user_enrolment_instance()->timecreated) {
                // Send the notification.
                send_quiz_open_notification::queue_for_users($quizmodule, [$hook->get_userid()]);
            }
        }
    }
}
