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
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_user\hook\before_user_update
 */
class before_user_update_test extends \advanced_testcase {

    /**
     * Test hook is dispatched.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();

        $count = 0;
        $receivedhook = null;
        $testcallback = function(before_user_update $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: before_user_update::class,
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
            expected: before_user_update::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $user1->id,
            actual: $receivedhook->user->id,
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
