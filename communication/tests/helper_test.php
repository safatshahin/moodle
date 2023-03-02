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

namespace core_communication;

/**
 * Class helper_test to test the communicatio helper and its associated methods.
 *
 * @package    core_communication
 * @category   test
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_communication\helper
 */
class helper_test extends \advanced_testcase {

    /**
     * Test get communication providers implementing the feature class from core communication.
     *
     * @return void
     */
    public function test_get_communication_providers_implementing_features(): void {
        $pluginsfromhelper = helper::get_communication_providers_implementing_features();
        $pluginsfromhelper = array_keys($pluginsfromhelper);

        $selection = [];
        $pluginsfromcore = \core\plugininfo\communication::get_enabled_plugins();
        foreach ($pluginsfromcore as $pluginname => $plugin) {
            $selection['communication_' . $pluginname] = $plugin;
        }
        $pluginsfromcore = array_keys($selection);

        // All the plugins implementing feature base must be enabled and matched with enabled plugin list.
        foreach ($pluginsfromhelper as $pluginfromhelper) {
            $this->assertContains($pluginfromhelper, $pluginsfromcore);
        }
    }
}
