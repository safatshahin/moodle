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

/**
 * Error message base class.
 *
 * @package    core_ai
 * @copyright  2024 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ai_error_message {

    public function __construct(
        protected readonly string $error_message,
        protected readonly string $error_code,
    ) {
    }

    public function get_error_message(): string {
        return $this->error_message;
    }

    public function get_error_code(): string {
        return $this->error_code;
    }

    public function get_full_error_message(): string {
        return $this->get_error_code() . ':' . $this->get_error_message();
    }

    public function get_minimal_error_message(): string {
        return "An error occurred while sending the request, please contact your side administrator for more information. Error code: " . $this->get_error_code(); // From string definitely.
    }
}
