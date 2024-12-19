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

namespace mod_assign;

use core_message\hook\check_processor_support;

/**
 * Hook listener assign.
 *
 * @package    mod_assign
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Set the SMS support for Assign.
     *
     * @param check_processor_support $hook The processor support hook.
     */
    public static function set_sms_support_for_assign(check_processor_support $hook): void {
        if ($hook->processor->name === 'sms' && $hook->provider->component === 'mod_assign') {
            $hook->set_processor_support();
        }
    }

}
