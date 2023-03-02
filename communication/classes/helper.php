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
 * Class helper will have all the common and necessary methods for core_communication which can be re-used in different locations.
 *
 * @package    core_communication
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Get the communication providers which are implementing communication feature.
     *
     * @return array
     */
    public static function get_communication_providers_implementing_features(): array {
        $plugins = \core_component::get_plugin_list_with_class('communication', 'communication_feature',
            'communication_feature.php');

        // Unset the inactive plugins.
        foreach ($plugins as $componentname => $plugin) {
            if (!\core\plugininfo\communication::is_plugin_enabled($componentname)) {
                unset($plugins[$componentname]);
            }
        }

        return $plugins;
    }

}
