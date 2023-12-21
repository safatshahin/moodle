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

namespace core_group\hook;

use stdClass;

/**
 * Hook after group creation.
 *
 * @package    core_group
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_created_post implements \core\hook\described_hook {

    public function __construct(
        protected stdClass $groupinstance,
    ) {}

    public static function get_hook_description(): string {
        return 'Hook dispatched after a group is created.';
    }

    public static function get_hook_tags(): array {
        return ['group'];
    }

    /**
     * Get the group instance.
     *
     * @return stdClass
     */
    public function get_instance(): stdClass {
        return $this->groupinstance;
    }
}
