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

use core_ai\aimodel\base;
use MoodleQuickForm;

/**
 * Mistral 7B Instruct AI model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2025 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mistral_7b_instruct_v0 extends base implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'mistral.mistral-7b-instruct-v0:2';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return get_string("model_{$this->get_model_name()}", 'aiprovider_awsbedrock');
    }

    #[\Override]
    public function get_model_settings(): array {
        return [
            // Temperature – Use a lower value to decrease randomness in responses.
            'temperature' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_temperature',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_temperature',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 0, 'max' => 1, 'default' => 0.5],
                ],
            ],
            // Top_p – Use a lower value to ignore less probable options and decrease the diversity of responses.
            'top_p' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_top_p',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_top_p',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 0, 'max' => 1, 'default' => 0.9],
                ],
            ],
            // Top_k – Only sample from the top K options for each subsequent token.
            // Use top_k to remove long tail low probability responses.
            'top_k' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_top_k',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_top_k',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 1, 'max' => 200, 'default' => 50],
                ],
            ],
            // Max token  – The maximum number of tokens to generate in the response. Maximum token limits are strictly enforced.
            'max_tokens' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_max_tokens',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_INT,
                'help' => [
                    'identifier' => 'settings_max_tokens',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 1, 'max' => 8192, 'default' => 512],
                ],
            ],
            // Stop Sequences – Specify a character sequence to indicate where the model should stop.
            'stop' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_stop_sequences',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_TEXT,
                'help' => [
                    'identifier' => 'settings_stop_sequences',
                    'component' => 'aiprovider_awsbedrock',
                ],
            ],
        ];
    }

    #[\Override]
    public function add_model_settings(MoodleQuickForm $mform): void {
        $settings = $this->get_model_settings();

        foreach ($settings as $key => $setting) {
            $mform->addElement(
                $setting['elementtype'],
                $key,
                get_string($setting['label']['identifier'], $setting['label']['component']),
            );
            $mform->setType($key, $setting['type']);
            if (isset($setting['help'])) {
                $mform->addHelpButton(
                    elementname: $key,
                    identifier: $setting['help']['identifier'],
                    component: $setting['help']['component'],
                    a: !empty($setting['help']['a']) ? $setting['help']['a'] : [],
                );
            }
        }
    }

    #[\Override]
    public function model_type(): int {
        return self::MODEL_TYPE_TEXT;
    }
}
