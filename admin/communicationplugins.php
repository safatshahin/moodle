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
 * Communication plugin settings.
 *
 * @package    core
 * @subpackage communication
 * @copyright  2022 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');

$action = required_param('action', PARAM_ALPHANUMEXT);
$name   = required_param('name', PARAM_PLUGIN);

$syscontext = context_system::instance();
$PAGE->set_url('/admin/communicationplugins.php');
$PAGE->set_context($syscontext);

require_admin();

$return = new moodle_url('/admin/settings.php', array('section' => 'managecommunications'));

$plugins = core_plugin_manager::instance()->get_plugins_of_type('comm');
$sortorder = array_flip(array_keys($plugins));

if (!isset($plugins[$name])) {
    throw new moodle_exception('commnotfound', 'communication', $return, $name);
}

switch ($action) {
    case 'disable':
        if ($plugins[$name]->is_enabled()) {
            set_config('disabled', 1, 'comm_'. $name);
        }
        break;
    case 'enable':
        if (!$plugins[$name]->is_enabled()) {
            unset_config('disabled', 'comm_'. $name);
        }
        break;
}

core_plugin_manager::reset_caches();

redirect($return);
