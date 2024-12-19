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

namespace core_message\hook;

use stdClass;

/**
 * Check if the notification provider supports the notification processor.
 *
 * @package    core_message
 * @copyright  2024 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @property-read stdClass $processor The notification processor
 * @property-read stdClass $provider The notification provider
 */
#[\core\attribute\label('Allows notification providers components to mention if they do not support a notification processor.')]
#[\core\attribute\tags('message')]
class check_processor_support {

    private bool $processorsupported = false;

    /**
     * Constructor for the hook.
     *
     * @param stdClass $processor The processor object.
     * @param stdClass $provider The provider object.
     */
    public function __construct(
        public readonly stdClass $processor,
        public readonly stdClass $provider,
    ) {
    }

    /**
     * Check if the processor is supported.
     *
     * @return bool
     */
    public function is_processor_supported(): bool {
        if ($this->processor->name !== 'sms') {
            return true;
        }
        return $this->processorsupported;
    }

    /**
     * Set the support of the processor if supported.
     *
     * @param bool $processorsupported True or false.
     */
    public function set_processor_support(bool $processorsupported = true): void {
        $this->processorsupported = $processorsupported;
    }
}
