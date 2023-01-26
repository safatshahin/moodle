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
 * Trait communication_test_helper_trait to generate initial setup for communication providers.
 *
 * @package    core_communication
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait communication_test_helper_trait {

    /**
     * Get or create course if it does not exist
     *
     * @param string $roomname The room name for the communication api
     * @param string $roomdesc The room description for communication api
     * @param string $provider The selected provider
     * @param int $enablecommunication The communication is enabled or not
     * @return \stdClass
     */
    protected function get_course(string $roomname = 'Sampleroom', string $roomdesc = 'Sampleroomtopic',
        string $provider = 'communication_matrix', int $enablecommunication = 1): \stdClass {

        $records = [
            'enablecommunication' => $enablecommunication,
            'selectedcommunication' => $provider,
            'communicationroomname' => $roomname,
            'communicationroomdesc' => $roomdesc,
        ];

        $course = $this->getDataGenerator()->create_course($records);
        $course->component = 'core_course';

        return $course;
    }
}
