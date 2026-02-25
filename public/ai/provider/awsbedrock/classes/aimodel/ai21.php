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

use aiprovider_awsbedrock\model_definition;

/**
 * AI21 model catalog.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2026 Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai21 extends awsbedrock_base {
    /**
     * Get AI21 models.
     *
     * @return array<string, \aiprovider_awsbedrock\model_definition>
     */
    public static function get_models(): array {
        $settings = self::get_settings();
        return [
            'ai21.jamba-1-5-large-v1:0' => self::create_model(
                'ai21.jamba-1-5-large-v1:0',
                model_definition::MODEL_TYPE_TEXT,
                $settings,
            ),
            'ai21.jamba-1-5-mini-v1:0' => self::create_model(
                'ai21.jamba-1-5-mini-v1:0',
                model_definition::MODEL_TYPE_TEXT,
                $settings,
            ),
        ];
    }

    /**
     * AI21 Jamba settings.
     *
     * @return array
     */
    private static function get_settings(): array {
        return [
            'temperature' => self::setting(
                'settings_temperature',
                PARAM_FLOAT,
                'settings_temperature',
                ['min' => 0, 'max' => 2, 'default' => 1.0],
            ),
            'top_p' => self::setting(
                'settings_top_p',
                PARAM_FLOAT,
                'settings_top_p',
                ['min' => 0, 'max' => 1.0, 'default' => 1.0],
            ),
            'max_tokens' => self::setting(
                'settings_max_tokens',
                PARAM_INT,
                'settings_max_tokens',
                ['min' => 0, 'max' => 4096, 'default' => 4096],
            ),
            'stop' => self::setting(
                'settings_stop_sequences',
                PARAM_TEXT,
                'settings_stop_sequences',
            ),
            'frequency_penalty' => self::setting(
                'settings_frequency_penalty',
                PARAM_FLOAT,
                'settings_frequency_penalty',
                ['min' => 0, 'max' => 2.0, 'default' => 0],
            ),
            'presence_penalty' => self::setting(
                'settings_presence_penalty',
                PARAM_FLOAT,
                'settings_presence_penalty',
                ['min' => 0, 'max' => 5.0, 'default' => 0],
            ),
        ];
    }
}
