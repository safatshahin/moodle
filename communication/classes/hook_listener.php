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
use core_course\hook\course_created_post;
use core_course\hook\course_deleted_pre;
use core_course\hook\course_updated;
use core_group\hook\group_created_post;
use core_group\hook\group_deleted_post;
use core_group\hook\group_membership_added;
use core_group\hook\group_membership_removed;
use core_group\hook\group_updated;
use core_user\hook\user_deleted_pre;
use core_user\hook\user_updated_pre;
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
     * Create course communication instance.
     *
     * @param course_created_post $hook The course created hook.
     */
    public static function create_course_communication(
        course_created_post $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $course = $hook->get_instance();

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
            component: helper::COURSE_COMMUNICATION_COMPONENT,
            instancetype: helper::COURSE_COMMUNICATION_INSTANCETYPE,
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
            helper::update_group_communication_instances_for_course(
                course: $course,
                provider: $provider,
            );
        } else {
            $enrolledusers = helper::get_enrolled_users_for_course(course: $course);
            $communication->add_members_to_room(
                userids: $enrolledusers,
                queue: false,
            );
        }
    }

    /**
     * Update the course communication instance.
     *
     * @param course_updated $hook The course updated hook.
     */
    public static function update_course_communication(
        course_updated $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }
        $course = $hook->get_instance();
        $oldcourse = $hook->get_old_instance();
        $changeincoursecat = $hook->is_course_category_changed();
        $groupmode = $course->groupmode ?? get_course($course->id)->groupmode;
        if ($changeincoursecat || $groupmode !== $oldcourse->groupmode) {
            helper::update_course_communication_instance(
                course: $course,
                oldcourse: $oldcourse,
                changesincoursecat: $changeincoursecat,
            );
        }
    }

    /**
     * Delete course communication data and remove members.
     * Course can have communication data if it is a group or a course.
     * This action is important to perform even if the experimental feature is disabled.
     *
     * @param course_deleted_pre $hook The course deleted hook.
     */
    public static function delete_course_communication(
        course_deleted_pre $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $course = $hook->get_instance();
        $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;
        $coursecontext = \context_course::instance(courseid: $course->id);

        // If group mode is not set then just handle the course communication room.
        if ((int)$groupmode === NOGROUPS) {
            $communication = helper::load_by_course(
                courseid: $course->id,
                context: $coursecontext,
            );
            $communication->delete_room();
        } else {
            // If group mode is set then handle the group communication rooms.
            $coursegroups = groups_get_all_groups(courseid: $course->id);
            foreach ($coursegroups as $coursegroup) {
                $communication = helper::load_by_group(
                    groupid: $coursegroup->id,
                    context: $coursecontext,
                );
                $communication->delete_room();
            }
        }
    }

    /**
     * Update the room membership for the user updates.
     *
     * @param user_updated_pre $hook The user updated hook.
     */
    public static function update_user_room_memberships(
        user_updated_pre $hook,
    ): void {
        // If the communication subsystem is not enabled then just ignore.
        if (!api::is_available()) {
            return;
        }

        $user = $hook->get_instance();
        $currentuserrecord = $hook->get_old_instance();

        // Get the user courses.
        $usercourses = enrol_get_users_courses(userid: $user->id);

        // If the user is suspended then remove the user from all the rooms.
        // Otherwise add the user to all the rooms for the courses the user enrolled in.
        if (!empty($currentuserrecord) && isset($user->suspended) && $currentuserrecord->suspended !== $user->suspended) {
            // Decide the action for the communication api for the user.
            $memberaction = ($user->suspended === 0) ? 'add_members_to_room' : 'remove_members_from_room';
            foreach ($usercourses as $usercourse) {
                helper::update_course_communication_room_membership(
                    course: $usercourse,
                    userids: [$user->id],
                    memberaction: $memberaction,
                );
            }
        }
    }

    /**
     * Delete all room memberships for a user.
     *
     * @param user_deleted_pre $hook The user deleted hook.
     */
    public static function delete_user_room_memberships(
        user_deleted_pre $hook,
    ): void {
        if (!api::is_available()) {
            return;
        }

        $user = $hook->get_instance();

        foreach (enrol_get_users_courses(userid: $user->id) as $course) {
            $groupmode = $course->groupmode ?? get_course(courseid: $course->id)->groupmode;
            $coursecontext = \context_course::instance(courseid: $course->id);

            if ((int)$groupmode === NOGROUPS) {
                $communication = helper::load_by_course(
                    courseid: $course->id,
                    context: $coursecontext,
                );
                $communication->get_room_user_provider()->remove_members_from_room(userids: [$user->id]);
                $communication->get_processor()->delete_instance_user_mapping(userids: [$user->id]);
            } else {
                // If group mode is set then handle the group communication rooms.
                $coursegroups = groups_get_all_groups(courseid: $course->id);
                foreach ($coursegroups as $coursegroup) {
                    $communication = helper::load_by_group(
                        groupid: $coursegroup->id,
                        context: $coursecontext,
                    );
                    $communication->get_room_user_provider()->remove_members_from_room(userids: [$user->id]);
                    $communication->get_processor()->delete_instance_user_mapping(userids: [$user->id]);
                }
            }
        }
    }
}
