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
 * Class communication_settings_data to manage the communication settings data in db.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_settings_data {

    /**
     * @var string $provider The communication provider
     */
    protected string $provider;

    /**
     * @var string $roomname The room of the room
     */
    protected string $roomname = '';

    /**
     * @var string $roomdesc The description of the room
     */
    protected string $roomdesc = '';

    /**
     * @var int $enabled If the communication is enabled or not
     */
    protected int $enabled;

    /**
     * @var bool $recordexist The record available or not
     */
    protected bool $recordexist = false;

    /**
     * @var int $commid The id of the communication instance
     */
    protected int $commid = 0;

    /**
     * Communication data constructor to load the communication information from communication table.
     *
     * @param int $instanceid The id of the instance
     * @param string $component The component of the instance
     */
    public function __construct(int $instanceid, string $component) {
        $this->instanceid = $instanceid;
        $this->component = $component;
        if ($commrecord = $this->get_communication_data()) {
            $this->commid = $commrecord->id;
            $this->enabled = $commrecord->status;
            $this->provider = $commrecord->provider;
            $this->roomname = $commrecord->roomname;
            $this->roomdesc = $commrecord->roomdesc;
        }
    }

    /**
     * Get the communication data from database. Either get the data object or return false if no data found.
     *
     * @return \stdClass|bool
     */
    public function get_communication_data(): bool|\stdClass {
        global $DB;
        $record = $DB->get_record('communication', ['instanceid' => $this->instanceid, 'component' => $this->component]);
        if ($record) {
            $this->recordexist = true;
        }
        return $record;
    }

    /**
     * Get communication instance id after creating the instance in communication table.
     *
     * @return int
     */
    public function get_communication_instance_id(): int {
        return $this->commid;
    }

    /**
     * Get communication provider.
     *
     * @return string|null
     */
    public function get_provider(): ?string {
        return $this->provider;
    }

    /**
     * Get room name.
     *
     * @return string|null
     */
    public function get_roomname(): ?string {
        return $this->roomname;
    }

    /**
     * Get room description.
     *
     * @return string|null
     */
    public function get_room_description(): ?string {
        return $this->roomdesc;
    }

    /**
     * Get communication status, enabled or disabled.
     *
     * @return int
     */
    public function get_communication_status(): int {
        return $this->enabled;
    }

    /**
     * Set communication status.
     *
     * @param int $status Enable or disable communication
     * @return void
     */
    public function set_status(int $status): void {
        $this->enabled = $status;
    }

    /**
     * Set communication provider.
     *
     * @param string $provider The name of the provider
     * @return void
     */
    public function set_provider(string $provider): void {
        $this->provider = $provider;
    }

    /**
     * Set room name.
     *
     * @param string $roomname The name of the room
     * @return void
     */
    public function set_roomname(string $roomname): void {
        $this->roomname = $roomname;
    }

    /**
     * Set room description.
     *
     * @param string $roomdescription The description of the room
     * @return void
     */
    public function set_room_description(string $roomdescription): void {
        $this->roomdesc = $roomdescription;
    }

    /**
     * Create coruse communication data.
     *
     * @return void
     */
    public function create(): void {
        global $DB;
        $commrecord = new \stdClass();
        $commrecord->instanceid = $this->instanceid;
        $commrecord->component = $this->component;
        $commrecord->status = $this->enabled;
        $commrecord->provider = $this->provider;
        $commrecord->roomname = $this->roomname;
        $commrecord->roomdesc = $this->roomdesc;
        $this->commid = $DB->insert_record('communication', $commrecord);
        $this->recordexist = true;
    }

    /**
     * Update communication data.
     *
     * @return void
     */
    public function update(): void {
        global $DB;
        if ($commrecord = $this->get_communication_data()) {
            $commrecord->status = $this->enabled;
            $commrecord->provider = $this->provider;
            $commrecord->roomname = $this->roomname;
            $commrecord->roomdesc = $this->roomdesc;
            $DB->update_record('communication', $commrecord);
        }
    }

    /**
     * Delete communication data.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;
        $DB->delete_records('communication', ['instanceid' => $this->instanceid, 'component' => $this->component]);
        $this->recordexist = false;
    }

    /**
     * Check if the record for communication exist or not.
     *
     * @return bool
     */
    public function record_exist(): bool {
        return $this->recordexist;
    }

}
