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
 * Interface synchronise_provider to check if the users and room is synced properly for the communication provider.
 *
 * This is used to ensure that the users and room are in sync with the communication provider.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface synchronise_provider {

    /**
     * Ensure the users are in sync with the communication provider.
     *
     * @param array $userids The user ids to be checked
     */
    public function ensure_synchronised_room_members(array $userids): void;

    /**
     * Ensure the room is in sync with the communication provider.
     */
    public function ensure_synchronised_room_info(): void;
}
