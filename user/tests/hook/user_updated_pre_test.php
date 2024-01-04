<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace core_user\hook;

/**
 * Test pre user update hook.
 *
 * @package    core_user
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_user\hook\user_updated_pre
 */
class user_updated_pre_test extends \advanced_testcase {

    /**
     * Test get description.
     *
     * @covers ::get_hook_description
     */
    public function test_get_hook_description(): void {
        $this->assertIsString(
            actual: user_updated_pre::get_hook_description(),
        );
    }

    /**
     * Test group property.
     *
     * @covers ::get_instance
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $olduser = $this->getDataGenerator()->create_user();
        $user = $olduser;
        $user->suspended = 1;

        $hook = new user_updated_pre(
            user: $user,
            currentuserdata: $olduser,
        );
        $this->assertSame(
            expected: $user,
            actual: $hook->get_instance(),
        );

        $this->assertSame(
            expected: $olduser,
            actual: $hook->get_old_instance(),
        );
    }

    /**
     * Test get tags.
     *
     * @covers ::get_hook_tags
     */
    public function test_hook_tags(): void {
        $this->assertIsArray(
            actual: user_updated_pre::get_hook_tags(),
        );
        $this->assertSame(
            expected: ['user'],
            actual: user_updated_pre::get_hook_tags(),
        );
    }

    /**
     * Test hook is dispatched.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();

        $count = 0;
        $receivedhook = null;
        $testcallback = function(user_updated_pre $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: user_updated_pre::class,
            callback: $testcallback,
        );
        $user1 = $this->getDataGenerator()->create_user();
        $user1->suspended = 1;
        user_update_user(
            user: $user1,
            updatepassword: false,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: user_updated_pre::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $user1->id,
            actual: $receivedhook->get_instance()->id,
        );

        $this->stopHookRedirections();
        $user2 = $this->getDataGenerator()->create_user();
        $user2->suspended = 1;
        user_update_user(
            user: $user2,
            updatepassword: false,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
