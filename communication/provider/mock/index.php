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
 * Index page for communication_mock.
 *
 * @package    communication_mock
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
global $CFG, $PAGE, $OUTPUT;

$reset = optional_param('reset', 0, PARAM_INT);

$context = context_system::instance();
$title = get_string("pluginname", 'communication_mock');
$PAGE->set_url('/communication/provider/mock/index.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);

echo $OUTPUT->header();

$tempdir = 'communication_mock/';
$localdir = $CFG->dataroot . '/temp/'.$tempdir;

echo '<br>';
echo 'Created members:';
echo '<br>';
$temp_filename = "createmembers.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}


echo '<br>';
echo 'Added members:';
echo '<br>';
$temp_filename = "addedmembers.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}

echo '<br>';
echo 'Removed members:';
echo '<br>';
$temp_filename = "removedmembers.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}

echo '<br>';
echo 'Created chat room:';
echo '<br>';
$temp_filename = "createdchatroom.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}

echo '<br>';
echo 'Updated chat room:';
echo '<br>';
$temp_filename = "updatedchatroom.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}

echo '<br>';
echo 'Deleted chat room:';
echo '<br>';
$temp_filename = "deletedchatroom.txt";
$fileToUpload = $localdir. $temp_filename;
if (file_exists($fileToUpload)) {
    $filecontent = file_get_contents($fileToUpload);
    echo $filecontent;
}

if ($reset === 1) {
    global $CFG;
    $files = glob($CFG->dataroot . '/temp/communication_mock/*'); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file)) {
            unlink($file); // delete file
        }
    }
}

echo $OUTPUT->footer();
