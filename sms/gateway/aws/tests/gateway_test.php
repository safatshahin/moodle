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

namespace smsgateway_aws;

use core_sms\message;
use core_sms\message_status;

/**
 * AWS SMS gateway tests.
 *
 * @package    smsgateway_aws
 * @category   test
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \smsgateway_aws\gateway
 */
final class gateway_test extends \advanced_testcase {
    public function test_send(): void {
        $this->resetAfterTest();

        $config = new \stdClass();
        $config->api_key = 'test_api_key';
        $config->api_secret = 'test_api_secret';
        $config->gateway = 'aws_sns';
        $config->api_region = 'ap-southeast-2';

        $manager = \core\di::get(\core_sms\manager::class);
        $gw = $manager->create_gateway_instance(
            classname: gateway::class,
            name: 'aws',
            enabled: true,
            config: $config,
        );

        $message = $manager->send(
            recipientnumber: '+447123456789',
            content: 'Hello, world!',
            component: 'core',
            messagetype: 'test',
            recipientuserid: null,
            async: false,
        );

        $this->assertInstanceOf(message::class, $message);
        $this->assertIsInt($message->id);
        // We can't reliably test success as AWS doesn't use dependency injection,
        // We can try mocking the result using aws-sdk, eg MockHandler.
        $this->assertEquals(message_status::GATEWAY_FAILED, $message->status);
        $this->assertEquals($gw->id, $message->gatewayid);
        $this->assertEquals('Hello, world!', $message->content);

        $storedmessage = $manager->get_message(['id' => $message->id]);
        $this->assertEquals($message, $storedmessage);
    }
}
