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
use core_communication\task\communication_user_operations;

/**
 * Class communication_handler to manage the provider communication objects and actions for apis using core_communication.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_handler {

    /**
     * @var communication_settings_data $communicationsettings The communication settings object
     */
    private communication_settings_data $communicationsettings;

    /**
     * @var string|null $avatarurl The url of the avatar for the instance
     */
    private ?string $avatarurl;

    /**
     * Communication handler constructor to manage and handle all communication related actions.
     *
     * This class is the entrypoint for all kinda usages.
     *
     * @param int $instanceid The id of the instance
     * @param string|null $avatarurl The url of the avatar of the instance
     * @param string $instancetype The type of the item for the instance
     * @param string $component The component of the item for the instance
     *
     */
    public function __construct(int $instanceid, string $avatarurl = null, string $instancetype = 'coursecommunication',
            string $component = 'core_course') {
        $this->communicationsettings = new communication_settings_data($instanceid, $component, $instancetype);
        $this->avatarurl = $avatarurl;
    }

    /**
     * Get the list of plugins for form selection.
     *
     * @return array
     */
    public static function get_communication_plugin_list_for_form(): array {
        // Add the option to have communication disabled.
        $selection['none'] = get_string('nocommunicationselected', 'communication');
        $communicationplugins = \core\plugininfo\communication::get_enabled_plugins();
        foreach ($communicationplugins as $pluginname => $notusing) {
            $selection['communication_' . $pluginname] = get_string('pluginname', 'communication_'. $pluginname);
        }
        return $selection;
    }

    /**
     * Define the form elements for the communication api.
     *
     * @param \MoodleQuickForm $mform The form element
     * @param \stdClass $instance The actual instance object
     * @param string $selectdefaultcommunication The default selected communication provider in the form field
     * @return void
     */
    public function form_definition(\MoodleQuickForm $mform, \stdClass $instance,
            string $selectdefaultcommunication = 'none'): void {

        global $PAGE;
        $PAGE->requires->js_call_amd('core_communication/communicationchooser', 'init');

        $mform->addElement('header', 'communication', get_string('communication', 'communication'));

        // List the communication providers.
        $communicationproviders = self::get_communication_plugin_list_for_form();
        $mform->addElement('select', 'selectedcommunication',
                get_string('seleccommunicationprovider', 'communication'),
                $communicationproviders, ['data-communicationchooser-field' => 'selector']);
        $mform->addHelpButton('selectedcommunication', 'seleccommunicationprovider', 'communication');
        $mform->setDefault('selectedcommunication', $selectdefaultcommunication);

        $mform->registerNoSubmitButton('updatecommunicationprovider');
        $mform->addElement('submit', 'updatecommunicationprovider', 'update communication', [
            'data-communicationchooser-field' => 'updateButton',
            'class' => 'd-none',
        ]);

        // Just a placeholder for the communication options.
        $mform->addElement('hidden', 'addcommunicationoptionshere');
        $mform->setType('addcommunicationoptionshere', PARAM_BOOL);

        $this->set_data($instance);
    }

    /**
     * Set the form definitions for the plugins.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public function form_definition_for_provider_plugins(\MoodleQuickForm $mform): void {
        $provider = $mform->getElementValue('selectedcommunication');

        if ($provider[0] !== 'none') {
            // Room name for the communication provider.
            $mform->insertElementBefore($mform->createElement('text', 'communicationroomname',
                    get_string('communicationroomname', 'communication'), 'maxlength="100" size="20"'),
                    'addcommunicationoptionshere');
            $mform->addHelpButton('communicationroomname', 'communicationroomname', 'communication');
            $mform->setType('communicationroomname', PARAM_TEXT);

            $providerformobject = $this->get_provider_form_definition($provider[0]);
            if (($providerformobject !== null) && method_exists($providerformobject, 'set_form_definition')) {
                $providerformobject::set_form_definition($mform);
            }
        }

    }

    /**
     * Get the form definition object from the provider.
     *
     * @return null|communication_form_base
     */
    public function get_provider_form_definition(string $provider): ?communication_form_base {
        $plugins = helper::get_communication_providers_implementing_features();
        $pluginnames = array_keys($plugins);

        if (in_array($provider, $pluginnames, true)) {
            $pluginentrypoint = new $plugins[$provider] ();
            $communicationform = $pluginentrypoint->get_provider_form_definition();

            if (!empty($communicationform)) {
                return $communicationform;
            }
        }
        return null;
    }

    /**
     * Set the form data if the data is already available.
     *
     * @param \stdClass $instance The instance object
     * @return void
     */
    public function set_data(\stdClass $instance): void {
        if (!empty($instance->id) && !empty($this->communicationsettings)) {

            $instance->selectedcommunication = $this->communicationsettings->get_provider();
            $instance->communicationroomname = $this->communicationsettings->get_room_name();

            // Now set the data from the plugins if available.
            $providerformobject = $this->get_provider_form_definition($instance->selectedcommunication);
            if (($providerformobject !== null) && method_exists($providerformobject, 'set_form_data')) {
                $providerformobject::set_form_data($instance, $this->communicationsettings->get_communication_instance_id());
            }
        }
    }

    /**
     * Save the data from the form or set data.
     *
     * @param string $selectedcommunication The selected communication provider
     * @param string $communicationroomname The communication room name
     * @return void
     */
    public function save_form_data(string $selectedcommunication, string $communicationroomname): void {
        if ($selectedcommunication === 'none') {
            $this->communicationsettings->disableprovider = $this->communicationsettings->provider;
        }
        $this->communicationsettings->provider = $selectedcommunication;
        $this->communicationsettings->roomname = $communicationroomname;
    }

    /**
     * Process the form data for provider plugins and convert to json.
     *
     * @param \stdClass $instance The actual instance object
     * @param string $selectedcommunication The selected communication provider
     * @return void
     */
    public function process_form_data_for_provider_plugins(\stdClass $instance, string $selectedcommunication): void {
        $providerformobject = $this->get_provider_form_definition($selectedcommunication);

        if ($providerformobject !== null && method_exists($providerformobject, 'save_form_data')) {
            // Save the form data for the communication plugins.
            $providerformobject::save_form_data($instance, $this->communicationsettings->get_communication_instance_id());
        }
    }

    /**
     * Check if update required according to the updated data.
     * This method will add efficiency while adding task to send requests etc.
     * Without this method, everytime and instance is saved, it will add a new task.
     *
     * @return bool
     */
    public function is_update_required(): bool {
        return $this->communicationsettings->provider !== 'none';
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
     * @param string $selectedcommunication The selected communication provider
     * @param string $communicationroomname The communication room name
     * @param \stdClass $instance The actual instance object
     * @return void
     */
    public function create_and_configure_room_and_add_members(string $selectedcommunication, string $communicationroomname,
            \stdClass $instance): void {

        if ($selectedcommunication !== 'none' && $selectedcommunication !== '') {
            // Update communication record.
            $this->save_form_data($selectedcommunication, $communicationroomname);

            $this->communicationsettings->save();

            // Now get the form data option from the plugins if available.
            $this->process_form_data_for_provider_plugins($instance, $this->communicationsettings->provider);

            // Add ad-hoc task to create the provider room.
            $createroom = new communication_room_operations();
            $createroom->set_custom_data(
                [
                    'instanceid' => $this->communicationsettings->instanceid,
                    'component' => $this->communicationsettings->component,
                    'instancetype' => $this->communicationsettings->instancetype,
                    'avatarurl' => $this->avatarurl,
                    'operation' => 'create_room',
                ]
            );
            // Queue the task for the next run.
            $this->add_to_task_queue($createroom);
        }
    }

    /**
     * Create a communication ad-hoc task for update operation.
     *
     * @param string $selectedcommunication The selected communication provider
     * @param string $communicationroomname The communication room name
     * @param \stdClass $instance The actual instance object
     * @return void
     */
    public function update_room_and_membership(string $selectedcommunication, string $communicationroomname,
            \stdClass $instance): void {
        if ($this->communicationsettings->record_exist()) {
            // Update communication record.
            $this->save_form_data($selectedcommunication, $communicationroomname);

            $this->communicationsettings->save();

            // Now get the form data option from the plugins if available.
            $this->process_form_data_for_provider_plugins($instance, $selectedcommunication);

            if ($this->is_update_required()) {
                // Add ad-hoc task to update the provider room.
                $updateroom = new communication_room_operations();
                $updateroom->set_custom_data(
                    [
                        'instanceid' => $this->communicationsettings->instanceid,
                        'component' => $this->communicationsettings->component,
                        'instancetype' => $this->communicationsettings->instancetype,
                        'avatarurl' => $this->avatarurl,
                        'operation' => 'update_room',
                    ]
                );
                // Queue the task for the next run.
                $this->add_to_task_queue($updateroom);
            }
        } else {
            $this->create_and_configure_room_and_add_members($selectedcommunication, $communicationroomname, $instance);
        }
    }

    /**
     * Create a communication ad-hoc task for delete operation.
     *
     * @return void
     */
    public function delete_room_and_remove_members(): void {
        // Remove the room data from the communication table.
        if ($this->communicationsettings->record_exist()) {
            // Add ad-hoc task to delete the provider room.
            $deleteroom = new communication_room_operations();
            $deleteroom->set_custom_data(
                [
                    'instanceid' => $this->communicationsettings->instanceid,
                    'component' => $this->communicationsettings->component,
                    'instancetype' => $this->communicationsettings->instancetype,
                    'avatarurl' => $this->avatarurl,
                    'operation' => 'delete_room',
                ]
            );
            // Queue the task for the next run.
            $this->add_to_task_queue($deleteroom);
        }
    }

    /**
     * Update room membership for a user.
     *
     * @param string $action The action to perform
     * @param array $userids The user ids to update
     * @param bool $async Run task asyncronously, or not
     * @return void
     */
    public function update_room_membership(string $action, array $userids, $async = true): void {

        if ($this->communicationsettings->record_exist()) {

            $data = [
                'instanceid' => $this->communicationsettings->instanceid,
                'component' => $this->communicationsettings->component,
                'instancetype' => $this->communicationsettings->instancetype,
                'disableprovider' => $this->communicationsettings->disableprovider,
                'userids' => $userids,
            ];

            switch ($action) {
                case 'add':
                    $data['operation'] = 'add_members';
                    break;

                case 'remove':
                    $data['operation'] = 'remove_members';
                    break;
            }
            // Add ad-hoc task to update room membership.
            $updatemembership = new communication_user_operations();
            $updatemembership->set_custom_data($data);

            if ($async) {
                // Queue the task for the next run.
                $this->add_to_task_queue($updatemembership);
            } else {
                // Run immidiately.
                $updatemembership->execute($data);
            }
        }
    }

}
