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

namespace core_user\hook;

use stdClass;

/**
 * Hook before user deletion.
 *
 * @package    core_user
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_deleted_pre implements \core\hook\described_hook {

    /**
     * Constructor for the hook.
     *
     * @param stdClass $user The user instance
     */
    public function __construct(
        protected stdClass $user,
    ) {}

    public static function get_hook_description(): string {
        return 'Hook dispatched after a user is deleted.';
    }

    public static function get_hook_tags(): array {
        return ['course'];
    }

    /**
     * Get the user instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->user;
    }
}
