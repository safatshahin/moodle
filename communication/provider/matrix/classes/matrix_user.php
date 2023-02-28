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

use core_communication\communication_user_base;

// Will be used to check for custom profile field.
require_once("$CFG->dirroot/user/profile/lib.php");

/**
 * Class matrix_user to manage matrix provider users.
 *
 * @package    communication_matrix
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix_user extends communication_user_base {

    /**
     * @var matrix_events_manager $eventmanager The event manager object to get the endpoints
     */
    private matrix_events_manager $eventmanager;

    /**
     * @var matrix_rooms $matrixrooms The matrix room object to update room information
     */
    private matrix_rooms $matrixrooms;

    /**
     * @var string $matrixuserid The user matrix id to update user profile
     */
    private string $matrixuserid;

    protected function init(): void {
        $this->matrixrooms = new matrix_rooms($this->communication->communicationsettings->get_communication_instance_id());
        $this->eventmanager = new matrix_events_manager($this->matrixrooms->roomid);
    }

    public function create_members(array $userids): void {
        foreach ($userids as $userid) {
            $user = \core_user::get_user($userid);
            $qualifiedmuid = $this->set_qualified_matrix_user_id($user->username);
            $json = [
                'displayname' => $user->fullname,
                'threepids' => [
                    'medium' => 'email',
                    'address' => $user->email
                ],
                'external_ids' => []
            ];

            $response = $this->eventmanager->request($json)->post($this->eventmanager->get_create_user_endpoint($qualifiedmuid));
            $response = json_decode($response->getBody());

            if (!empty($matrixuserid = $response->name)) {
                $this->matrixuserid = $matrixuserid;
                $this->add_user_matrix_id($userid);
            } else {
                throw new \coding_exception('Can not update record without matrix user id');
            }
        }
    }

    public function add_members_to_room(array $userids): void {
        // TODO: MDL-76708 Implement add_members_to_room() method.
    }

    public function remove_members_from_room(array $userids): void {
        // TODO: MDL-76708 Implement remove_members_from_room() method.
    }

    /**
     * Add user's matrix user id.
     *
     * @return void
     */
    public function add_user_matrix_id(string $userid): void {
        if (!$this->matrix_user_id_exists_in_moodle($userid)) {
            $matrixprofilefield = get_config('communication_matrix', 'profile_field_name');

            $userinfofield = profile_user_record($userid);
            if (isset($userinfofield->{$matrixprofilefield}) && !empty($userinfofield->{$matrixprofilefield})) {
                $userinfodata = new \stdClass();
                $userinfodata->userid = $userid;
                $userinfodata->data = $this->matrixuserid;
                $userinfodata->fieldid = $userinfofield->id;
                profile_save_data($userinfodata);
            }
        }
    }

    /**
     * Checks if matrix user id exists in moodle.
     *
     * @param string $userid The moodle user id
     * @return boolean
     */
    public function matrix_user_id_exists_in_moodle(string $userid): bool {
        // Grab matrix profile_field setting.
        $matrixprofilefield = get_config('communication_matrix', 'profile_field_name');

        $userinfofield = profile_user_record($userid);
        if (isset($userinfofield->{$matrixprofilefield})) {
            if (!empty($userinfofield->{$matrixprofilefield})) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Sets qualified matrix user user id
     *
     * @param string $username Moodle user's username
     * @return string
     */
    private function set_qualified_matrix_user_id(string $username) : string {
        $homeserver = parse_url($this->eventmanager->matrixhomeserverurl)['host'];
        $homeserver = strpos($homeserver, '.') !== false ? $homeserver : explode('.', $homeserver)[0];
        $homeserver = strpos($homeserver, ':') !== false ? $homeserver : explode(':', $homeserver)[0];
        return "@{$username}:{$homeserver}";
    }
}
