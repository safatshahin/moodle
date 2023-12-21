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
use core\hook\described_hook;
use core_course\communication\communication_helper as course_communication_helper;
use core_group\hook\group_created_post;
use core_group\hook\group_deleted_post;
use core_group\hook\group_membership_added;
use core_group\hook\group_membership_removed;
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
     * Get the course and group object for the group hook.
     *
     * @param described_hook $hook The hook object.
     * @return array
     */
    protected static function get_group_and_course_data_for_group_hook(described_hook $hook): array {
        $group = $hook->get_instance();
        $course = helper::get_course(
            courseid: $group->courseid,
        );

        return [
            $group,
            $course,
        ];
    }

    /**
     * Communication api call to create room for a group if course has group mode enabled.
     *
     * @param group_created_post $hook The group created hook.
     */
    public static function create_group_communication(
        group_created_post $hook,
    ): void {
        [$group, $course] = self::get_group_and_course_data_for_group_hook(
            hook: $hook,
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
        [$group, $course] = self::get_group_and_course_data_for_group_hook(
            hook: $hook,
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
        [$group, $course] = self::get_group_and_course_data_for_group_hook(
            hook: $hook,
        );

        // Check if group mode enabled before handling the communication.
        if (!helper::is_group_mode_enabled_for_course(course: $course)) {
            return;
        }

        $context = context_course::instance($course->id);
        $communication = helper::load_by_group(
            groupid: $group->id,
            context: $context,
        );
        $communication->delete_room();
    }

    /**
     * Add members to group room when a new member is added to the group.
     *
     * @param group_membership_added $hook The group membership added hook.
     */
    public static function add_members_to_group_room(
        group_membership_added $hook,
    ): void {
        [$group, $course] = self::get_group_and_course_data_for_group_hook(
            hook: $hook,
        );

        // Check if group mode enabled before handling the communication.
        if (!helper::is_group_mode_enabled_for_course(course: $course)) {
            return;
        }

        $context = context_course::instance($course->id);
        $communication = helper::load_by_group(
            groupid: $group->id,
            context: $context,
        );
        $communication->add_members_to_room(
            userids: $hook->get_userids(),
        );
    }

    /**
     * Remove members from the room when a member is removed from group room.
     *
     * @param group_membership_removed $hook The group membership removed hook.
     */
    public static function remove_members_from_group_room(
        group_membership_removed $hook,
    ): void {
        [$group, $course] = self::get_group_and_course_data_for_group_hook(
            hook: $hook,
        );

        // Check if group mode enabled before handling the communication.
        if (!helper::is_group_mode_enabled_for_course(course: $course)) {
            return;
        }

        $context = context_course::instance($course->id);
        $communication = helper::load_by_group(
            groupid: $group->id,
            context: $context,
        );
        $communication->remove_members_from_room(
            userids: $hook->get_userids(),
        );
    }

    /**
     * Update course communication according to course data.
     * Course can have course or group rooms. Group mode enabling will create rooms for groups.
     *
     * @param stdClass $course The course data
     * @param stdClass $oldcourse The old course data before the update
     * @param bool $changesincoursecat Whether the course moved to a different category
     */
    public static function update_course_communication(
        stdClass $course,
        stdClass $oldcourse,
        bool $changesincoursecat
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        // Check if provider is selected.
        $provider = $course->selectedcommunication ?? null;
        // If the course moved to hidden category, set provider to none.
        if ($changesincoursecat && empty($course->visible)) {
            $provider = processor::PROVIDER_NONE;
        }

        // Get the course context.
        $coursecontext = \context_course::instance(courseid: $course->id);
        // Get the course image.
        $courseimage = course_get_courseimage(course: $course);
        // Get the course communication instance.
        $coursecommunication = self::load_by_course(
            courseid: $course->id,
            context: $coursecontext,
        );

        // Attempt to get the communication provider if it wasn't provided in the data.
        if (empty($provider)) {
            $provider = $coursecommunication->get_provider();
        }

        // This nasty logic is here because of hide course doesn't pass anything in the data object.
        if (!empty($course->communicationroomname)) {
            $coursecommunicationroomname = $course->communicationroomname;
        } else {
            $coursecommunicationroomname = $course->fullname ?? $oldcourse->fullname;
        }

        // List of enrolled users for course communication.
        $enrolledusers = self::get_enrolled_users_for_course(course: $course);

        // Check for group mode, we will have to get the course data again as the group info is not always in the object.
        $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;

        // If group mode is disabled, get the communication information for creating room for a course.
        if ((int)$groupmode === NOGROUPS) {
            // Remove all the members from active group rooms if there is any.
            $coursegroups = groups_get_all_groups(courseid: $course->id);
            foreach ($coursegroups as $coursegroup) {
                $communication = groupcommunication_helper::load_by_group(
                    groupid: $coursegroup->id,
                    context: $coursecontext,
                );
                // Remove the members from the group room.
                $communication->remove_all_members_from_room();
                // Now delete the group room.
                $communication->update_room(active: processor::PROVIDER_INACTIVE);
            }

            // Now create/update the course room.
            $communication = self::load_by_course(
                courseid: $course->id,
                context: $coursecontext,
            );
            $communication->configure_room_and_membership_by_provider(
                provider: $provider,
                instance: $course,
                communicationroomname: $coursecommunicationroomname,
                users: $enrolledusers,
                instanceimage: $courseimage,
            );
        } else {
            // Update the group communication instances.
            self::update_group_communication_instances(
                course: $course,
                provider: $provider,
            );

            // Remove all the members for the course room if instance available.
            $communication = self::load_by_course(
                courseid: $course->id,
                context: $coursecontext,
                provider: $provider === processor::PROVIDER_NONE ? null : $provider,
            );
            $communication->remove_all_members_from_room();
            // Now update the course communication instance with the latest changes.
            // We are not making room for this instance as it is a group mode enabled course.
            // If provider is none, then we will make the room inactive, otherwise always active in group mode.
            $communication->update_room(
                active: $provider === processor::PROVIDER_NONE ? processor::PROVIDER_INACTIVE : processor::PROVIDER_ACTIVE,
                communicationroomname: $coursecommunicationroomname,
                avatar: $courseimage,
                instance: $course,
                queue: false,
            );
        }
    }

    /**
     * Get the course communication status notification for course.
     *
     * @param \stdClass $course The course object.
     */
    public static function get_course_communication_status_notification(\stdClass $course): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        // Get the group mode for this course.
        $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;
        $coursecontext = \context_course::instance(courseid: $course->id);

        // If group mode is not set then just handle the course communication for these users.
        if ((int)$groupmode === NOGROUPS) {
            $communication = self::load_by_course(
                courseid: $course->id,
                context: $coursecontext,
            );
            $communication->show_communication_room_status_notification();
        } else {
            // If group mode is set then handle the group communication rooms for these users.
            $coursegroups = groups_get_all_groups(courseid: $course->id);
            $numberofgroups = count($coursegroups);

            // If no groups available, nothing to show.
            if ($numberofgroups === 0) {
                return;
            }

            $numberofreadygroups = 0;

            foreach ($coursegroups as $coursegroup) {
                $communication = groupcommunication_helper::load_by_group(
                    groupid: $coursegroup->id,
                    context: $coursecontext,
                );
                $roomstatus = $communication->get_communication_room_url() ? 'ready' : 'pending';
                switch ($roomstatus) {
                    case 'ready':
                        $numberofreadygroups ++;
                        break;
                    case 'pending':
                        $pendincommunicationobject = $communication;
                        break;
                }
            }

            if ($numberofgroups === $numberofreadygroups) {
                $communication->show_communication_room_status_notification();
            } else {
                $pendincommunicationobject->show_communication_room_status_notification();
            }
        }
    }

    /**
     * Delete course communication data and remove members.
     * Course can have communication data if it is a group or a course.
     * This action is important to perform even if the experimental feature is disabled.
     *
     * @param stdclass $course The course object.
     */
    public static function delete_course_communication(stdclass $course): void {
        $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;
        $coursecontext = \context_course::instance(courseid: $course->id);

        // If group mode is not set then just handle the course communication room.
        if ((int)$groupmode === NOGROUPS) {
            $communication = self::load_by_course(
                courseid: $course->id,
                context: $coursecontext,
            );
            $communication->delete_room();
        } else {
            // If group mode is set then handle the group communication rooms.
            $coursegroups = groups_get_all_groups(courseid: $course->id);
            foreach ($coursegroups as $coursegroup) {
                $communication = \core_group\communication\communication_helper::load_by_group(
                    groupid: $coursegroup->id,
                    context: $coursecontext,
                );
                $communication->delete_room();
            }
        }
    }

    /**
     * Create course communication instance.
     *
     * @param stdClass $course The course object.
     */
    public static function create_course_communication_instance(stdClass $course): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        // Check for default provider config setting.
        $defaultprovider = get_config(
            plugin: 'moodlecourse',
            name: 'coursecommunicationprovider',
        );
        $provider = $course->selectedcommunication ?? $defaultprovider;

        if (empty($provider) && $provider === processor::PROVIDER_NONE) {
            return;
        }

        // Check for group mode, we will have to get the course data again as the group info is not always in the object.
        $createcourseroom = true;
        $creategrouprooms = false;
        $coursedata = get_course(courseid: $course->id);
        $groupmode = $course->groupmode ?? $coursedata->groupmode;
        if ((int)$groupmode !== NOGROUPS) {
            $createcourseroom = false;
            $creategrouprooms = true;
        }

        // Prepare the communication api data.
        $courseimage = course_get_courseimage(course: $course);
        $communicationroomname = !empty($course->communicationroomname) ? $course->communicationroomname : $coursedata->fullname;
        $coursecontext = \context_course::instance(courseid: $course->id);
        // Communication api call for course communication.
        $communication = \core_communication\api::load_by_instance(
            context: $coursecontext,
            component: self::COURSE_COMMUNICATION_COMPONENT,
            instancetype: self::COURSE_COMMUNICATION_INSTANCETYPE,
            instanceid: $course->id,
            provider: $provider,
        );
        $communication->create_and_configure_room(
            communicationroomname: $communicationroomname,
            avatar: $courseimage,
            instance: $course,
            queue: $createcourseroom,
        );

        // Communication api call for group communication.
        if ($creategrouprooms) {
            self::update_group_communication_instances(
                course: $course,
                provider: $provider,
            );
        } else {
            $enrolledusers = self::get_enrolled_users_for_course(course: $course);
            $communication->add_members_to_room(
                userids: $enrolledusers,
                queue: false,
            );
        }
    }

    /**
     * Update the group communication instances.
     *
     * @param stdClass $course The course object.
     * @param string $provider The provider name.
     */
    public static function update_group_communication_instances(
        stdClass $course,
        string $provider,
    ): void {
        $coursegroups = groups_get_all_groups(courseid: $course->id);
        $coursecontext = \context_course::instance(courseid: $course->id);
        $allaccessgroupusers = self::get_users_has_access_to_all_groups(
            userids: self::get_enrolled_users_for_course(course: $course),
            courseid: $course->id,
        );

        foreach ($coursegroups as $coursegroup) {
            $groupuserstoadd = array_column(
                groups_get_members(groupid: $coursegroup->id),
                'id',
            );

            foreach ($allaccessgroupusers as $allaccessgroupuser) {
                if (!in_array($allaccessgroupuser, $groupuserstoadd, true)) {
                    $groupuserstoadd[] = $allaccessgroupuser;
                }
            }

            // Now create/update the group room.
            $communication = groupcommunication_helper::load_by_group(
                groupid: $coursegroup->id,
                context: $coursecontext,
            );
            $communication->configure_room_and_membership_by_provider(
                provider: $provider,
                instance: $course,
                communicationroomname: $coursegroup->name,
                users: $groupuserstoadd,
            );
        }
    }
}
