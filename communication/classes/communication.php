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

/**
 * Class communication to manage the base operations of the providers.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication {

    /**
     * @var int $commid The id of the communication instance for communication objects and features
     */
    protected int $commid;

    /**
     * @var string|null The url of the instance avatar
     */
    protected string|null $instanceavatarurl = null;

    /**
     * @var string $roomname The name of the communication room
     */
    protected string $roomname = '';

    /**
     * @var string $roomdescription The description of the communication room
     */
    protected string $roomdescription = '';

    /**
     * @var string $provider The name of the provider
     */
    protected string $provider;

    /**
     * @var communication_room_base $communicationroom The communication room object
     */
    public communication_room_base $communicationroom;

    /**
     * @var communication_user_base $communicationuser The communication user object
     */
    public communication_user_base $communicationuser;

    /**
     * @var array $userids The id of the users
     */
    protected array $userids;

    /**
     * Communication room constructor to get the communication features.
     *
     * @param string $provider the name of the provider
     * @param int $commid The id of the communication instance
     * @param array $userids The users for the communication user api
     */
    public function __construct(string $provider, int $commid, array $userids = []) {
        $this->provider = $provider;
        $this->commid = $commid;
        $this->userids = $userids;
        $this->init_provider();
        $this->init();
    }

    /**
     * Function to allow child classes load objects etc.
     *
     * @return void
     */
    protected function init(): void {
    }

    /**
     * Initialize provider room operations.
     *
     * @return void
     */
    protected function init_provider(): void {
        $plugins = \core_component::get_plugin_list_with_class('communication', 'communication_feature',
            'communication_feature.php');
        $pluginnames = array_keys($plugins);
        if (in_array($this->provider, $pluginnames, true)) {
            $pluginentrypoint = new $plugins[$this->provider] ();
            $communicationroom = $pluginentrypoint->get_provider_room($this);
            if (!empty($communicationroom)) {
                $this->communicationroom = $communicationroom;
            }
            if (!empty($this->userids)) {
                $communicationuser = $pluginentrypoint->get_provider_user($this);
                if (!empty($communicationuser)) {
                    $this->communicationuser = $communicationuser;
                }
            }

        }
    }

    /**
     * Get the communication instance id.
     *
     * @return int
     */
    public function get_communication_instance_id(): int {
        return $this->commid;
    }

    /**
     * Get the instance avatar url.
     *
     * @return string|null
     */
    public function get_instance_avatar_url(): ?string {
        return $this->instanceavatarurl;
    }

    /**
     * Gets the room name.
     *
     * @return string
     */
    public function get_room_name(): string {
        return $this->roomname;
    }

    /**
     * Get the room description.
     *
     * @return string
     */
    public function get_room_description(): string {
        return $this->roomdescription;
    }

    /**
     * Set the room options if necessary before the create or update.
     *
     * @param string|null $roomname The name of the communication room
     * @param string|null $roomdescription The description of the communication room
     * @param string|null $instanceavatarurl The url of the instance avatar
     * @return void
     */
    public function set_room_options(string $roomname = null, string $roomdescription = null,
        string $instanceavatarurl = null): void {
        if (!empty($roomname)) {
            $this->roomname = $roomname;
        }
        if (!empty($roomdescription)) {
            $this->roomdescription = $roomdescription;
        }
        $this->instanceavatarurl = $instanceavatarurl;
    }

    /**
     * Create operation for the communication api.
     *
     * @return void
     */
    public function create_room(): void {
        $this->communicationroom->create();
    }

    /**
     * Update operation for the communication api.
     *
     * @return void
     */
    public function update_room(): void {
        $this->communicationroom->update();
    }

    /**
     * Delete operation for the communication api.
     *
     * @return void
     */
    public function delete_room(): void {
        $this->communicationroom->delete();
    }

    /**
     * Add members to the room.
     *
     * @return void
     */
    public function add_members(): void {
        $this->communicationuser->add_members_to_room($this->userids);
    }

    /**
     * Remove members from room.
     *
     * @return void
     */
    public function remove_members(): void {
        $this->communicationuser->remove_members_from_room($this->userids);
    }

}
