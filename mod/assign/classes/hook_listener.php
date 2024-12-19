<?php

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

    public static function set_sms_support_for_assign(check_processor_support $hook) {
        if ($hook->processor->name === 'sms' && $hook->provider->component === 'mod_assign') {
            $hook->set_processor_support();
        }
    }

}
