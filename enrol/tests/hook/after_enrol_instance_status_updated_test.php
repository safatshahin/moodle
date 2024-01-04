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
 * Test post enrolment instance status updated hook.
 *
 * @package    core_group
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_enrol\hook\after_enrol_instance_status_updated
 */
class after_enrol_instance_status_updated_test extends \advanced_testcase {

    /**
     * Test hook is dispatch.
     */
    public function test_hook_dispatch(): void {
        $this->resetAfterTest();
        global $DB;

        $count = 0;
        $receivedhook = null;
        $testcallback = function(after_enrol_instance_status_updated $hook) use (&$receivedhook, &$count): void {
            $count++;
            $receivedhook = $hook;
        };

        $this->redirectHook(
            hookname: after_enrol_instance_status_updated::class,
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
        $selfplugin->update_status(
            instance: $instance,
            newstatus: ENROL_INSTANCE_DISABLED,
        );

        $this->assertSame(
            expected: 1,
            actual: $count,
        );
        $this->assertInstanceOf(
            expected: after_enrol_instance_status_updated::class,
            actual:$receivedhook,
        );
        $this->assertSame(
            expected: $instance->id,
            actual: $receivedhook->enrolinstance->id,
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
        $selfplugin->update_status(
            instance: $instance,
            newstatus: ENROL_INSTANCE_DISABLED,
        );
        $this->assertSame(
            expected: 1,
            actual: $count,
        );
    }
}
