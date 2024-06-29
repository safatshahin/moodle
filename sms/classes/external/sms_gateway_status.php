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

namespace core_sms\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_api;

/**
 * Webservice to enable or disable sms gateway.
 *
 * @package    core_sms
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sms_gateway_status extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'gateway' => new external_value(PARAM_INT, 'Gateway ID', VALUE_REQUIRED),
            'enabled' => new external_value(PARAM_INT, 'Enabled or disabled', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $gateway, int $enabled): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'gateway' => $gateway,
            'enabled' => $enabled,
        ]);

        $result = [
            'result' => true,
            'message' => '',
            'messagetype' => '',
        ];
        $manager = \core\di::get(\core_sms\manager::class);
        $gatewaymanagers = $manager->get_gateway_instances(['id' => $params['gateway']]);
        $gatewaymanager = reset($gatewaymanagers);

        if ($params['enabled'] === 1) {
            $manager->enable_gateway(gateway: $gatewaymanager);
        } else {
            $gatewayresult = $manager->disable_gateway(gateway: $gatewaymanager);
            if ($gatewayresult->enabled) {
                $result = [
                    'result' => false,
                    'message' => 'sms_gateway_disable_failed',
                    'messagetype' => 'error'
                ];
            }
        }

        return $result;
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, 'Status true or false'),
                'message' => new external_value(PARAM_TEXT, 'Messages'),
                'messagetype' => new external_value(PARAM_TEXT, 'Message type'),
            ]
        );
    }

}
