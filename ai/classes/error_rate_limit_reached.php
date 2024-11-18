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

namespace core_ai;

use core_ai\ai_error_message;

/**
 * Error message for rate limit reached.
 *
 * @package    core_ai
 * @copyright  2024 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class error_rate_limit_reached extends ai_error_message {
    public function __construct(
        protected readonly bool $global = false,
    ) {
        $limitstring = 'Rate limit reached'; // off course use lang strings.
        if ($this->global) {
            $limitstring = 'Global rate limit reached';
        }
        parent::__construct(
            error_message: $limitstring,
            error_code: 429,
        );
    }
}
