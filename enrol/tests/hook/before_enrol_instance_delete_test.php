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

namespace core_enrol\hook;

/**
 * Test pre enrolment instance delete hook.
 *
 * @package    core_group
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_enrol\hook\before_enrol_instance_delete
 */
class before_enrol_instance_delete_test extends \advanced_testcase {

    /**
     * Test get description.
     *
     * @covers ::get_hook_description
     */
    public function test_get_hook_description(): void {
        $this->assertIsString(
            actual: before_enrol_instance_delete::get_hook_description(),
        );
    }

    /**
     * Test enrol property.
     *
     * @covers ::get_instance
     */
    public function test_constructor(): void {
        $this->resetAfterTest();
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $instance = $DB->get_record(
            table: 'enrol',
            conditions: [
                'courseid' => $course->id,
                'enrol' => 'self',
            ],
            strictness: MUST_EXIST,
        );

        $hook = new before_enrol_instance_delete(
            enrolinstance: $instance,
        );
        $this->assertSame(
            expected: $instance,
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
            actual: before_enrol_instance_delete::get_hook_tags(),
        );
        $this->assertSame(
            expected: ['enrol'],
            actual: before_enrol_instance_delete::get_hook_tags(),
        );
    }

    /**
     * Test hook is dispatched.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();
        global $DB;

        $count = 0;
        $receivedhook = null;
        $testcallback = function(before_enrol_instance_delete $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: before_enrol_instance_delete::class,
            callback: $testcallback,
        );

        $selfplugin = enrol_get_plugin(name: 'self');
        $studentrole = $DB->get_record(
            table: 'role',
            conditions: ['shortname' => 'student'],
        );
        $course1 = $this->getDataGenerator()->create_course();
        // Creating enrol instance.
        $instanceid = $selfplugin->add_instance(
            course: $course1,
            fields: [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 1',
                'customint6' => 1,
                'roleid' => $studentrole->id,
            ],
        );

        // Deleting enrol instance.
        $instance = $DB->get_record(
            table: 'enrol',
            conditions: ['id' => $instanceid],
        );
        $selfplugin->delete_instance(
            instance: $instance,
        );

        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: before_enrol_instance_delete::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $instance->id,
            actual: $receivedhook->get_instance()->id,
        );

        // Now stop the redirection and check that the hook is not dispatched.
        $this->stopHookRedirections();
        $course2 = $this->getDataGenerator()->create_course();
        // Creating enrol instance.
        $instanceid = $selfplugin->add_instance(
            course: $course2,
            fields: [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 1',
                'customint6' => 1,
                'roleid' => $studentrole->id,
            ],
        );

        // Deleting enrol instance.
        $instance = $DB->get_record(
            table: 'enrol',
            conditions: ['id' => $instanceid],
        );
        $selfplugin->delete_instance(
            instance: $instance,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
