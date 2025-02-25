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

namespace smsgateway_modica;

use core\http_client;
use core_sms\manager;
use core_sms\message;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Modica SMS gateway.
 *
 * @see https://confluence.modicagroup.com/display/DC/Mobile+Gateway+REST+API#MobileGatewayRESTAPI-Sendingtoasingledestination
 * @package    smsgateway_modica
 * @copyright  2025 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_sms\gateway {

    /**
     * @var string MODICA_DEFAULT_API The default api gateway for modica.
     */
    public const MODICA_DEFAULT_API = 'https://api.modicagroup.com/rest/gateway/messages';

    #[\Override]
    public function send(
        message $message,
    ): message {
        global $DB;

        // Get the config from the message record.
        $modicaconfig = $DB->get_field(
            table: 'sms_gateways',
            return: 'config',
            conditions: [
                'id' => $message->gatewayid,
                'enabled' => 1,
                'gateway' => 'smsgateway_modica\gateway',
            ],
        );

        if (!$modicaconfig) {
            return $message->with(
                status: \core_sms\message_status::GATEWAY_NOT_AVAILABLE,
            );
        }

        $config = (object) json_decode($modicaconfig, true, 512, JSON_THROW_ON_ERROR);
        $recipientnumber = manager::format_number(
            phonenumber: $message->recipientnumber,
            countrycode: $config->countrycode ?? null,
        );

        $params = [
            'http' => $config->modica_url ?? self::MODICA_DEFAULT_API,
            'username' => $config->modica_application_name,
            'password' => $config->modica_application_password,
        ];

        $json = json_encode(
            [
                'destination' => $recipientnumber,
                'content' => $message->content,
            ],
            JSON_THROW_ON_ERROR,
        );

        try {
            $client = new http_client([
                'auth' => [
                    $params['username'],
                    $params['password'],
                ],
            ]);

            $response = $client->post($params['http'], [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $json,
            ]);

            if ($response->getStatusCode() === 201) {
                $status = \core_sms\message_status::GATEWAY_SENT;
            } else {
                $status = \core_sms\message_status::GATEWAY_FAILED;
            }
        } catch (GuzzleException $e) {
            $status = \core_sms\message_status::GATEWAY_FAILED;
        }

        return $message->with(
            status: $status,
        );
    }

    #[\Override]
    public function get_send_priority(message $message): int {
        return 50;
    }
}
