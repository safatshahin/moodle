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
 * Claude 3.5 Sonnet V2 AI model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2025 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anthropic_claude_3_5_sonnet_v2 extends anthropic_claude_3_5_sonnet_v1 implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'anthropic.claude-3-5-sonnet-20241022-v2:0';
    }

    #[\Override]
    public function get_model_settings(): array {
        $settings = parent::get_model_settings();

        return array_merge($settings, [
            // Add the cross region inference setting.
            'cross_region_inference' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_cross_region_inference',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_TEXT,
                'help' => [
                    'identifier' => 'settings_cross_region_inference',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['default' => 'us.anthropic.claude-3-5-sonnet-20241022-v2:0'],
                ],
            ],
        ]);
    }
}
