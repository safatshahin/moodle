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
 * CLI script to allow recalculation of best attempts for quizzes.
 *
 * @package    mod_quiz
 * @subpackage cli
 * @copyright  2026 Jay Oswald <jayoswald@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz;
use core\output\progress_trace\text_progress_trace;
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/clilib.php");

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'run_all' => false,
        'quiz_id' => 0,
        'adhoc' => false,
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = <<<EOT
Recalculate best quiz attempts.

Options:
--help                Print out this help
--run_all             Recalculate for all quizzes
--quiz_id=ID          Recalculate for specific quiz ID
--adhoc               Schedule adhoc tasks instead of running immediately
EOT;

if ($options['help']) {
    echo $help;
    exit(0);
}

if ($options['run_all'] && $options['quiz_id']) {
    cli_error("Cannot use --run_all and --quiz_id together.");
}

if (!$options['run_all'] && !$options['quiz_id']) {
    cli_error("You must use either --run_all or --quiz_id.");
}

if ($options['run_all']) {
    mtrace('Recalculating best attempts for all quizzes...');
    if ($options['adhoc']) {
        grade_attempt_tracker::calculate_all_queued(new text_progress_trace());
    } else {
        grade_attempt_tracker::calculate_all_now(new text_progress_trace());
    }
} else {
    mtrace('Recalculating best attempts for quiz id ' . $options['quiz_id'] . '...');
    if ($options['adhoc']) {
        grade_attempt_tracker::queue_quiz_calculation($options['quiz_id']);
    } else {
        grade_attempt_tracker::calculate_quiz($options['quiz_id'], new text_progress_trace());
    }
}
