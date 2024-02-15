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

/**
 * Hook listener callbacks.
 *
 * @package    core
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_group\hook\group_created_post::class,
        'callback' => \core_communication\hook_listener::class . '::create_group_communication',
    ],
    [
        'hook' => \core_group\hook\group_updated_post::class,
        'callback' => \core_communication\hook_listener::class . '::update_group_communication',
    ],
    [
        'hook' => \core_group\hook\group_deleted_post::class,
        'callback' => \core_communication\hook_listener::class . '::delete_group_communication',
    ],
    [
        'hook' => \core_group\hook\group_membership_added::class,
        'callback' => \core_communication\hook_listener::class . '::add_members_to_group_room',
    ],
    [
        'hook' => \core_group\hook\group_membership_removed::class,
        'callback' => \core_communication\hook_listener::class . '::remove_members_from_group_room',
    ],
    [
        'hook' => \core_course\hook\after_course_created::class,
        'callback' => \core_communication\hook_listener::class . '::create_course_communication',
    ],
    [
        'hook' => \core_course\hook\after_course_updated::class,
        'callback' => \core_communication\hook_listener::class . '::update_course_communication',
    ],
    [
        'hook' => \core_course\hook\before_course_delete::class,
        'callback' => \core_communication\hook_listener::class . '::delete_course_communication',
    ],
    [
        'hook' => \core_user\hook\user_updated_pre::class,
        'callback' => \core_communication\hook_listener::class . '::update_user_room_memberships',
    ],
    [
        'hook' => \core_user\hook\user_deleted_pre::class,
        'callback' => \core_communication\hook_listener::class . '::delete_user_room_memberships',
    ],
    [
        'hook' => \core\hook\access\role_assigned_post::class,
        'callback' => \core_communication\hook_listener::class . '::update_user_membership_for_role_changes',
    ],
    [
        'hook' => \core\hook\access\role_unassigned_post::class,
        'callback' => \core_communication\hook_listener::class . '::update_user_membership_for_role_changes',
    ],
    [
        'hook' => \core_enrol\hook\enrol_instance_status_updated_post::class,
        'callback' => \core_communication\hook_listener::class . '::update_communication_memberships_for_enrol_status_change',
    ],
    [
        'hook' => \core_enrol\hook\enrol_instance_deleted_pre::class,
        'callback' => \core_communication\hook_listener::class . '::remove_communication_memberships_for_enrol_instance_deletion',
    ],
    [
        'hook' => \core_enrol\hook\user_enrolled_post::class,
        'callback' => \core_communication\hook_listener::class . '::add_communication_membership_for_enrolled_user',
    ],
    [
        'hook' => \core_enrol\hook\user_enrolment_updated_pre::class,
        'callback' => \core_communication\hook_listener::class . '::update_communication_membership_for_updated_user_enrolment',
    ],
    [
        'hook' => \core_enrol\hook\user_unenrolled_pre::class,
        'callback' => \core_communication\hook_listener::class . '::remove_communication_membership_for_unenrolled_user',
    ],
];
