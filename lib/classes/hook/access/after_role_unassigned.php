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

namespace core\hook\access;

use core\hook\described_hook;
use context;

/**
 * Hook after a role is unassigned.
 *
 * @package    core
 * @copyright  2023 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_unassigned_post implements described_hook {

    /**
     * Constructor for the hook.
     *
     * @param context $context The context of the role assignment.
     * @param int $userid The user id of the user.
     *
     */
    public function __construct(
        protected context $context,
        protected int $userid,
    ) {
    }

    public static function get_hook_description(): string {
        return get_string('hook_role_unassigned_post', 'access');
    }

    public static function get_hook_tags(): array {
        return ['role'];
    }

    /**
     * Get the context of the role assignment.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Get the user id of the user.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }
}
