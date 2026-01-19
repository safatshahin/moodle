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
 * AI21 Labs Jamba 1.5 Large AI model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2025 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai21_jamba_1_5_large_v1 extends base implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'ai21.jamba-1-5-large-v1:0';
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
                    'a' => ['min' => 0, 'max' => 2, 'default' => 1.0],
                ],
            ],
            // Top P – Limit the pool of next tokens in each step to the top N percentile of possible tokens,
            // where 1.0 means the pool of all possible tokens, and 0.01 means the pool of only the most likely next tokens.
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
                    'a' => ['min' => 0, 'max' => 1.0, 'default' => 1.0],
                ],
            ],
            // Max token – The maximum number of tokens to generate in the response. Maximum token limits are strictly enforced.
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
                    'a' => ['min' => 0, 'max' => 4096, 'default' => 4096],
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
            // Frequency Penalty - Reduce frequency of repeated words within a single response message by increasing this number.
            // This penalty gradually increases the more times a word appears during response generation.
            'frequency_penalty' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_frequency_penalty',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_frequency_penalty',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 0, 'max' => 2.0, 'default' => 0],
                ],
            ],
            // Presence Penalty - Reduce the frequency of repeated words within a single message by increasing this number.
            // Unlike frequency penalty, presence penalty is the same no matter how many times a word appears.
            'presence_penalty' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_presence_penalty',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_presence_penalty',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 0, 'max' => 5.0, 'default' => 0],
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
