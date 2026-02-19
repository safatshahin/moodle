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

/**
 * Unit tests for tool_installaddon post-install task.
 *
 * @package    tool_installaddon
 * @copyright  2026 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_installaddon\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for post-install task.
 */
final class post_install_test extends \advanced_testcase {
    public function test_execute_sets_footer_when_hidden(): void {
        $this->resetAfterTest();

        set_config('activitychooseractivefooter', 'hidden');
        $task = new post_install();
        $task->execute();

        $this->assertSame('tool_installaddon', get_config('core', 'activitychooseractivefooter'));
    }

    public function test_execute_does_not_override_existing_footer(): void {
        $this->resetAfterTest();

        set_config('activitychooseractivefooter', 'mod_lti');
        $task = new post_install();
        $task->execute();

        $this->assertSame('mod_lti', get_config('core', 'activitychooseractivefooter'));
    }
}
