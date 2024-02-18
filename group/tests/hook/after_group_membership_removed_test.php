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

namespace core_group\hook;

/**
 * Test post group membership removed hook.
 *
 * @package    core_group
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_group\hook\after_group_membership_removed
 */
class after_group_membership_removed_test extends \advanced_testcase {

    /**
     * Test get description.
     *
     * @covers ::get_hook_description
     */
    public function test_get_hook_description(): void {
        $this->assertIsString(
            actual: after_group_membership_removed::get_hook_description(),
        );
    }

    /**
     * Test group property.
     *
     * @covers ::get_instance
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(
            record: ['courseid' => $course->id],
        );
        $user = $this->getDataGenerator()->create_user();

        $hook = new after_group_membership_removed(
            groupinstance: $group,
            userids: [$user->id],
        );
        $this->assertSame(
            expected: $group,
            actual: $hook->get_instance(),
        );
        $this->assertSame(
            expected: [$user->id],
            actual: $hook->get_userids(),
        );
    }

    /**
     * Test get tags.
     *
     * @covers ::get_hook_tags
     */
    public function test_hook_tags(): void {
        $this->assertIsArray(
            actual: after_group_membership_removed::get_hook_tags(),
        );
        $this->assertSame(
            expected: ['group'],
            actual: after_group_membership_removed::get_hook_tags(),
        );
    }

    /**
     * Test hook is dispatched.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();

        $count = 0;
        $receivedhook = null;
        $testcallback = function(after_group_membership_removed $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: after_group_membership_removed::class,
            callback: $testcallback,
        );
        $course1 = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(
            record: ['courseid' => $course1->id],
        );
        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            userid: $user1->id,
            courseid: $course1->id,
        );
        $this->getDataGenerator()->create_group_member(
            record: [
                'groupid' => $group1->id,
                'userid' => $user1->id,
            ],
        );
        groups_remove_member(
            grouporid: $group1,
            userorid: $user1,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: after_group_membership_removed::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $group1->id,
            actual: $receivedhook->get_instance()->id,
        );

        // Now stop the redirection and check that the hook is not dispatched.
        $this->stopHookRedirections();
        $course2 = $this->getDataGenerator()->create_course();
        $group2 = $this->getDataGenerator()->create_group(
            record: ['courseid' => $course2->id],
        );
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            userid: $user2->id,
            courseid: $course2->id,
        );
        $this->getDataGenerator()->create_group_member(
            record: [
                'groupid' => $group2->id,
                'userid' => $user2->id,
            ],
        );
        groups_remove_member(
            grouporid: $group2,
            userorid: $user2,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
