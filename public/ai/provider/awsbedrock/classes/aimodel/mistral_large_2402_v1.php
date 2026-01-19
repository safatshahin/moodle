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

namespace aiprovider_awsbedrock\aimodel;

/**
 * Mistral Large AI model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2025 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mistral_large_2402_v1 extends mistral_7b_instruct_v0 implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'mistral.mistral-large-2402-v1:0';
    }

    #[\Override]
    public function get_model_settings(): array {
        $settings = parent::get_model_settings();
        if (isset($settings['temperature']['help']['a']['default'])) {
            $settings['temperature']['help']['a']['default'] = 0.7;
        }
        if (isset($settings['top_p']['help']['a']['default'])) {
            $settings['top_p']['help']['a']['default'] = 1;
        }
        if (isset($settings['top_k']['help']['a']['default'])) {
            $settings['top_k']['help']['a']['default'] = get_string('none', 'aiprovider_awsbedrock');
        }
        if (isset($settings['max_tokens']['help']['a']['default'])) {
            $settings['max_tokens']['help']['a']['default'] = 8192;
        }
        return $settings;
    }
}
