<?php

namespace core_communication\tests;

/**
 * Trait communication_test_helper_trait to generate initial setup for communication providers.
 *
 * @package    communication_matrix
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