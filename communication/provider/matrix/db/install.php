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
 * Upgrade script for communication_matrix.
 *
 * @package   communication_matrix
 * @copyright 2023 Stevani Andolo <stevani.andolo@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_communication_matrix_install() {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/user/profile/definelib.php');
    require_once($CFG->dirroot . '/user/profile/field/text/define.class.php');

    // Check if communicatoin category exists.
    $categoryname = get_string('communicationsubsystem', 'user');
    $category = $DB->count_records('user_info_category', ['name' => $categoryname]);
    if ($category < 1) {
        $data = new \stdClass();
        $data->sortorder = $DB->count_records('user_info_category') + 1;
        $data->name = $categoryname;
        $data->id = $DB->insert_record('user_info_category', $data, true);

        $createdcategory = $DB->get_record('user_info_category', array('id' => $data->id));
        $categoryid = $createdcategory->id;
        \core\event\user_info_category_created::create_from_category($createdcategory)->trigger();
        set_config('profile_category', $categoryname, 'communication');
    } else {
        $category = $DB->get_record('user_info_category', array('name' => $categoryname));
        $categoryid = $category->id;
    }

    // Check if matrixuserid exists in user_info_field table.
    $matrixuserid = $DB->count_records('user_info_field', [
        'shortname' => 'matrixuserid', 'categoryid' => $categoryid
    ]);
    if ($matrixuserid < 1) {
        $profileclass = new \profile_define_text();
        $data = (object) [
            'shortname' => 'matrixuserid',
            'name' => get_string('matrixuserid', 'user'),
            'datatype' => 'text',
            'description' => get_string('matrixuserid_desc', 'user'),
            'descriptionformat' => 1,
            'categoryid' => $categoryid,
            'forceunique' => 1,
            'visible' => 0,
            'param1' => 30,
            'param2' => 2048
        ];
        $profileclass->define_save($data);
        set_config('profile_field_name', 'matrixuserid', 'communication_matrix');
    }

    return true;
}
