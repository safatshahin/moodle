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

namespace communication_matrix;

use context_course;
use core_communication\processor;

/**
 * class communication_feature to handle matrix specific actions.
 *
 * @package    communication_matrix
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_feature implements
    \core_communication\communication_provider,
    \core_communication\user_provider,
    \core_communication\room_chat_provider,
    \core_communication\room_user_provider,
    \core_communication\form_provider {

    /** @var matrix_events_manager $eventmanager The event manager object to get the endpoints */
    private matrix_events_manager $eventmanager;

    /** @var matrix_rooms $matrixrooms The matrix room object to update room information */
    private matrix_rooms $matrixrooms;

    /**
     * User default power level for matrix.
     */
    private const POWER_LEVEL_DEFAULT = 0;

    /**
     * User moderator power level for matrix.
     */
    private const POWER_LEVEL_MODERATOR = 50;

    /**
     * User power level for matrix associated to moodle site admins. It is a custom power level for site admins.
     */
    private const POWER_LEVEL_MOODLE_SITE_ADMIN = 90;

    /**
     * User maximum power level for matrix. This is only associated to the token user to allow god mode actions.
     */
    private const POWER_LEVEL_MAXIMUM = 100;

    /**
     * Load the communication provider for the communication api.
     *
     * @param processor $communication The communication processor object
     * @return communication_feature The communication provider object
     */
    public static function load_for_instance(processor $communication): self {
        return new self($communication);
    }

    /**
     * Constructor for communication provider to initialize necessary objects for api cals etc..
     *
     * @param processor $communication The communication processor object
     */
    private function __construct(
        private \core_communication\processor $communication,
    ) {
        $this->matrixrooms = new matrix_rooms($communication->get_id());
        $this->eventmanager = new matrix_events_manager($this->matrixrooms->get_matrix_room_id());
    }

    /**
     * Create members.
     *
     * @param array $userids The Moodle user ids to create
     */
    public function create_members(array $userids): void {
        $addedmembers = [];

        foreach ($userids as $userid) {
            $user = \core_user::get_user($userid);
            $userfullname = fullname($user);

            // Proceed if we have a user's full name and email to work with.
            if (!empty($user->email) && !empty($userfullname)) {
                $json = [
                    'displayname' => $userfullname,
                    'threepids' => [(object)[
                        'medium' => 'email',
                        'address' => $user->email
                    ]],
                    'external_ids' => []
                ];

                list($qualifiedmuid, $pureusername) = matrix_user_manager::set_qualified_matrix_user_id(
                    $userid,
                    $this->eventmanager->matrixhomeserverurl
                );

                // First create user in matrix.
                $response = $this->eventmanager->request($json)->put($this->eventmanager->get_create_user_endpoint($qualifiedmuid));
                $response = json_decode($response->getBody());

                if (!empty($matrixuserid = $response->name)) {
                    // Then create matrix user id in moodle.
                    matrix_user_manager::add_user_matrix_id_to_moodle($userid, $pureusername);
                    if ($this->add_registered_matrix_user_to_room($matrixuserid)) {
                        $addedmembers[] = $userid;
                    }
                }
            }
        }

        // Set the power level of the users.
        if (!empty($addedmembers) && $this->is_power_levels_update_required($addedmembers)) {
            $this->set_matrix_power_levels();
        }

        // Mark then users as synced for the added members.
        $this->communication->mark_users_as_synced($addedmembers);
    }

    public function update_room_membership(array $userids): void {
        if ($this->is_power_levels_update_required($userids)) {
            $this->set_matrix_power_levels();
        }
        // Mark then users as synced for the updated members.
        $this->communication->mark_users_as_synced($userids);
    }

    /**
     * Add members to a room.
     *
     * @param array $userids The user ids to add
     */
    public function add_members_to_room(array $userids): void {
        $unregisteredmembers = [];
        $addedmembers = [];

        foreach ($userids as $userid) {
            $matrixuserid = matrix_user_manager::get_matrixid_from_moodle(
                $userid,
                $this->eventmanager->matrixhomeserverurl
            );

            if ($matrixuserid && $this->check_user_exists($matrixuserid)) {
                if ($this->add_registered_matrix_user_to_room($matrixuserid)) {
                    $addedmembers[] = $userid;
                }
            } else {
                $unregisteredmembers[] = $userid;
            }
        }

        // Set the power level of the users.
        if (!empty($addedmembers) && $this->is_power_levels_update_required($addedmembers)) {
            $this->set_matrix_power_levels();
        }

        // Mark then users as synced for the added members.
        $this->communication->mark_users_as_synced($addedmembers);

        // Create Matrix users.
        if (count($unregisteredmembers) > 0) {
            $this->create_members($unregisteredmembers);
        }
    }

    /**
     * Determine if a power level update is required.
     * Matrix will always set a user to the default power level of 0 when a power level update is made.
     * That is, unless we specify another level. As long as one person's level is greater than the default,
     * we will need to set the power levels of all users greater than the default.
     *
     * @param array $userids The users to evaluate
     * @return boolean Returns true if an update is required
     */
    private function is_power_levels_update_required(array $userids): bool {
        // Is the user's power level greater than the default?
        foreach ($userids as $userid) {
            if ($this->get_user_allowed_power_level($userid) > self::POWER_LEVEL_DEFAULT) {
                return true;
            }
        }
        return false;

    }

    /**
     * Set the matrix power level with the room.
     *
     * @param array $resetusers The list of users to override and reset their power level to 0
     */
    private function set_matrix_power_levels(array $resetusers = []): void {
        // Get all the current users for the room.
        $existingusers = $this->communication->get_all_userids_for_instance();

        $userpowerlevels = [];
        foreach ($existingusers as $existinguser) {
            $matrixuserid = matrix_user_manager::get_matrixid_from_moodle(
                $existinguser,
                $this->eventmanager->matrixhomeserverurl
            );

            if (!$matrixuserid) {
                continue;
            }

            if (!empty($resetusers) && in_array($existinguser, $resetusers, true)) {
                $userpowerlevels[$matrixuserid] = self::POWER_LEVEL_DEFAULT;
            } else {
                $existinguserpowerlevel = $this->get_user_allowed_power_level($existinguser);
                // We don't need to include the default power level users in request, as matrix will make then default anyways.
                if ($existinguserpowerlevel > self::POWER_LEVEL_DEFAULT) {
                    $userpowerlevels[$matrixuserid] = $existinguserpowerlevel;
                }
            }
        }

        // Now add the token user permission to retain the permission in the room.
        $matrixtokenuser = $this->eventmanager->get_token_user();
        if ($matrixtokenuser && $this->check_user_exists($matrixtokenuser)) {
            $userpowerlevels[$matrixtokenuser] = self::POWER_LEVEL_MAXIMUM;
        }

        // The json to set the user power level.
        $json = [
            'ban' => self::POWER_LEVEL_MAXIMUM,
            'invite' => self::POWER_LEVEL_MODERATOR,
            'kick' => self::POWER_LEVEL_MODERATOR,
            'notifications' => [
                'room' => self::POWER_LEVEL_MODERATOR,
            ],
            'redact' => self::POWER_LEVEL_MODERATOR,
            'users' => $userpowerlevels,
        ];

        $this->eventmanager->request($json)->put($this->eventmanager->get_set_power_levels_endpoint());
    }

    /**
     * Get the allowed power level for the user id according to perms/site admin or default.
     *
     * @param int $userid
     * @return int
     */
    public function get_user_allowed_power_level(int $userid): int {
        $context = context_course::instance($this->communication->get_instance_id());
        $powerlevel = self::POWER_LEVEL_DEFAULT;

        if (has_capability('communication/matrix:moderator', $context, $userid)) {
            $powerlevel = self::POWER_LEVEL_MODERATOR;
        }

        // If site admin, override all caps.
        if (is_siteadmin($userid)) {
            $powerlevel = self::POWER_LEVEL_MOODLE_SITE_ADMIN;
        }

        return $powerlevel;
    }

    /**
     * Adds the registered matrix user id to room.
     *
     * @param string $matrixuserid Registered matrix user id
     * @return bool Returns true if a user is newly added, or an existing member of the room.
     */
    private function add_registered_matrix_user_to_room(string $matrixuserid): bool {
        if (!$this->check_room_membership($matrixuserid)) {
            $json = ['user_id' => $matrixuserid];
            $headers = ['Content-Type' => 'application/json'];

            $response = $this->eventmanager->request(
                $json,
                $headers
            )->post(
                $this->eventmanager->get_room_membership_join_endpoint()
            );
            $response = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
            if (empty($roomid = $response->room_id) && $roomid !== $this->eventmanager->roomid) {
                return false;
            }
        }
        return true;
    }

    /**
     * Remove members from a room.
     *
     * @param array $userids The Moodle user ids to remove
     */
    public function remove_members_from_room(array $userids): void {
        // If the room is not created or not existing, dont need to do anything with users.
        if (!$this->eventmanager->roomid) {
            return;
        }

        // Remove the power level for the user first.
        $this->set_matrix_power_levels($userids);
        $membersremoved = [];

        foreach ($userids as $userid) {
            // Check user is member of room first.
            $matrixuserid = matrix_user_manager::get_matrixid_from_moodle(
                $userid,
                $this->eventmanager->matrixhomeserverurl
            );

            // Check if user is the room admin and halt removal of this user.
            $matrixroomdata = $this->eventmanager->request()->get($this->eventmanager->get_room_info_endpoint());
            $matrixroomdata = json_decode($matrixroomdata->getBody(), false, 512, JSON_THROW_ON_ERROR);
            $roomadmin = $matrixroomdata->creator;
            $isadmin = $matrixuserid === $roomadmin;

            if (
                !$isadmin && $matrixuserid && $this->check_user_exists($matrixuserid) &&
                $this->check_room_membership($matrixuserid)
            ) {
                $json = ['user_id' => $matrixuserid];
                $headers = ['Content-Type' => 'application/json'];
                $this->eventmanager->request(
                    $json,
                    $headers
                )->post(
                    $this->eventmanager->get_room_membership_kick_endpoint()
                );

                $membersremoved[] = $userid;
            }
        }

        $this->communication->delete_instance_user_mapping($membersremoved);
    }

    /**
     * Check if a user exists in Matrix.
     * Use if user existence is needed before doing something else.
     *
     * @param string $matrixuserid The Matrix user id to check
     * @return bool
     */
    public function check_user_exists(string $matrixuserid): bool {
        $response = $this->eventmanager->request([], [], false)->get($this->eventmanager->get_user_info_endpoint($matrixuserid));
        $response = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);

        return isset($response->name);
    }

    /**
     * Check if a user is a member of a room.
     * Use if membership confirmation is needed before doing something else.
     *
     * @param string $matrixuserid The Matrix user id to check
     * @return bool
     */
    public function check_room_membership(string $matrixuserid): bool {
        $response = $this->eventmanager->request([], [], false)->get($this->eventmanager->get_room_membership_joined_endpoint());
        $response = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        // Check user id is in the returned room member ids.
        return isset($response['joined']) && array_key_exists($matrixuserid, $response['joined']);
    }

    public function create_chat_room(): bool {
        if ($this->matrixrooms->room_record_exists() && $this->matrixrooms->get_matrix_room_id()) {
            return $this->update_chat_room();
        }
        // Create a new room.
        $json = [
            'name' => $this->communication->get_room_name(),
            'visibility' => 'private',
            'preset' => 'private_chat',
            'initial_state' => [],
        ];

        // Set the room topic if set.
        if (!empty($matrixroomtopic = $this->matrixrooms->get_matrix_room_topic())) {
            $json['topic'] = $matrixroomtopic;
        }

        $response = $this->eventmanager->request($json)->post($this->eventmanager->get_create_room_endpoint());
        $response = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);

        // Check if room was created.
        if (!empty($roomid = $response->room_id)) {
            if ($this->matrixrooms->room_record_exists()) {
                $this->matrixrooms->update_matrix_room_record($roomid, $matrixroomtopic);
            } else {
                $this->matrixrooms->create_matrix_room_record($this->communication->get_id(), $roomid, $matrixroomtopic);
            }
            $this->eventmanager->roomid = $roomid;
            $this->update_room_avatar();
            return true;
        }

        return false;
    }

    public function update_chat_room(): bool {
        if (!$this->matrixrooms->room_record_exists()) {
            return $this->create_chat_room();
        }

        // Get room data.
        $matrixroomdata = $this->eventmanager->request()->get($this->eventmanager->get_room_info_endpoint());
        $matrixroomdata = json_decode($matrixroomdata->getBody(), false, 512, JSON_THROW_ON_ERROR);

        // Update the room name when it's updated from the form.
        if ($matrixroomdata->name !== $this->communication->get_room_name()) {
            $json = ['name' => $this->communication->get_room_name()];
            $this->eventmanager->request($json)->put($this->eventmanager->get_update_room_name_endpoint());
        }

        // Update the room topic if set.
        if (!empty($matrixroomtopic = $this->matrixrooms->get_matrix_room_topic())) {
            $json = ['topic' => $matrixroomtopic];
            $this->eventmanager->request($json)->put($this->eventmanager->get_update_room_topic_endpoint());
            $this->matrixrooms->update_matrix_room_record($this->matrixrooms->get_matrix_room_id(), $matrixroomtopic);
        }

        // Update room avatar.
        $this->update_room_avatar();

        return true;
    }

    public function delete_chat_room(): bool {
        return $this->matrixrooms->delete_matrix_room_record();
    }

    /**
     * Update the room avatar when an instance image is added or updated.
     */
    public function update_room_avatar(): void {

        // Check if we have an avatar that needs to be synced.
        if (!$this->communication->is_avatar_synced()) {

            $instanceimage = $this->communication->get_avatar();
            $contenturi = null;

            // If avatar is set for the instance, upload to Matrix. Otherwise, leave null for unsetting.
            if (!empty($instanceimage)) {
                $contenturi = $this->eventmanager->upload_matrix_content($instanceimage->get_content());
            }

            $response = $this->eventmanager->request(['url' => $contenturi], [], false)->put(
                $this->eventmanager->get_update_avatar_endpoint());

            // Indicate the avatar has been synced if it was successfully set with Matrix.
            if ($response->getReasonPhrase() === 'OK') {
                $this->communication->set_avatar_synced_flag(true);
            }
        }
    }

    public function get_chat_room_url(): ?string {
        // Check for room record in Moodle and that it exists in Matrix.
        if (!$this->matrixrooms->room_record_exists() || !$this->matrixrooms->get_matrix_room_id()) {
            return null;
        }

        return $this->eventmanager->matrixwebclienturl . '#/room/' . $this->matrixrooms->get_matrix_room_id();
    }

    public function save_form_data(\stdClass $instance): void {
        $matrixroomtopic = $instance->matrixroomtopic ?? null;
        if ($this->matrixrooms->room_record_exists()) {
            $this->matrixrooms->update_matrix_room_record($this->matrixrooms->get_matrix_room_id(), $matrixroomtopic);
        } else {
            // Create the record with empty room id as we don't have it yet.
            $this->matrixrooms->create_matrix_room_record(
                $this->communication->get_id(),
                $this->matrixrooms->get_matrix_room_id(),
                $matrixroomtopic,
            );
        }
    }

    public function set_form_data(\stdClass $instance): void {
        if (!empty($instance->id) && !empty($this->communication->get_id())) {
            $instance->matrixroomtopic = $this->matrixrooms->get_matrix_room_topic();
        }
    }

    public static function set_form_definition(\MoodleQuickForm $mform): void {
        // Room description for the communication provider.
        $mform->insertElementBefore($mform->createElement('text', 'matrixroomtopic',
            get_string('matrixroomtopic', 'communication_matrix'),
            'maxlength="255" size="20"'), 'addcommunicationoptionshere');
        $mform->addHelpButton('matrixroomtopic', 'matrixroomtopic', 'communication_matrix');
        $mform->setType('matrixroomtopic', PARAM_TEXT);
    }
}
