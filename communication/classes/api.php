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

use core_communication\task\add_members_to_room_task;
use core_communication\task\create_and_configure_room_task;
use core_communication\task\delete_room_task;
use core_communication\task\remove_members_from_room;
use core_communication\task\update_room_task;
use stdClass;

/**
 * Class api is the public endpoint of the communication api. This class is the point of contact for api usage.
 *
 * Communication api allows to add ad-hoc tasks to the queue to perform actions on the communication providers. This api will
 * not allow any immediate actions to be performed on the communication providers. It will only add the tasks to the queue. The
 * exception has been made for deletion of members in case of deleting the user. This is because the user will not be available.
 * The member management api part allows run actions immediately if required.
 *
 * Communication api does allow to have form elements related to communication api in the required forms. This is done by using
 * the form_definition method. This method will add the form elements to the form.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * @var null|communication_processor $communication The communication settings object
     */
    private ?communication_processor $communication;

    /**
     * Communication handler constructor to manage and handle all communication related actions.
     *
     * This class is the entrypoint for all kinda usages.
     * It will be used by the other api to manage the communication providers.
     *
     * @param string $component The component of the item for the instance
     * @param string $instancetype The type of the item for the instance
     * @param int $instanceid The id of the instance
     *
     */
    private function __construct(
        private string $component,
        private string $instancetype,
        private int $instanceid
    ) {
        $this->communication = communication_processor::load_by_instance(
            $this->component,
            $this->instancetype,
            $this->instanceid,
        );
    }

    /**
     * Get the communication processor object.
     *
     * @param string $component The component of the item for the instance
     * @param string $instancetype The type of the item for the instance
     * @param int $instanceid The id of the instance
     * @return api
     */
    public static function load_by_instance(
        string $component,
        string $instancetype,
        int $instanceid
    ): self {
        return new self($component, $instancetype, $instanceid);
    }

    /**
     * Get the communication room url.
     *
     * @return string|null
     */
    public function get_communication_room_url(): ?string {
        return $this->communication?->get_room_url();
    }

    /**
     * Get the list of plugins for form selection.
     *
     * @return array
     */
    public static function get_communication_plugin_list_for_form(): array {
        // Add the option to have communication disabled.
        $selection[communication_processor::PROVIDER_NONE] = get_string('nocommunicationselected', 'communication');
        $communicationplugins = \core\plugininfo\communication::get_enabled_plugins();
        foreach ($communicationplugins as $pluginname => $notusing) {
            $selection['communication_' . $pluginname] = get_string('pluginname', 'communication_'. $pluginname);
        }
        return $selection;
    }

    /**
     * Define the form elements for the communication api.
     * This method will be called from the form definition method of the instance.
     *
     * @param \MoodleQuickForm $mform The form element
     */
    public function form_definition(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'communication', get_string('communication', 'communication'));

        // List the communication providers.
        $communicationproviders = self::get_communication_plugin_list_for_form();
        $mform->addElement(
            'select',
            'selectedcommunication',
            get_string('seleccommunicationprovider', 'communication'),
            $communicationproviders);
        $mform->addHelpButton('selectedcommunication', 'seleccommunicationprovider', 'communication');
        $mform->setDefault('selectedcommunication', communication_processor::PROVIDER_NONE);

        // Room name for the communication provider.
        $mform->addElement('text',
            'communicationroomname',
            get_string('communicationroomname', 'communication'),
            'maxlength="100" size="20"');
        $mform->addHelpButton('communicationroomname', 'communicationroomname', 'communication');
        $mform->setType('communicationroomname', PARAM_TEXT);
        $mform->hideIf(
            'communicationroomname',
            'selectedcommunication',
            'eq',
            communication_processor::PROVIDER_NONE);
    }

    /**
     * Get the avatar file record for the avatar for filesystem.
     *
     * @param string $filename The filename of the avatar
     * @return stdClass
     */
    public function get_avatar_filerecord(string $filename): stdClass {
        return (object) [
            'contextid' => \context_system::instance()->id,
            'component' => 'core_communication',
            'filearea' => 'avatar',
            'filename' => $filename,
            'filepath' => '/',
            'itemid' => $this->communication->get_id(),
        ];
    }

    /**
     *
     * Get the avatar file.
     *
     * If null is set, then delete the old area file and set the avatarfilename to null.
     * This will make sure the plugin api deletes the avatar from the room.
     *
     * @param string|null $datauri The datauri of the avatar
     * @return bool
     */
    public function set_avatar_from_datauri_or_filepath(?string $datauri): bool {
        global $DB;

        $currentfilerecord = $this->communication->get_avatar();
        if (!empty($datauri) && !empty($currentfilerecord)) {
            $currentfilehash = $currentfilerecord->get_contenthash();
            $updatedfilehash = \file_storage::hash_from_string(file_get_contents($datauri));

            // No update required.
            if ($currentfilehash === $updatedfilehash) {
                return false;
            }
        }

        $context = \context_system::instance();
        $filename = null;

        $fs = get_file_storage();
        $fs->delete_area_files(
            $context->id,
            'core_communication',
            'avatar',
            $this->communication->get_id()
        );

        if (!empty($datauri)) {
            $filename = "avatar.svg";
            $fs->create_file_from_string($this->get_avatar_filerecord($filename), file_get_contents($datauri));
        }

        $DB->set_field('communication', 'avatarfilename', $filename, ['id' => $this->communication->get_id()]);
        return true;
    }

    /**
     * Set the form data if the data is already available.
     *
     * @param \stdClass $instance The instance object
     */
    public function set_data(\stdClass $instance): void {
        if (!empty($instance->id) && $this->communication) {
            $instance->selectedcommunication = $this->communication->get_provider();
            $instance->communicationroomname = $this->communication->get_room_name();
        }
    }

    /**
     * Get the communication provider.
     *
     * @return string
     */
    public function get_current_communication_provider(): string {
        return $this->communication->get_provider();
    }

    /**
     * Create a communication ad-hoc task for create operation.
     * This method will add a task to the queue to create the room.
     *
     * @param string $selectedcommunication The selected communication provider
     * @param string $communicationroomname The communication room name
     * @param string|null $avatarurl The avatar url
     */
    public function create_and_configure_room(
        string $selectedcommunication,
        string $communicationroomname,
        ?string $avatarurl = null): void {

        if ($selectedcommunication !== communication_processor::PROVIDER_NONE && $selectedcommunication !== '') {
            // Create communication record.
            $this->communication = communication_processor::create_instance(
                $selectedcommunication,
                $this->instanceid,
                $this->component,
                $this->instancetype,
                $communicationroomname,
            );

            // Set the avatar.
            if (!empty($avatarurl)) {
                $this->set_avatar_from_datauri_or_filepath($avatarurl);
            }

            // Add ad-hoc task to create the provider room.
            create_and_configure_room_task::queue(
                $this->communication,
            );
        }
    }

    /**
     * Create a communication ad-hoc task for update operation.
     * This method will add a task to the queue to update the room.
     *
     * @param string $selectedprovider The selected communication provider
     * @param string $communicationroomname The communication room name
     * @param string|null $avatarurl The avatar url
     */
    public function update_room(
        string $selectedprovider,
        string $communicationroomname,
        ?string $avatarurl = null): void {

        // Existing object found, let's update the communication record and associated actions.
        if ($this->communication !== null) {
            // Get the previous data to compare for update.
            $previousroomname = $this->communication->get_room_name();

            // Update communication record.
            $this->communication->update_instance($selectedprovider, $communicationroomname);

            // Update the avatar.
            $imageupdaterequired = $this->set_avatar_from_datauri_or_filepath($avatarurl);

            // Add ad-hoc task to update the provider room if the room name changed.
            if ($this->communication->get_provider() !== communication_processor::PROVIDER_NONE &&
                    ($previousroomname !== $communicationroomname || $imageupdaterequired)) {
                update_room_task::queue(
                    $this->communication,
                );
            }
        } else {
            // The instance didn't have any communication record, so create one.
            $this->create_and_configure_room($selectedprovider, $communicationroomname);
        }
    }

    /**
     * Create a communication ad-hoc task for delete operation.
     * This method will add a task to the queue to delete the room.
     */
    public function delete_room(): void {
        if ($this->communication !== null) {
            // Add the ad-hoc task to remove the room data from the communication table and associated provider actions.
            delete_room_task::queue(
                $this->communication,
            );
        }
    }

    /**
     * Create a communication ad-hoc task for delete operation.
     *
     * This method will add a task to the queue to delete the room users.
     */
    public function delete_room_members(): void {
        if (!$this->communication) {
            return;
        }

        // Add the ad-hoc task to remove the room data from the communication table and associated provider actions.
        remove_members_from_room::queue(
            $this->communication,
            $this->communication->get_existing_instance_users(),
        );
    }

    /**
     * Update room membership for a user.
     * This method will add a task to the queue to update the membership.
     *
     * @param string $action The action to perform
     * @param array $userids The user ids to update
     * @param bool $async Run task asynchronously, or not
     */
    public function update_room_membership(string $action, array $userids, bool $async = true): void {
        // No communication object? something not done right.
        if (!$this->communication) {
            return;
        }

        // No userids? don't bother doing anything.
        if (empty($userids)) {
            return;
        }

        // Action selection to add or remove members.
        switch ($action) {
            case 'add':
                $task = add_members_to_room_task::class;
                $operation = 'add_members_to_room';
                $this->communication->create_instance_user_mapping($userids);
                break;

            case 'remove':
                $task = remove_members_from_room::class;
                $operation = 'remove_members_from_room';
                break;
            default:
                throw new \coding_exception('Invalid action');
        }

        // Add the task to action member addition or removal/do it immediately.
        if ($async) {
            $task::queue(
                $this->communication,
                $userids,
            );
        } else {
            $this->communication->get_room_user_provider()->{$operation}($userids);
        }
    }

}
