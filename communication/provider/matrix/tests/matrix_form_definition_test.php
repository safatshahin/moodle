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

use core_communication\communication_settings_data;
use core_communication\communication_test_helper_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/matrix_test_helper_trait.php');
require_once(__DIR__ . '/../../../tests/communication_test_helper_trait.php');

/**
 * Class matrix_form_definition_test to test the matrix custom form elements.
 *
 * @package    communication_matrix
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \communication_matrix\matrix_form_definition
 * @coversDefaultClass \core_communication\communication_handler
 */
class matrix_form_definition_test extends \advanced_testcase {

    use matrix_test_helper_trait;
    use communication_test_helper_trait;

    /**
     * Test save form data options.
     *
     * @return void
     * @covers ::save_form_data
     */
    public function test_save_form_data(): void {
        $this->resetAfterTest();
        $course = $this->get_course();

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, 'core_course', 'coursecommunication');
        $this->assertTrue($communicationsettingsdata->record_exist());

        $course->matrixroomtopic = 'Sampletopicupdated';
        matrix_form_definition::save_form_data($course, $communicationsettingsdata->get_communication_instance_id());

        // Test the updated topic.
        $matrixroomdata = new matrix_rooms($communicationsettingsdata->get_communication_instance_id());
        $this->assertEquals('Sampletopicupdated', $matrixroomdata->topic);
    }

    /**
     * Test set form data for matrix form fields.
     *
     * @return void
     * @covers ::set_form_data
     */
    public function test_set_form_data(): void {
        $this->resetAfterTest();
        $course = $this->get_course();

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, 'core_course', 'coursecommunication');
        $this->assertTrue($communicationsettingsdata->record_exist());

        // Set the custom data from matrix plugin.
        matrix_form_definition::set_form_data($course, $communicationsettingsdata->get_communication_instance_id());
        $this->assertNotNull($course->matrixroomtopic);
    }

}
