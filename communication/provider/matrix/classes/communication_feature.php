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

use core_communication\communication_processor;

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
    \core_communication\room_user_provider {


    /** @var matrix_events_manager $eventmanager The event manager object to get the endpoints */
    private matrix_events_manager $eventmanager;

    /** @var matrix_rooms $matrixrooms The matrix room object to update room information */
    private matrix_rooms $matrixrooms;

    /**
     * Load the communication provider for the communication api.
     *
     * @param communication_processor $communication The communication processor object
     * @return communication_feature The communication provider object
     */
    public static function load_for_instance(communication_processor $communication,
    ): self {
        return new self($communication);
    }

    /**
     * Constructor for communication provider to initialize necessary objects for api cals etc..
     *
     * @param communication_processor $communication The communication processor object
     */
    private function __construct(
        private \core_communication\communication_processor $communication,
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
                    $this->add_registered_matrix_user_to_room($matrixuserid);
                }
            }
        }
    }

    /**
     * Add members to a room.
     *
     * @param array $userids The user ids to add
     */
    public function add_members_to_room(array $userids): void {
        $unregisteredmembers = [];

        foreach ($userids as $userid) {
            $matrixuserid = matrix_user_manager::get_matrixid_from_moodle(
                $userid,
                $this->eventmanager->matrixhomeserverurl
            );

            if ($matrixuserid && $this->check_user_exists($matrixuserid)) {
                $this->add_registered_matrix_user_to_room($matrixuserid);
            } else {
                $unregisteredmembers[] = $userid;
            }
        }

        // Create Matrix users.
        if (count($unregisteredmembers) > 0) {
            $this->create_members($unregisteredmembers);
        }
    }

    /**
     * Adds the registered matrix user id to room.
     *
     * @param string $matrixuserid Registered matrix user id
     */
    private function add_registered_matrix_user_to_room(string $matrixuserid): void {
        if (!$this->check_room_membership($matrixuserid)) {
            $json = ['user_id' => $matrixuserid];
            $headers = ['Content-Type' => 'application/json'];

            $this->eventmanager->request($json, $headers)->post($this->eventmanager->get_room_membership_join_endpoint());
        }
    }

    /**
     * Remove members from a room.
     *
     * @param array $userids The Moodle user ids to remove
     */
    public function remove_members_from_room(array $userids): void {
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
                $this->eventmanager->request($json, $headers)->post($this->eventmanager->get_room_membership_kick_endpoint());
            }
        }
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

    public function create_or_update_chat_room(): bool {
        // Check if room exists, then update if existing.
        if ($this->matrixrooms->room_record_exists()) {
            // Get room data.
            $matrixroomdata = $this->eventmanager->request()->get($this->eventmanager->get_room_info_endpoint());
            $matrixroomdata = json_decode($matrixroomdata->getBody(), false, 512, JSON_THROW_ON_ERROR);

            // Update the room name when it's updated from the form.
            if ($matrixroomdata->name !== $this->communication->get_room_name()) {
                $json = ['name' => $this->communication->get_room_name()];
                $this->eventmanager->request($json)->put($this->eventmanager->get_update_room_name_endpoint());
            }

            // Update room avatar.
            $this->update_room_avatar();

            return true;
        }

        // Create a new room.
        $alias = str_replace(' ', '', $this->communication->get_room_name());
        $json = [
            'name' => $this->communication->get_room_name(),
            'visibility' => 'private',
            'preset' => 'private_chat',
            'room_alias_name' => $alias,
            'initial_state' => [],
        ];

        $response = $this->eventmanager->request($json)->post($this->eventmanager->get_create_room_endpoint());
        $response = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);

        // Check if room was created.
        if (!empty($roomid = $response->room_id)) {
            $roomalias = '#' . $alias . ':' . matrix_user_manager::set_matrix_home_server($this->eventmanager->matrixhomeserverurl);
            $this->matrixrooms->create_matrix_room_record($this->communication->get_id(), $roomid, $roomalias);
            $this->eventmanager->roomid = $roomid;
            $this->update_room_avatar();
            return true;
        }

        return false;
    }

    public function delete_chat_room(): bool {
        return $this->matrixrooms->delete_matrix_room_record();
    }

    /**
     * Update the room avatar when an instance image is added or updated.
     */
    public function update_room_avatar(): void {
        $instanceimage = $this->communication->get_avatar();
        $contenturi = null;

        // If avatar is set for the instance, update in matrix.
        if (!empty($instanceimage)) {
            // First upload the content.
            $contenturi = $this->eventmanager->upload_matrix_content($instanceimage->get_content());
        }

        // Now update the room avatar.
        $json = [
            'url' => $contenturi,
        ];

        $this->eventmanager->request($json, [], false)->put($this->eventmanager->get_update_avatar_endpoint());
    }

    public function get_chat_room_url(): ?string {
        // Check for room record in Moodle and that it exists in Matrix.
        if (!$this->matrixrooms->room_record_exists()) {
            return null;
        }

        return $this->eventmanager->matrixwebclienturl . '#/room/' . $this->matrixrooms->get_matrix_room_alias();
    }
}
