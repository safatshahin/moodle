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
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Hook before course deletion.
 *
 * @package    core_course
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_course_delete implements
    described_hook,
    StoppableEventInterface {

    /**
     * @var bool Whether the propagation of this event has been stopped.
     */
    protected bool $stopped = false;

    /**
     * Constructor for the hook.
     *
     * @param stdClass $course The course instance.
     */
    public function __construct(
        protected stdClass $course,
    ) {
    }

    public static function get_hook_description(): string {
        return "This hook is triggered before a course is deleted.";
    }

    public static function get_hook_tags(): array {
        return ['course'];
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }

    /**
     * Stop the propagation of this event.
     */
    public function stop(): void {
        $this->stopped = true;
    }

    /**
     * Get the course instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->course;
    }
}
