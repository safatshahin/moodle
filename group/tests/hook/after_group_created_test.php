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
 * Test post group creation hook.
 *
 * @package    core_group
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_group\hook\afterGroup_created_
 */
class group_created_post_test extends \advanced_testcase {

    /**
     * Test get description.
     *
     * @covers ::get_hook_description
     */
    public function test_get_hook_description(): void {
        $this->assertIsString(
            actual: afterGroup_created_::get_hook_description(),
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

        $hook = new afterGroup_created_(
            groupinstance: $group,
        );
        $this->assertSame(
            expected: $group,
            actual: $hook->get_instance(),
        );
    }

    /**
     * Test get tags.
     *
     * @covers ::get_hook_tags
     */
    public function test_hook_tags(): void {
        $this->assertIsArray(
            actual: afterGroup_created_::get_hook_tags(),
        );
        $this->assertSame(
            expected: ['group'],
            actual: afterGroup_created_::get_hook_tags(),
        );
    }

    /**
     * Test hook is dispatched.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();

        $count = 0;
        $receivedhook = null;
        $testcallback = function(afterGroup_created_ $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: afterGroup_created_::class,
            callback: $testcallback,
        );
        $course1 = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(
            record: ['courseid' => $course1->id],
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: afterGroup_created_::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $group1->id,
            actual: $receivedhook->get_instance()->id,
        );

        // Now stop the redirection and check that the hook is not dispatched.
        $this->stopHookRedirections();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(
            record: ['courseid' => $course2->id],
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
