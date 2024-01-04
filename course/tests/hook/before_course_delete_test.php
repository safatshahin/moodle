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

namespace core_course\hook;

/**
 * Test pre course deletion hook.
 *
 * @package    core_course
 * @category   test
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core_course\hook\before_course_delete
 */
class before_course_delete_test extends \advanced_testcase {

    /**
     * Test hook is dispatched while deleting a course.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $count = 0;
        $receivedhook = null;
        $testcallback = function(before_course_delete $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: before_course_delete::class,
            callback:$testcallback,
        );
        delete_course(
            courseorid: $course1,
            showfeedback: false,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: before_course_delete::class,
            actual: $receivedhook,
        );
        $this->assertSame(
            expected: $course1->id,
            actual: $receivedhook->course->id,
        );

        // Now stop the redirection and check that the hook is not dispatched.
        $this->stopHookRedirections();
        delete_course(
            courseorid: $course2,
            showfeedback: false,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
