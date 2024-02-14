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

use stdClass;

/**
 * This task is used to send notifications for a module.
 *
 * Any modules need to send standard notifications can use this class instead of extending the scheduled_task class.
 * This class includes all the common methods and properties that are required to send notifications from a module in a course.
 *
 * @package    core
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class send_module_notifications_task extends adhoc_task {

    /**
     * @var int The threshold for the next run time.
     */
    public const int THRESHOLD = 172800;

    /**
     * @var int The user chunk size.
     */
    public const int USER_CHUNK = 1000;

    /**
     * Get the enrolled users for course.
     *
     * @param int $courseid The course id.
     * @return array
     */
    public function get_users(int $courseid): array {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');
        return array_column(
            enrol_get_course_users(courseid: $courseid, onlyactive: true),
            'id',
        );
    }

    /**
     * Send the notifications for the module.
     *
     * @param stdClass $module The module object.
     * @param array $userids The user ids.
     */
    abstract public function send_notifications(stdClass $module, array $userids): void;
}
