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

use core_communication\communication_form_base;

/**
 * Class matrix_form_definition to manage the custom form elements for matrix plugin.
 *
 * @package    communication_matrix
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix_form_definition extends communication_form_base {

    public static function get_form_data_options(): array {
        return ['matrixroomtopic'];
    }

    public static function set_form_data(\stdClass $instance, int $communicationid): void {
        if (!empty($instance->id) && !empty($communicationid)) {
            $matrixroomdata = new matrix_rooms($communicationid);
            $instance->matrixroomtopic = $matrixroomdata->topic;
        }
    }

    public static function set_form_definition(\MoodleQuickForm $mform): void {
        // Room description for the communication provider.
        $mform->insertElementBefore($mform->createElement('text', 'matrixroomtopic',
                get_string('matrixroomtopic', 'communication_matrix'),
                'maxlength="255" size="20"'), 'addcommunicationoptionshere');
        $mform->addHelpButton('matrixroomtopic', 'matrixroomtopic', 'communication_matrix');
        $mform->setType('matrixroomtopic', PARAM_TEXT);
    }
}
