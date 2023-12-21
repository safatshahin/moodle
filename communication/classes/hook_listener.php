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

use context_course;
use core_course\communication\communication_helper as course_communication_helper;
use core_group\hook\group_created_post;
use core_group\hook\group_deleted_post;
use core_group\hook\group_updated;
use stdClass;

/**
 * Hook listener for communication.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Communication api call to create room for a group if course has group mode enabled.
     *
     * @param group_created_post $hook The group created hook.
     */
    public static function create_group_communication(
        group_created_post $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $group = $hook->get_instance();
        $course = \core_communication\helper::get_course(
            courseid: $group->courseid,
        );

        // Check if group mode enabled before handling the communication.
        if (!helper::is_group_mode_enabled_for_course(course: $course)) {
            return;
        }

        $coursecontext = \context_course::instance(courseid: $course->id);
        // Get the course communication instance to set the provider.
        $coursecommunication = course_communication_helper::load_by_course(
            courseid: $course->id,
            context: $coursecontext,
        );

        $communication = api::load_by_instance(
            context: $coursecontext,
            component: helper::GROUP_COMMUNICATION_COMPONENT,
            instancetype: helper::GROUP_COMMUNICATION_INSTANCETYPE,
            instanceid: $group->id,
            provider: $coursecommunication->get_provider(),
        );

        $communication->create_and_configure_room(
            communicationroomname: $group->name,
            instance: $course,
        );

        // As it's a new group, we need to add the users with all access group role to the room.
        $enrolledusers = course_communication_helper::get_enrolled_users_for_course(course: $course);
        $userstoadd = course_communication_helper::get_users_has_access_to_all_groups(
            userids: $enrolledusers,
            courseid: $course->id,
        );
        $communication->add_members_to_room(
            userids: $userstoadd,
            queue: false,
        );
    }

    /**
     * Communication api call to update room for a group if course has group mode enabled.
     *
     * @param group_updated $hook The group updated hook.
     */
    public static function update_group_communication(
        group_updated $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $group = $hook->get_instance();
        $course = \core_communication\helper::get_course(
            courseid: $group->courseid,
        );

        // Check if group mode enabled before handling the communication.
        if (!helper::is_group_mode_enabled_for_course(course: $course)) {
            return;
        }

        $coursecontext = \context_course::instance(courseid: $course->id);
        $communication = helper::load_by_group(
            groupid: $group->id,
            context: $coursecontext,
        );

        // If the name didn't change, then we don't need to update the room.
        if ($group->name === $communication->get_room_name()) {
            return;
        }

        $communication->update_room(
            active: processor::PROVIDER_ACTIVE,
            communicationroomname: $group->name,
            instance: $course,
        );
    }

    /**
     * Delete the communication room for a group if course has group mode enabled.
     *
     * @param group_deleted_post $hook The group deleted hook.
     */
    public static function delete_group_communication(
        group_deleted_post $hook
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $group = $hook->get_instance();
        $course = \core_communication\helper::get_course(
            courseid: $group->courseid,
        );

        $context = context_course::instance($course->id);
        $communication = helper::load_by_group(
            groupid: $group->id,
            context: $context,
        );
        $communication->delete_room();
    }
}
