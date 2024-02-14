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
 * Scheduled task to queue the ad-hoc task to send upcoming quiz open notifications.
 *
 * @package    mod_quiz
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_notify_upcoming_quiz_open extends scheduled_task {

    public function get_name(): string {
        return get_string('notify_upcoming_quiz_open', 'mod_quiz');
    }

    public function execute(): void {
        global $DB;
        $timenow = time();
        // $sql = <<<EOF
        //             SELECT *
        //               FROM {quiz}
        //              WHERE timecreated > :lastruntime
        //                AND timecreated < :timenow
        //                AND timeopen > :timenow
        //                AND timeclose < :timenow
        //                AND timeopennotificationsent = 0
        //              UNION
        //             SELECT *
        //               FROM {quiz}
        //              WHERE timemodified > :lastruntime
        //                AND timemodified < :timenow
        //                AND timecreated < :lastruntime
        //                AND timeopen > :timenow
        //                AND timeclose < :timenow
        //                AND timeopennotificationsent = 0
        // EOF;
        $sql = <<<EOF
                    SELECT *
                      FROM {quiz}
                     WHERE timeopen != 0
                       AND timeopen > :timenow
                       AND timeopennotificationsent = 0
        EOF;

        $quizmodules = $DB->get_records_sql(
            sql: $sql,
            params:
            [
                'timenow' => $timenow,
            ],
        );
        foreach ($quizmodules as $quizmodule) {
            // Check if the timeopen of the quiz is less than 48 hours later from now.
            if ($quizmodule->timeopen - time() < 172800) {
                send_quiz_open_notification::queue($quizmodule);
            }
        }
    }
}
