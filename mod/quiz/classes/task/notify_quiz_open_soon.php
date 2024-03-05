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

use core\task\adhoc_task;
use mod_quiz\task\quiz_notification_helper;

/**
 * Ad-hoc task to notify users of quizzes with an approaching open date.
 *
 * @package    mod_quiz
 * @copyright  2024 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_quiz_open_soon extends adhoc_task {
    /**
     * Run the ad-hoc task to send a notification to a user about an approaching open date.
     */
    public function execute(): void {
        $quiz = $this->get_custom_data();
        $users = quiz_notification_helper::get_users_within_quiz($quiz);
        foreach ($users as $user) {
            quiz_notification_helper::send_quiz_open_soon_notification($user, $quiz);
        }
    }
}
