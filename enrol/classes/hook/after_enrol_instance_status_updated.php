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

namespace core_enrol\hook;

use core\hook\described_hook;
use stdClass;

/**
 * Hook after enrolment status is changed.
 *
 * @package    core_enrol
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_enrol_instance_status_updated implements described_hook {

    /**
     * Constructor for the hook.
     *
     * @param stdClass $enrolinstance The enrol instance.
     * @param int $newstatus The new status.
     */
    public function __construct(
        protected stdClass $enrolinstance,
        protected int $newstatus,
    ) {
    }

    public static function get_hook_description(): string {
        return "This hook is called after the enrolment status is changed.";
    }

    public static function get_hook_tags(): array {
        return ['enrol'];
    }

    /**
     * Get the group instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->enrolinstance;
    }

    /**
     * Get the new status for the enrolment instance.
     *
     * @return int
     */
    public function get_new_enrol_status(): int {
        return $this->newstatus;
    }
}
