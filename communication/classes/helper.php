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

namespace core_communication;

use context;
use stdClass;

/**
 * Helper method for communication.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var string GROUP_COMMUNICATION_INSTANCETYPE The group communication instance type. */
    public const GROUP_COMMUNICATION_INSTANCETYPE = 'groupcommunication';

    /** @var string GROUP_COMMUNICATION_COMPONENT The group communication component. */
    public const GROUP_COMMUNICATION_COMPONENT = 'core_group';

    /**
     * Load the communication instance for group id.
     *
     * @param int $groupid The group id
     * @param context $context The context, to make sure any instance using group can load the communication instance
     * @return api The communication instance.
     */
    public static function load_by_group(int $groupid, context $context): api {
        return \core_communication\api::load_by_instance(
            context: $context,
            component: self::GROUP_COMMUNICATION_COMPONENT,
            instancetype: self::GROUP_COMMUNICATION_INSTANCETYPE,
            instanceid: $groupid,
        );
    }

    /**
     * Communication api call to create room for a group if course has group mode enabled.
     *
     * @param int $courseid The course id.
     * @return stdClass
     */
    public static function get_course(int $courseid): stdClass {
        global $DB;
        return $DB->get_record(
            table: 'course',
            conditions: ['id' => $courseid],
            fields: '*',
            strictness: MUST_EXIST,
        );
    }

    /**
     * Is group mode enabled for the course.
     *
     * @param stdClass $course The course object
     */
    public static function is_group_mode_enabled_for_course(stdClass $course): bool {
        $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;
        return (int)$groupmode !== NOGROUPS;
    }
}
