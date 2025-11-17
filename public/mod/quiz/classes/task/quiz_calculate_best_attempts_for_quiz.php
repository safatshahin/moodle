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

namespace mod_quiz\task;

use core\task\adhoc_task;
use core\output\progress_trace\text_progress_trace;

/**
 * Calculates the best attempts for a quiz.
 *
 * @package    mod_quiz
 * @copyright  2026 Jay Oswald <jayoswald@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_calculate_best_attempts_for_quiz extends adhoc_task {
    /**
     * Execute the task.
     */
    #[\Override]
    public function execute() {
        $quizid = $this->get_custom_data()->id;
        \mod_quiz\grade_attempt_tracker::calculate_quiz($quizid, new text_progress_trace());
    }
}
