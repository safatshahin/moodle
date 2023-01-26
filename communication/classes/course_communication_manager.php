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

use core\task\adhoc_task;
use core_communication\task\communication_room_operations;

/**
 * Class course_communication_manager to manage the provider communication for course.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_communication_manager {

    /**
     * @var \stdClass $instance The instance object
     */
    private \stdClass $instance;

    /**
     * @var communication_settings_data $communicationsettings The communication settings object
     */
    private communication_settings_data $communicationsettings;

    /**
     * Communication handler constructor to manage and handle all communication related actions.
     *
     * This class is the entrypoint for all kinda usages.
     *
     * @param \stdClass|null $instance The instance object
     */
    public function __construct(\stdClass $instance = null) {
        if ($instance !== null) {
            $this->instance = $instance;
        }
        if (!empty($this->instance->id)) {
            $this->communicationsettings = new communication_settings_data($instance->id, $instance->component);
        }
    }

    /**
     * Get the available communication providers.
     * It will only supply the enabled ones and also the ones implementing the plugin entrypoint.
     *
     * @return array
     */
    public function get_available_communication_providers(): array {
        $plugintype = 'communication';
        $plugins = \core_component::get_plugin_list($plugintype);
        foreach ($plugins as $pluginname => $plugin) {
            if (!\core\plugininfo\communication::is_plugin_enabled($plugintype . '_' . $pluginname)) {
                unset($plugins[$pluginname]);
            }
        }
        return $plugins;
    }

    /**
     * Get the list of plugins for form selection.
     *
     * @return array
     */
    public function get_communication_plugin_list_for_form(): array {
        $selection = [];
        $communicationplugins = $this->get_available_communication_providers();
        foreach ($communicationplugins as $pluginname => $notusing) {
            $selection['communication_' . $pluginname] = get_string('pluginname', 'communication_'. $pluginname);
        }
        return $selection;
    }

    /**
     * Define the form elements for the communication api.
     *
     * @param \MoodleQuickForm $mform The form element
     * @return void
     */
    public function form_definition(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'communication', get_string('communication', 'communication'));
        $mform->addElement('selectyesno', 'enablecommunication',
            get_string('enablecommunication', 'communication'));
        $mform->setDefault('enablecommunication', get_config('communication_matrix', 'matrixcreateroom'));
        $mform->addHelpButton('enablecommunication', 'enablecommunication', 'communication');

        // List the communication providers if enabled/selected yes.
        $communicationproviders = $this->get_communication_plugin_list_for_form();
        $mform->addElement('select', 'selectedcommunication',
            get_string('seleccommunicationprovider', 'communication'), $communicationproviders);
        $mform->hideIf('selectedcommunication', 'enablecommunication', 'eq', 0);

        // Room name for the communication provider.
        $mform->addElement('text', 'communicationroomname',
            get_string('communicationroomname', 'communication'), 'maxlength="100" size="20"');
        $mform->addHelpButton('communicationroomname', 'communicationroomname', 'communication');
        $mform->setType('communicationroomname', PARAM_TEXT);
        $mform->hideIf('communicationroomname', 'enablecommunication', 'eq', 0);

        // Room description for the communication provider.
        $mform->addElement('text', 'communicationroomdesc',
            get_string('communicationroomdesc', 'communication'), 'maxlength="255" size="20"');
        $mform->addHelpButton('communicationroomdesc', 'communicationroomdesc', 'communication');
        $mform->setType('communicationroomdesc', PARAM_TEXT);
        $mform->hideIf('communicationroomdesc', 'enablecommunication', 'eq', 0);
    }

    /**
     * Set the form data.
     *
     * @param \stdClass $instance The instance object
     * @return void
     */
    public function set_data(\stdClass $instance): void {
        if (!empty($instance->id)) {
            $instance->enablecommunication = $this->communicationsettings->get_communication_status();
            $instance->selectedcommunication = $this->communicationsettings->get_provider();
            $instance->communicationroomname = $this->communicationsettings->get_roomname();
            $instance->communicationroomdesc = $this->communicationsettings->get_room_description();
        }
    }

    /**
     * Save the data from the form.
     *
     * @return void
     */
    public function save_form_data(): void {
        if (!empty($this->instance->id)) {

            $this->communicationsettings->set_status($this->instance->enablecommunication);
            $this->communicationsettings->set_provider($this->instance->selectedcommunication);

            if (!empty($this->instance->communicationroomname)) {
                $this->communicationsettings->set_roomname($this->instance->communicationroomname);
            }

            if (!empty($this->instance->communicationroomdesc)) {
                $this->communicationsettings->set_room_description($this->instance->communicationroomdesc);
            }
        }
    }

    /**
     * Add the task to ad-hoc queue.
     *
     * @param adhoc_task $task The task to be added to the queue
     * @return void
     */
    public function add_to_task_queue(adhoc_task $task): void {
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Create a communication ad-hoc task for create operation.
     *
     * @return void
     */
    public function create(): void {
        $this->save_form_data();
        $this->communicationsettings->create();

        // Add ad-hoc task to create the provider room.
        $createroom = new communication_room_operations();
        $createroom->set_custom_data(
            [
                'provider' => $this->communicationsettings->get_provider(),
                'instanceid' => $this->instance->id,
                'roomname' => $this->communicationsettings->get_roomname(),
                'roomdesc' => $this->communicationsettings->get_room_description(),
                'operation' => 'create_room',
            ]
        );
        // Queue the task for the next run.
        $this->add_to_task_queue($createroom);

        // TODO MDL-76708 add adhoc task to add all the users to the to the room.
    }

    /**
     * Create a communication ad-hoc task for update operation.
     *
     * @return void
     */
    public function update(): void {
        if ($this->communicationsettings->record_exist() && $this->communicationsettings->get_communication_status()) {
            $this->save_form_data();
            $this->communicationsettings->update();

            // Add ad-hoc task to update the provider room.
            $updateroom = new communication_room_operations();
            $updateroom->set_custom_data(
                [
                    'provider' => $this->communicationsettings->get_provider(),
                    'instanceid' => $this->instance->id,
                    'roomname' => $this->communicationsettings->get_roomname(),
                    'roomdesc' => $this->communicationsettings->get_room_description(),
                    'operation' => 'update_room',
                ]
            );
            // Queue the task for the next run.
            $this->add_to_task_queue($updateroom);

            // TODO MDL-76708 add ad-hoc task to remove all the users from the room if the provider is changed or disabled.
        }
    }

    /**
     * Create a communication ad-hoc task for delete operation.
     *
     * @return void
     */
    public function delete(): void {
        // TODO MDL-76708 add ad-hoc task to remove all the users from the room.

        // Remove the room data from the communication table.
        if ($this->communicationsettings->record_exist()) {
            $this->communicationsettings->delete();
        }

    }

}
