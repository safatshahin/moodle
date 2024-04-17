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

namespace smsgateway_aws\local\service;

use core\aws\aws_helper;
use core_sms\message_status;
use MoodleQuickForm;
use smsgateway_aws\local\aws_sms_service_provider;
use stdClass;

class aws_sns implements aws_sms_service_provider {

    /**
     * Include the required calls.
     */
    private static function require(): void {
        global $CFG;
        require_once($CFG->libdir . '/aws-sdk/src/functions.php');
        require_once($CFG->libdir . '/guzzlehttp/guzzle/src/functions_include.php');
        require_once($CFG->libdir . '/guzzlehttp/promises/src/functions_include.php');
    }

    public static function send_sms_message(
        string $messagecontent,
        string $phonenumber,
        stdclass $config,
    ): message_status {
        global $SITE;
        self::require();

        // Setup client params and instantiate client.
        $params = [
            'version' => 'latest',
            'region' => $config->api_region,
            'http' => ['proxy' => aws_helper::get_proxy_string()],
        ];
        if (!$config->usecredchain) {
            $params['credentials'] = [
                'key' => $config->api_key,
                'secret' => $config->api_secret,
            ];
        }
        $client = new \Aws\Sns\SnsClient($params);

        // Set up the sender information.
        $senderid = $SITE->shortname;
        // Remove spaces and non-alphanumeric characters from ID.
        $senderid = preg_replace("/[^A-Za-z0-9]/", '', trim($senderid));
        // We have to truncate the senderID to 11 chars.
        $senderid = substr($senderid, 0, 11);

        try {
            // These messages need to be transactional.
            $client->SetSMSAttributes([
                'attributes' => [
                    'DefaultSMSType' => 'Transactional',
                    'DefaultSenderID' => $senderid,
                ],
            ]);

            // Actually send the message.
            $client->publish([
                'Message' => $messagecontent,
                'PhoneNumber' => $phonenumber,
            ]);
            return \core_sms\message_status::GATEWAY_SENT;
        } catch (\Aws\Exception\AwsException $e) {
            return \core_sms\message_status::GATEWAY_NOT_AVAILABLE;
        }
    }

    /**
     * Set form definition.
     *
     * @param MoodleQuickForm $mform Moodle form element.
     * @return void
     * @todo MDL-81732 Implement the api in gateway and use this/move to the api extention.
     */
    public static function set_form_definition(
        MoodleQuickForm $mform,
    ): void {
        $codeslink = 'https://en.wikipedia.org/wiki/List_of_country_calling_codes';
        $link = \html_writer::link($codeslink, $codeslink);
        $mform->addElement(
            'text',
            'countrycode',
            get_string('countrycode', 'smsgateway_aws'),
            'maxlength="255" size="20"',
        );
        $mform->addHelpButton(
            elementname: 'countrycode',
            identifier: 'countrycode',
            component: 'smsgateway_aws',
            a: $link,
        );
        $mform->setDefault(
            elementName: 'countrycode',
            defaultValue: 0,
        );

        $gateways = [
            'aws_sns' => get_string('aws_sns', 'smsgateway_aws'),
        ];
        $mform->addElement(
            'select',
            'gateway',
            get_string('gateway', 'smsgateway_aws'),
            $gateways,
        );
        $mform->addHelpButton(
            elementname: 'gateway',
            identifier: 'gateway',
            component: 'smsgateway_aws',
        );
        $mform->setDefault(
            elementName: 'gateway',
            defaultValue: 'aws_sns',
        );

        $mform->addElement(
            'checkbox',
            'usecredchain',
            get_string('aws_usecredchain', 'smsgateway_aws'),
        );
        $mform->setDefault(
            elementName: 'usecredchain',
            defaultValue: 0,
        );

        $mform->addElement(
            'text',
            'api_key',
            get_string('aws_key', 'smsgateway_aws'),
            'maxlength="255" size="20"',
        );
        $mform->addHelpButton(
            elementname: 'aws_key',
            identifier: 'aws_key',
            component: 'smsgateway_aws',
        );
        $mform->setDefault(
            elementName: 'aws_key',
            defaultValue: '',
        );

        $mform->addElement(
            'text',
            'api_secret',
            get_string('api_secret', 'smsgateway_aws'),
            'maxlength="255" size="20"',
        );
        $mform->addHelpButton(
            elementname: 'api_secret',
            identifier: 'api_secret',
            component: 'smsgateway_aws',
        );
        $mform->setDefault(
            elementName: 'api_secret',
            defaultValue: '',
        );

        // TODO MDL-81732 move admin_settings_aws_region to form element.
        $mform->addElement(
            'text',
            'api_region',
            get_string('api_region', 'smsgateway_aws'),
            'maxlength="255" size="20"',
        );
        $mform->addHelpButton(
            elementname: 'api_region',
            identifier: 'api_region',
            component: 'smsgateway_aws',
        );
        $mform->setDefault(
            elementName: 'api_region',
            defaultValue: 'ap-southeast-2',
        );
    }
}
