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

use core_calendar\local\event\forms\eventtype;

require_once($CFG->dirroot . '/message/output/lib.php');
require_once($CFG->libdir . '/moodlelib.php');

/**
 * Message processor for SMS.
 *
 * @package    message_sms
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_sms extends message_output {

    public function send_message($eventdata): bool {
        $userdata = is_object($eventdata->userto) ? $eventdata->userto : get_complete_user_data('id', $eventdata->userto);

        if (!$this->should_send_sms($eventdata, $userdata)) {
            return false;
        }

        $manager = \core\di::get(\core_sms\manager::class);
        $manager->send(
            recipientnumber: $userdata->phone2,
            content: $eventdata->smallmessage,
            component: $eventdata->component,
            messagetype: $eventdata->name,
            recipientuserid: $userdata->id,
            async: false,
            gatewayid: get_config('message_sms', 'smsgateway'),
        );

        // TODO add validation to return false if issues with sending SMS itself.
        return true;
    }

    /**
     * Check whether the SMS eventdata and user fulfills the requirements.
     *
     * @param stdclass $eventdata
     * @param stdclass $userdata
     * @return bool
     */
    public function should_send_sms(stdclass $eventdata, stdclass $userdata): bool {
        // Skip any SMS if user doesn't have a mobile number.
        if (empty($userdata->phone2)) {
            return false;
        }

        // Skip any messaging of suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' || $eventdata->userto->suspended || $eventdata->userto->deleted) {
            return false;
        }

        // Don't send SMS if it's not a production site and the following config is set.
        if (!empty($CFG->nosmsever)) {
            return false;
        }

        // Check support for SMS from the component.
        $processor = new stdclass();
        $processor->name = 'sms';
        $provider = new stdclass();
        $provider->component = $eventdata->component;

        $hook = new \core_message\hook\check_processor_support(
            processor: $processor,
            provider: $provider,
        );
        $hookmanager = \core\di::get(\core\hook\manager::class)->dispatch($hook);
        if (!$hookmanager->is_processor_supported()) {
            return false;
        }

        return true;
    }

    public function load_data(&$preferences, $userid) {
        return;
    }

    public function config_form($preferences) {
        return;
    }

    public function process_form($form, &$preferences) {
        return;
    }

    public function get_default_messaging_settings() {
        return MESSAGE_DISALLOWED;
    }

    public function can_send_to_any_users() {
        return true;
    }
}
