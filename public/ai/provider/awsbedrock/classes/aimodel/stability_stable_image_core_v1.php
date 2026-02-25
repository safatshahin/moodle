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
 * Stability AI Stable Image Core model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2026 Raquel Ortega <raquel.ortega@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stability_stable_image_core_v1 extends base implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'stability.stable-image-core-v1:1';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return get_string("model_{$this->get_model_name()}", 'aiprovider_awsbedrock');
    }

    #[\Override]
    public function get_model_settings(): array {
        return [
            // A specific value that is used to guide the 'randomness' of the generation.
            // (Omit this parameter or pass 0 to use a random seed.)
            'seed' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_seed_img',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_INT,
                'help' => [
                    'identifier' => 'settings_seed_img',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 0, 'max' => 4294967295, 'default' => 0],
                ],
            ],
            // Keywords of what you do not wish to see in the output image. Max: 10.000 characters.
            'negative_prompt' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_negative_prompt_img',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_TEXT,
                'help' => [
                    'identifier' => 'settings_negative_prompt_img',
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
        return self::MODEL_TYPE_IMAGE;
    }
}
