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
class course_delete_pre implements
    described_hook,
    StoppableEventInterface {

    /**
     * Constructor for the hook.
     *
     * @param stdClass $course The course instance.
     * @param bool $stopped Whether the propagation of this event has been stopped.
     */
    public function __construct(
        protected stdClass $course,
        protected bool $stopped = false,
    ) {
    }

    public static function get_hook_description(): string {
        return get_string('hook_course_deleted_pre', 'course');
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
