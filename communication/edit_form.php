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

/**
 * Edit communication settings for a course - the form definition.
 *
 * @package    core_communication
 * @copyright  2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Defines the course communication settings form.
 */
class course_communication_form extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {

        $mform = $this->_form;
        $course = $this->_customdata['course'];

        // Add communication plugins to the form.
        $instanceconfig = core_communication\processor::PROVIDER_NONE;
        $communication = \core_communication\api::load_by_instance(
            'core_course',
            'coursecommunication',
            $course->id);
        $communication->form_definition($mform, $instanceconfig);
        $communication->set_data($course);

        // Form buttons.
        $buttonarray = [];
        $classarray = ['class' => 'form-submit'];
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        // Hidden elements.
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        // Finally set the current form data.
        $this->set_data($course);

    }

    /**
     * Fill in the communication page data depending on provider selected.
     */
    public function definition_after_data() {

        $mform = $this->_form;
        $courseid = $mform->getElementValue('courseid');

        // Add communication plugins to the form with respect to the provider.
        $communication = \core_communication\api::load_by_instance(
            'core_course',
            'coursecommunication',
            $courseid
        );
        $communication->form_definition_for_provider($mform);
    }
}
