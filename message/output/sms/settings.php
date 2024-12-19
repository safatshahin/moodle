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
 * Settings for SMS processor.
 *
 * @package    message_sms
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Get the gateway records.
$manager = \core\di::get(\core_sms\manager::class);
$gatewayrecords = $manager->get_gateway_records(['enabled' => 1]);
$smsconfigureurl = new moodle_url(
    '/sms/configure.php',
    [
        'returnurl' => new moodle_url(
            '/admin/settings.php',
            ['section' => 'messagesettingsms'],
        ),
    ],
);
$smsconfigureurl = $smsconfigureurl->out();

$settings->add(
    new admin_setting_heading(
        'message_sms/heading',
        '',
        new lang_string(
            'settings:heading',
            'message_sms',
        ),
    ),
);

if (count($gatewayrecords) > 0) {
    $gateways = [0 => new lang_string('none')];
    foreach ($gatewayrecords as $record) {
        $values = explode('\\', $record->gateway);
        $gatewayname = new lang_string('pluginname', $values[0]);
        $gateways[$record->id] = $record->name . ' (' . $gatewayname . ')';
    }

    $settings->add(
        new admin_setting_configselect(
            'message_sms/smsgateway',
            new lang_string('settings:smsgateway', 'message_sms'),
            new lang_string('settings:smsgateway_help', 'message_sms', $smsconfigureurl),
            0,
            $gateways,
        ),
    );
} else {
    $settings->add(
        new admin_setting_description(
            'message_sms/setupdesc',
            '',
            new lang_string(
                'settings:setupdesc',
                'message_sms',
                $smsconfigureurl,
            ),
        ),
    );
}
