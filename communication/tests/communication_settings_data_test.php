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
 * Class communication_settings_data_test to test the communication data in db.
 *
 * @package    core_communication
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\communication_settings_data
 */
class communication_settings_data_test extends \advanced_testcase {

    /**
     * Test the creation of communication record.
     *
     * @return void
     * @covers ::create
     * @covers ::set_status
     * @covers ::set_provider
     * @covers ::set_roomname
     * @covers ::set_room_description
     * @covers ::get_communication_data
     * @covers ::get_communication_status
     * @covers ::get_roomname
     * @covers ::get_room_description
     * @covers ::get_provider
     * @covers ::get_communication_instance_id
     * @covers ::record_exist
     */
    public function test_create_communication_record(): void {
        global $DB;
        $this->resetAfterTest();

        // Sameple test data.
        $instanceid = 10;
        $component = 'core_course';
        $enablecommunication = 1;
        $selectedcommunication = 'communication_matrix';
        $communicationroomname = 'communicationroom';
        $communicationroomdesc = 'communicationdescription';

        // Communication settings data object.
        $communicationsettingsdata = new communication_settings_data($instanceid, $component);
        $communicationsettingsdata->set_status($enablecommunication);
        $communicationsettingsdata->set_provider($selectedcommunication);
        $communicationsettingsdata->set_roomname($communicationroomname);
        $communicationsettingsdata->set_room_description($communicationroomdesc);
        $communicationsettingsdata->create();

        // Now test the record against the database.
        $settingsdatarecord = $DB->get_record('communication', ['instanceid' => $instanceid, 'component' => $component]);

        // Test against the set data.
        $this->assertNotEmpty($settingsdatarecord);
        $this->assertEquals($instanceid, $settingsdatarecord->instanceid);
        $this->assertEquals($component, $settingsdatarecord->component);
        $this->assertEquals($enablecommunication, $settingsdatarecord->status);
        $this->assertEquals($selectedcommunication, $settingsdatarecord->provider);
        $this->assertEquals($communicationroomname, $settingsdatarecord->roomname);
        $this->assertEquals($communicationroomdesc, $settingsdatarecord->roomdesc);

        // Test against the object.
        $this->assertTrue($communicationsettingsdata->record_exist());
        $this->assertEquals($communicationsettingsdata->get_communication_instance_id(), $settingsdatarecord->id);
        $this->assertEquals($communicationsettingsdata->get_communication_status(), $settingsdatarecord->status);
        $this->assertEquals($communicationsettingsdata->get_provider(), $settingsdatarecord->provider);
        $this->assertEquals($communicationsettingsdata->get_roomname(), $settingsdatarecord->roomname);
        $this->assertEquals($communicationsettingsdata->get_room_description(), $settingsdatarecord->roomdesc);
    }

    /**
     * Test update communication record.
     *
     * @return void
     * @covers ::update
     * @covers ::set_status
     * @covers ::set_provider
     * @covers ::set_roomname
     * @covers ::set_room_description
     * @covers ::get_communication_data
     * @covers ::get_communication_status
     * @covers ::get_roomname
     * @covers ::get_room_description
     * @covers ::get_provider
     * @covers ::get_communication_instance_id
     * @covers ::record_exist
     */
    public function test_update_communication_record(): void {
        global $DB;
        $this->resetAfterTest();

        // Sameple test data.
        $instanceid = 10;
        $component = 'core_course';
        $enablecommunication = 1;
        $selectedcommunication = 'communication_matrix';
        $communicationroomname = 'communicationroom';
        $communicationroomdesc = 'communicationdescription';

        // Communication settings data object.
        $communicationsettingsdata = new communication_settings_data($instanceid, $component);
        $communicationsettingsdata->set_status($enablecommunication);
        $communicationsettingsdata->set_provider($selectedcommunication);
        $communicationsettingsdata->set_roomname($communicationroomname);
        $communicationsettingsdata->set_room_description($communicationroomdesc);
        $communicationsettingsdata->create();

        // Now update the record.
        $communicationroomname = 'communicationroomupdated';
        $communicationroomdesc = 'communicationdescriptionupdated';

        // Update the object and data.
        $communicationsettingsdata->set_roomname($communicationroomname);
        $communicationsettingsdata->set_room_description($communicationroomdesc);
        $communicationsettingsdata->update();

        // Now test the record against the database.
        $settingsdatarecord = $DB->get_record('communication', ['instanceid' => $instanceid, 'component' => $component]);

        // Test against the set data.
        $this->assertNotEmpty($settingsdatarecord);
        $this->assertEquals($instanceid, $settingsdatarecord->instanceid);
        $this->assertEquals($component, $settingsdatarecord->component);
        $this->assertEquals($enablecommunication, $settingsdatarecord->status);
        $this->assertEquals($selectedcommunication, $settingsdatarecord->provider);
        $this->assertEquals($communicationroomname, $settingsdatarecord->roomname);
        $this->assertEquals($communicationroomdesc, $settingsdatarecord->roomdesc);

        // Test against the object.
        $this->assertTrue($communicationsettingsdata->record_exist());
        $this->assertEquals($communicationsettingsdata->get_communication_instance_id(), $settingsdatarecord->id);
        $this->assertEquals($communicationsettingsdata->get_communication_status(), $settingsdatarecord->status);
        $this->assertEquals($communicationsettingsdata->get_provider(), $settingsdatarecord->provider);
        $this->assertEquals($communicationsettingsdata->get_roomname(), $settingsdatarecord->roomname);
        $this->assertEquals($communicationsettingsdata->get_room_description(), $settingsdatarecord->roomdesc);
    }

    /**
     * Test delete communication record.
     *
     * @return void
     * @covers ::delete
     * @covers ::set_status
     * @covers ::set_provider
     * @covers ::set_roomname
     * @covers ::set_room_description
     * @covers ::get_communication_data
     * @covers ::get_communication_status
     * @covers ::get_roomname
     * @covers ::get_room_description
     * @covers ::get_provider
     * @covers ::get_communication_instance_id
     * @covers ::record_exist
     */
    public function test_delete_communication_record(): void {
        global $DB;
        $this->resetAfterTest();

        // Sameple test data.
        $instanceid = 10;
        $component = 'core_course';
        $enablecommunication = 1;
        $selectedcommunication = 'communication_matrix';
        $communicationroomname = 'communicationroom';
        $communicationroomdesc = 'communicationdescription';

        // Communication settings data object.
        $communicationsettingsdata = new communication_settings_data($instanceid, $component);
        $communicationsettingsdata->set_status($enablecommunication);
        $communicationsettingsdata->set_provider($selectedcommunication);
        $communicationsettingsdata->set_roomname($communicationroomname);
        $communicationsettingsdata->set_room_description($communicationroomdesc);
        $communicationsettingsdata->create();

        // Now test the record against the database.
        $settingsdatarecord = $DB->get_record('communication', ['instanceid' => $instanceid, 'component' => $component]);

        // Test against the set data.
        $this->assertNotEmpty($settingsdatarecord);
        $this->assertTrue($communicationsettingsdata->record_exist());

        // Now delete the record.
        $communicationsettingsdata->delete();

        // Now test the record against the database.
        $settingsdatarecord = $DB->get_record('communication', ['instanceid' => $instanceid, 'component' => $component]);

        // Test against the set data.
        $this->assertEmpty($settingsdatarecord);
        // Test against the object.
        $this->assertFalse($communicationsettingsdata->record_exist());
    }
}
