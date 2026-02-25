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
 * Meta Llama 3.1 405B Instruct AI model
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2026 Raquel Ortega <raquel.ortega@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meta_llama3_1_405b_instruct_v1 extends meta_llama3_8b_instruct_v1 implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'meta.llama3-1-405b-instruct-v1:0';
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
                    'a' => ['default' => 'us.meta.llama3-1-405b-instruct-v1:0'],
                ],
            ],
        ]);
    }
}
