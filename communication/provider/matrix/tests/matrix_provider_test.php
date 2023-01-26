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

use core_communication\communication_handler;
use core_communication\communication_settings_data;
use core_communication\communication_test_helper_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/matrix_test_helper_trait.php');
require_once(__DIR__ . '/../../../tests/communication_test_helper_trait.php');

/**
 * Class matrix_provider_test to test the matrix provider scenarios using the matrix endpoints.
 *
 * @package    communication_matrix
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\communication
 * @coversDefaultClass \core_communication\task\communication_room_operations
 * @coversDefaultClass \core_communication\communication_room_base
 * @coversDefaultClass \communication_matrix\matrix_room_manager
 */
class matrix_provider_test extends \advanced_testcase {

    use matrix_test_helper_trait;
    use communication_test_helper_trait;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->initialise_mock_server();
    }

    /**
     * Test creating course with matrix provider creates all the associated data and matrix room.
     *
     * @return void
     * @covers ::create_room
     * @covers ::set_room_options
     * @covers ::execute
     */
    public function test_create_course_with_matrix_provider(): void {
        // Sample data.
        $roomname = 'Samplematrixroom';
        $roomdesc = 'Samplematrixroomtopic';
        $provider = 'communication_matrix';
        $enablecommunication = 1;
        $course = $this->get_course($roomname, $roomdesc, $provider, $enablecommunication);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\communication_room_operations');

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationsettingsdata->get_communication_instance_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($matrixrooms->get_roomid(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($roomname, $matrixroomdata->name);
        $this->assertEquals($roomdesc, $matrixroomdata->topic);
    }

    /**
     * Test update course with matrix provider.
     *
     * @return void
     * @covers ::update_room
     * @covers ::set_room_options
     */
    public function test_update_course_with_matrix_provider(): void {
        global $CFG;
        $course = $this->get_course();

        // Sample data.
        $course->communicationroomname = 'Sampleroomupdated';
        $course->communicationroomdesc = 'Sampleroomtopicupdated';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;
        $course->avatarurl = $CFG->dirroot . '/communication/provider/matrix/tests/fixtures/moodle_logo.jpg';
        $course->communicationupdateavatar = 1;

        // Handler object to update communication data.
        $communication = new communication_handler($course);
        $communication->update();

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\communication_room_operations');

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationsettingsdata->get_communication_instance_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($matrixrooms->get_roomid(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($course->communicationroomname, $matrixroomdata->name);
        $this->assertEquals($course->communicationroomdesc, $matrixroomdata->topic);
        $this->assertNotEmpty($matrixroomdata->avatar);
    }

    /**
     * Test course delete with matrix provider.
     *
     * @return void
     * @covers ::delete_room
     * @covers ::set_room_options
     * @covers ::execute
     */
    public function test_delete_course_with_matrix_provider(): void {
        global $DB;
        // Sample data.
        $roomname = 'Samplematrixroom';
        $roomdesc = 'Samplematrixroomtopic';
        $provider = 'communication_matrix';
        $enablecommunication = 1;
        $course = $this->get_course($roomname, $roomdesc, $provider, $enablecommunication);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\communication_room_operations');

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());
        $communicationid = $communicationsettingsdata->get_communication_instance_id();

        // Initialize the matrix room object.
        $matrixrooms = new matrix_rooms($communicationsettingsdata->get_communication_instance_id());

        // Test against the data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($matrixrooms->get_roomid(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_room_alias(), $matrixroomdata->canonical_alias);

        // Now delete the course.
        delete_course($course->id, false);

        // Run the task.
        $this->runAdhocTasks('\core_communication\task\communication_room_operations');

        // Get the communication id.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertFalse($communicationsettingsdata->record_exist());

        // Initialize the matrix room object.
        $matrixrooms = $DB->get_record('matrix_rooms', ['commid' => $communicationid]);
        $this->assertEmpty($matrixrooms);
    }

}
