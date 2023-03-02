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
 * Class communication_form_base to manage communication provider form options from provider plugins.
 *
 * Every provider plugin should implement this class to return the implemented form elements.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class communication_form_base {

    /**
     * Function to allow child classes load objects etc.
     *
     * @return void
     */
    protected function init(): void {
    }

    /**
     * Get the form data options to get the data from the instance if any data is set.
     *
     * This should match with all elements defined in set_form_definition.
     *
     * @return array
     */
    public static function get_form_data_options(): array {
        return [];
    }

    /**
     * Set the form data to the instance if any data is available.
     *
     * @param \stdClass $instance The actual instance to set the data
     * @param int $communicationid The id of the communication instance
     * @return void
     */
    public static function set_form_data(\stdClass $instance, int $communicationid): void {
    }

    /**
     * Set the form definitions.
     *
     * @param \MoodleQuickForm $mform The form object
     * @return void
     */
    public static function set_form_definition(\MoodleQuickForm $mform): void {
    }
}
