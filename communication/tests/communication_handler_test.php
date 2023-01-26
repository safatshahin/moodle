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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/communication_test_helper_trait.php');

/**
 * Class communication_handler_test to test the communication handler and its associated methods.
 *
 * @package    core_communication
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\communication_handler
 */
class communication_handler_test extends \advanced_testcase {

    use communication_test_helper_trait;

    /**
     * Test communication providers returns the correct providers.
     *
     * @return void
     * @covers ::get_available_communication_providers
     */
    public function test_get_available_communication_providers(): void {
        $communication = new communication_handler();
        $communicationplugins = $communication->get_available_communication_providers();
        // Get the communication plugins.
        $plugins = \core_component::get_plugin_list('communication');
        // Check the number of plugins matches.
        $this->assertCount(count($plugins), $communicationplugins);
    }

    /**
     * Test the communication plugin list for the form element returns the correct number of plugins.
     *
     * @return void
     * @covers ::get_communication_plugin_list_for_form
     * @covers ::get_available_communication_providers
     */
    public function test_get_communication_plugin_list_for_form(): void {
        $communication = new communication_handler();
        $communicationplugins = $communication->get_communication_plugin_list_for_form();
        // Get the communication plugins.
        $plugins = \core_component::get_plugin_list('communication');
        // Check the number of plugins matches.
        $this->assertCount(count($plugins), $communicationplugins);
    }

    /**
     * Test set data to the instance.
     *
     * @return void
     * @covers ::set_data
     */
    public function test_set_data(): void {
        $this->resetAfterTest();
        $course = $this->get_course();
        $communication = new communication_handler($course);

        // Sample data.
        $roomname = 'Sampleroom';
        $roomdesc = 'Sampleroomtopic';
        $provider = 'communication_matrix';
        $enablecommunication = 1;

        // Set the data.
        $communication->set_data($course);

        // Test the set data.
        $this->assertEquals($roomname, $communication->instance->communicationroomname);
        $this->assertEquals($roomdesc, $communication->instance->communicationroomdesc);
        $this->assertEquals($provider, $communication->instance->selectedcommunication);
        $this->assertEquals($enablecommunication, $communication->instance->enablecommunication);
    }

    /**
     * Test save form data.
     *
     * @return void
     * @covers ::save_form_data
     */
    public function test_save_form_data(): void {
        $this->resetAfterTest();
        $course = $this->get_course();
        // Sample data.
        $course->communicationroomname = 'Sampletestroom';
        $course->communicationroomdesc = 'Sampletestroomtopic';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;

        // Handler object and save form data.
        $communication = new communication_handler($course);
        $communication->save_form_data();

        // Test the set data.
        $this->assertEquals($course->communicationroomname, $communication->communicationsettings->get_roomname());
        $this->assertEquals($course->communicationroomdesc, $communication->communicationsettings->get_room_description());
        $this->assertEquals($course->selectedcommunication, $communication->communicationsettings->get_provider());
        $this->assertEquals($course->enablecommunication, $communication->communicationsettings->get_communication_status());
    }

    /**
     * Test is update required.
     *
     * @return void
     * @covers ::is_update_required
     */
    public function test_is_update_required(): void {
        $this->resetAfterTest();
        $course = $this->get_course();
        // Sample changed data.
        $course->communicationroomname = 'Sampletestroom';
        $course->communicationroomdesc = 'Samplertestroomtopic';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;

        // Handler object and save form data.
        $communication = new communication_handler($course);
        $communication->save_form_data();

        // Returns true when data is changed.
        $this->assertTrue($communication->is_update_required());

        $course = $this->get_course();
        // Sample changed data.
        $course->communicationroomname = 'Sampleroom';
        $course->communicationroomdesc = 'Sampleroomtopic';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;

        // Handler object and save form data.
        $communication = new communication_handler($course);
        $communication->save_form_data();

        // Returns false when data is not changed.
        $this->assertFalse($communication->is_update_required());
    }

    /**
     * Test the handler create method to add/create tasks.
     *
     * @return void
     * @covers ::create
     * @covers ::add_to_task_queue
     */
    public function test_create_handler_operation(): void {
        $this->resetAfterTest();
        // Get the course by disabling communication so that we can create it manually calling the handler.
        $course = $this->get_course('Sampleroom', 'Sampleroomtopic', 'communication_matrix', 0);

        // Sample data.
        $course->communicationroomname = 'Sampleroom';
        $course->communicationroomdesc = 'Sampleroomtopic';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;

        // Handler object to create communication data.
        $communication = new communication_handler($course);
        $communication->create();

        // Test the tasks added.
        $adhoctask = \core\task\manager::get_adhoc_tasks('\\core_communication\\task\\communication_room_operations');
        $this->assertCount(1, $adhoctask);

        $adhoctask = reset($adhoctask);
        $this->assertInstanceOf('\\core_communication\\task\\communication_room_operations', $adhoctask);

        // Test the communication record added.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());
    }

    /**
     * Test update handler operation.
     *
     * @return void
     * @covers ::update
     * @covers ::is_update_required
     * @covers ::add_to_task_queue
     */
    public function test_update_handler_operation(): void {
        $this->resetAfterTest();
        $course = $this->get_course();

        // Sample data.
        $course->communicationroomname = 'Sampleroom';
        $course->communicationroomdesc = 'Sampleroomtopic';
        $course->selectedcommunication = 'communication_matrix';
        $course->enablecommunication = 1;

        // Handler object to update communication data.
        $communication = new communication_handler($course);
        $communication->update();

        // Test the tasks added.
        $adhoctask = \core\task\manager::get_adhoc_tasks('\\core_communication\\task\\communication_room_operations');
        $this->assertCount(1, $adhoctask);

        $adhoctask = reset($adhoctask);
        $this->assertInstanceOf('\\core_communication\\task\\communication_room_operations', $adhoctask);

        // Test the communication record added.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());
    }

    /**
     * Test delete handler operation.
     *
     * @return void
     * @covers ::delete
     */
    public function test_delete_handler_operation(): void {
        $this->resetAfterTest();
        $course = $this->get_course();

        // Test the communication record added.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertTrue($communicationsettingsdata->record_exist());

        // Handler object to delete communication data.
        $communication = new communication_handler($course);
        $communication->delete();

        // Test the communication record added.
        $communicationsettingsdata = new communication_settings_data($course->id, $course->component);
        $this->assertFalse($communicationsettingsdata->record_exist());
    }
}
