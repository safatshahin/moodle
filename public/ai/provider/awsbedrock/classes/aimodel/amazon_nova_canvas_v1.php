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
 * Amazon Nova Canvas AI model.
 *
 * @package    aiprovider_awsbedrock
 * @copyright  2025 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amazon_nova_canvas_v1 extends base implements awsbedrock_base {
    #[\Override]
    public function get_model_name(): string {
        return 'amazon.nova-canvas-v1:0';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return get_string("model_{$this->get_model_name()}", 'aiprovider_awsbedrock');
    }

    #[\Override]
    public function get_model_settings(): array {
        return [
            // Specifies how strongly the generated image should adhere to the prompt.
            // Use a lower value to introduce more randomness in the generation.
            'cfgScale' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_cfg_scale',
                    'component' => 'aiprovider_awsbedrock',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_cfg_scale',
                    'component' => 'aiprovider_awsbedrock',
                    'a' => ['min' => 1.1, 'max' => 10, 'default' => 6.5],
                ],
            ],
            // Determines the initial noise setting for the generation process.
            // Changing the seed value while leaving all other parameters the same will
            // produce a totally new image that still adheres to your prompt, dimensions, and other settings.
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
                    'a' => ['min' => 0, 'max' => 2147483646, 'default' => 12],
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
