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
 * Edit communication settings for a course.
 *
 * @package    core_communication
 * @copyright  2023 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once('lib.php');
require_once('edit_form.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

$redirecturl = new moodle_url('/course/view.php', ['id' => $courseid]);

// Requires communication to be enabled.
if (!core_communication\api::is_available()) {
    redirect($redirecturl);
}

// Perform some basic access control checks.
if ($courseid) {

    if ($courseid == SITEID) {
        // Don't allow editing of 'site course' using this form.
        throw new \moodle_exception('cannoteditsiteform');
    }

    if (!$course = $DB->get_record('course', ['id' => $courseid])) {
        throw new \moodle_exception('invalidcourseid');
    }

    $context = context_course::instance($course->id);

    require_login($course);
    require_capability('moodle/course:update', $context);

} else {

    require_login();
    throw new \moodle_exception('needcourseid');
}

// Set up the page.
$PAGE->set_course($course);
$PAGE->set_url('/communication/edit.php', ['courseid' => $course->id]);
$PAGE->set_title($course->shortname);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

// Get our form definitions.
$form = new course_communication_form(null, ['course' => $course]);

if ($form->is_cancelled()) {

    redirect($redirecturl);

} else if ($data = $form->get_data()) {

    // Set id property for correct use in update_course.
    $data->id = $data->courseid;

    update_course($data);
    redirect($redirecturl);
}

// Display the page contents.
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
