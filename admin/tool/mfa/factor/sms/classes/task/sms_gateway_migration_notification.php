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

namespace factor_sms\task;

use core\task\adhoc_task;
use moodle_url;

/**
 * Notification for admins to notify about the migration of SMS setup from MFA to SMS gateway plugins.
 *
 * @package    factor_sms
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sms_gateway_migration_notification extends adhoc_task {

    public function execute(): void {
        $user = $this->get_custom_data();

        // In case the user was removed as the site admin before this notification was sent.
        if (!is_siteadmin($user)) {
            return;
        }

        $smsconfigureurl = new moodle_url('/sms/configure.php');
        $messagebody = get_string('notification:smsgatewaymigrationinfo', 'factor_sms', $smsconfigureurl);

        $message = new \core\message\message();
        $message->courseid = SITEID;
        $message->component = 'factor_sms';
        $message->name = 'notices';
        $message->userfrom = \core_user::get_noreply_user();
        $message->subject = get_string('notification:smsgatewaymigration', 'factor_sms');
        $message->fullmessageformat = FORMAT_HTML;
        $message->notification = 1;
        $message->userto = $user;
        $message->fullmessagehtml = $messagebody;
        $message->fullmessage = html_to_text($messagebody);

        // Send message.
        message_send($message);
    }
}
