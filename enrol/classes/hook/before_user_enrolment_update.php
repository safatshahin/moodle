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
 * Hook before a user enrolment is updated.
 *
 * @package    core
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_user_enrolment_update implements described_hook {

    /**
     * Constructor for the hook.
     *
     * @param stdClass $enrolinstance The enrol instance.
     * @param stdClass $userenrolmentinstance The user enrolment instance.
     * @param bool $statusmodified Whether the status of the enrolment has been modified.
     * @param bool $timeendmodified Whether the time end of the enrolment has been modified.
     */
    public function __construct(
        protected stdClass $enrolinstance,
        protected stdClass $userenrolmentinstance,
        protected bool $statusmodified,
        protected bool $timeendmodified,
    ) {
    }

    public static function get_hook_description(): string {
        return "This hook is triggered before a user enrolment is updated.";
    }

    public static function get_hook_tags(): array {
        return ['enrol', 'user'];
    }

    /**
     * Get the enrol instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->enrolinstance;
    }

    /**
     * Get user enrolment instance.
     *
     * @return stdClass
     */
    public function get_user_enrolment_instance(): stdClass {
        return $this->userenrolmentinstance;
    }

    /**
     * Get the user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userenrolmentinstance->userid;
    }

    /**
     * Is status modified for the user enrol instance.
     *
     * @return bool
     */
    public function is_status_modified(): bool {
        return $this->statusmodified;
    }

    /**
     * Is the time end for the user enrol instance modified.
     *
     * @return bool
     */
    public function is_timeend_modified(): bool {
        return $this->timeendmodified;
    }
}
