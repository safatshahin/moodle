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

namespace communication_mock;

use core_communication\processor;

/**
 * class communication_mock to handle mock specific actions.
 *
 * @package    communication_mock
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_feature implements
    \core_communication\communication_provider,
    \core_communication\user_provider,
    \core_communication\room_chat_provider,
    \core_communication\room_user_provider {

    /**
     * Load the communication provider for the communication api.
     *
     * @param processor $communication The communication processor object
     * @return communication_feature The communication provider object
     */
    public static function load_for_instance(processor $communication,
    ): self {
        return new self($communication);
    }

    /**
     * Constructor for communication provider to initialize necessary objects for api cals etc..
     *
     * @param processor $communication The communication processor object
     */
    private function __construct(
        private \core_communication\processor $communication,
    ) {}

    /**
     * Creates local directory.
     * @return string
     */
    public function create_local_dir() {
        global $CFG;
        if (!file_exists ($CFG->dataroot . '/temp/communication_mock')) {
            make_temp_directory('communication_mock/');
        }
        return 'communication_mock/';
    }

    /**
     * Create members.
     *
     * @param array $userids The Moodle user ids to create
     */
    public function create_members(array $userids): void {
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "createmembers.txt";
        $fileToUpload = $localdir. $temp_filename;
        file_put_contents($fileToUpload, json_encode($userids));
    }

    /**
     * Add members to a room.
     *
     * @param array $userids The user ids to add
     */
    public function add_members_to_room(array $userids): void {
        $this->create_members($userids);
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "addedmembers.txt";
        $fileToUpload = $localdir. $temp_filename;
        file_put_contents($fileToUpload, json_encode($userids));
    }

    /**
     * Remove members from a room.
     *
     * @param array $userids The Moodle user ids to remove
     */
    public function remove_members_from_room(array $userids): void {
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "removedmembers.txt";
        $fileToUpload = $localdir. $temp_filename;
        file_put_contents($fileToUpload, json_encode($userids));
    }


    public function create_chat_room(): bool {
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "createdchatroom.txt";
        $fileToUpload = $localdir. $temp_filename;
        $communicationdata = $this->communication->get_id() . '_' . $this->communication->get_room_name();
        file_put_contents($fileToUpload, json_encode($communicationdata));
        return true;
    }

    public function update_chat_room(): bool {
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "updatedchatroom.txt";
        $fileToUpload = $localdir. $temp_filename;
        $communicationdata = $this->communication->get_id() . '_' . $this->communication->get_room_name();
        file_put_contents($fileToUpload, json_encode($communicationdata));
        return true;
    }

    public function delete_chat_room(): bool {
        global $CFG;
        $tempdir = $this->create_local_dir();
        $localdir = $CFG->dataroot . '/temp/'.$tempdir;
        $temp_filename = "deletedchatroom.txt";
        $fileToUpload = $localdir. $temp_filename;
        $communicationdata = $this->communication->get_id() . '_' . $this->communication->get_room_name();
        file_put_contents($fileToUpload, json_encode($communicationdata));
        return true;
    }
    public function get_chat_room_url(): ?string {
        return 'https://www.moodle.org';
    }
}
