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

namespace communication_matrix\tests;

use communication_matrix\matrix_room_manager;
use communication_matrix\matrix_rooms;
use core_communication\communication;
use core_communication\communication_room_base;
use core_communication\communication_settings_data;
use core_communication\tests\communication_test_helper_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/matrix_test_helper_trait.php');
require_once(__DIR__ . '/../../../tests/communication_test_helper_trait.php');

/**
 * Class matrix_events_manager_test to test the matrix events endpoint.
 *
 * @package    communication_matrix
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \communication_matrix\matrix_room_manager
 */
class matrix_room_manager_test extends \advanced_testcase {

    use matrix_test_helper_trait;
    use communication_test_helper_trait;

    /**
     * @var communication_room_base|matrix_room_manager $matrixroommanager Matrix room manager object
     */
    protected communication_room_base|matrix_room_manager $matrixroommanager;

    /**
     * @var communication $communication The communication object
     */
    protected communication $communication;

    /**
     * @var communication_settings_data $communicationdata The communication settings data object
     */
    protected communication_settings_data $communicationdata;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->initialise_mock_server();
        $this->course = $this->get_course();
        $this->communicationdata = new communication_settings_data($this->course->id, 'core_course');
        $this->communication = new communication($this->communicationdata->get_provider(),
            $this->communicationdata->get_communication_instance_id());
        $this->matrixroommanager = new matrix_room_manager($this->communication);
    }

    /**
     * Test create room.
     *
     * @return void
     * @covers ::create
     * @covers ::init
     */
    public function test_create(): void {
        global $CFG;
        $avatarurl = $CFG->dirroot . '/communication/provider/matrix/tests/fixtures/moodle_logo.jpg';
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description(), $avatarurl);
        $this->matrixroommanager->create();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Test the response against the stored data.
        $this->assertNotEmpty($matrixrooms->get_roomid());
        $this->assertNotEmpty($matrixrooms->get_room_alias());

        // Add api call to get room data and test against set data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($matrixrooms->get_roomid(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($this->communicationdata->get_roomname(), $matrixroomdata->name);
        $this->assertEquals($this->communicationdata->get_room_description(), $matrixroomdata->topic);
        $this->assertNotEmpty($matrixroomdata->avatar);

    }

    /**
     * Test update room.
     *
     * @return void
     * @covers ::update
     * @covers ::update_room_topic
     * @covers ::update_room_name
     * @covers ::update_room_avatar
     */
    public function test_update(): void{
        global $CFG;
        // First create the communication objects and data.
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description());
        $this->matrixroommanager->create();

        // Now update with custom data.
        $newroomname = 'Newsampleroomname';
        $newroomdesc = 'Newroomdescription';

        $avatarurl = $CFG->dirroot . '/communication/provider/matrix/tests/fixtures/moodle_logo.jpg';
        $this->communication->set_room_options($newroomname, $newroomdesc, $avatarurl);
        $this->matrixroommanager->update();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Test the response against the stored data.
        $this->assertNotEmpty($matrixrooms->get_roomid());
        $this->assertNotEmpty($matrixrooms->get_room_alias());

        // Add api call to get room data and test against set data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($matrixrooms->get_roomid(), $matrixroomdata->room_id);
        $this->assertEquals($matrixrooms->get_room_alias(), $matrixroomdata->canonical_alias);
        $this->assertEquals($newroomname, $matrixroomdata->name);
        $this->assertEquals($newroomdesc, $matrixroomdata->topic);
        $this->assertNotEmpty($matrixroomdata->avatar);
    }

    /**
     * Test update room name.
     *
     * @return void
     * @covers ::update_room_name
     */
    public function test_update_room_name(): void {
        // First create the communication objects and data.
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description());
        $this->matrixroommanager->create();

        // Now update the room name.
        $newroomname = 'Newsampleroomnameupdate';
        $this->communication->set_room_options($newroomname, $this->communicationdata->get_room_description());
        $this->matrixroommanager->update_room_name();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Add api call to get room data and test against set data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($newroomname, $matrixroomdata->name);
    }

    /**
     * Test update room topic.
     *
     * @return void
     * @covers ::update_room_topic
     */
    public function test_update_room_topic(): void {
        // First create the communication objects and data.
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description());
        $this->matrixroommanager->create();

        // Now update the room topic.
        $newroomdesc = 'Newsampleroomtopicupdate';
        $this->communication->set_room_options($this->communicationdata->get_provider(), $newroomdesc);
        $this->matrixroommanager->update_room_topic();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Add api call to get room data and test against set data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertEquals($newroomdesc, $matrixroomdata->topic);
    }

    /**
     * Test update room avatar.
     *
     * @return void
     * @covers ::update_room_avatar
     */
    public function test_update_room_avatar(): void {
        global $CFG;
        // First create the communication objects and data.
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description());
        $this->matrixroommanager->create();

        // Now update the room avatar.
        $avatarurl = $CFG->dirroot . '/communication/provider/matrix/tests/fixtures/moodle_logo.jpg';
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description(), $avatarurl);
        $this->matrixroommanager->update_room_avatar();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Add api call to get room data and test against set data.
        $matrixroomdata = $this->get_matrix_room_data($matrixrooms->get_roomid());
        $this->assertNotEmpty($matrixroomdata->avatar);
    }

    /**
     * Test delete room.
     * Deleting room won't delete anything from matrix. Will just remove users and delete local records.
     *
     * @return void
     * @covers ::delete
     */
    public function test_delete(): void {
        // First create the communication objects and data.
        $this->communication->set_room_options($this->communicationdata->get_roomname(),
            $this->communicationdata->get_room_description());
        $this->matrixroommanager->create();

        // Now delete.
        $this->matrixroommanager->delete();
        $matrixrooms = new matrix_rooms($this->communication->get_communication_instance_id());

        // Test the response against the stored data.
        $this->assertNull($matrixrooms->get_roomid());
        $this->assertNull($matrixrooms->get_room_alias());
    }
}
