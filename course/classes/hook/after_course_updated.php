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

namespace core_course\hook;

use core\hook\described_hook;
use stdClass;

/**
 * Hook after course updates.
 *
 * @package    core_course
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_course_updated implements described_hook {

    /**
     * Constructor for the hook.
     *
     * @param stdClass $course The course instance.
     * @param stdClass $oldcourse The old course instance.
     * @param bool $changeincoursecat Whether the course category has changed.
     */
    public function __construct(
        protected stdClass $course,
        protected stdClass $oldcourse,
        protected bool $changeincoursecat = false,
    ) {
    }

    public static function get_hook_description(): string {
        return "This hook is dispatched after a course is updated.";
    }

    public static function get_hook_tags(): array {
        return ['course'];
    }

    /**
     * Get the course instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->course;
    }

    /**
     * Get the old course instance.
     *
     * @return stdClass
     */
    public function get_old_instance(): stdClass {
        return $this->oldcourse;
    }

    /**
     * Is the course category changed.
     *
     * @return bool
     */
    public function is_course_category_changed(): bool {
        return $this->changeincoursecat;
    }
}
