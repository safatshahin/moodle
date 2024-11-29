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

namespace tool_mfa\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * External API to get the factor combinations table.
 *
 * @package    tool_mfa
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factor_combinations_table extends external_api {

    /**
     * Generate parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get the factor combinations table with HTML format.
     *
     * @return array The generated content.
     */
    public static function execute(): array {
        require_capability('moodle/site:config', \context_system::instance());

        $factorcombinations = new \tool_mfa\local\admin_setting_factor_combinations();
        return [
            'html' => $factorcombinations->define_factor_combinations_table(),
        ];
    }

    /**
     * Generate content return value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'html' => new external_value(
                PARAM_RAW,
                'The generated factor combinations table',
                VALUE_DEFAULT,
                '',
            ),
        ]);
    }
}
