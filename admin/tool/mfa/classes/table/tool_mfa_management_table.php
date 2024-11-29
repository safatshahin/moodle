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

namespace tool_mfa\table;

/**
 * Table to manage factor plugins.
 *
 * @package     tool_mfa
 * @copyright   Meirza <meirza.arson@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_mfa_management_table extends \core_admin\table\plugin_management_table {
    #[\Override]
    protected function get_plugintype(): string {
        return 'factor';
    }

    #[\Override]
    public function guess_base_url(): void {
        $this->define_baseurl(
            new \moodle_url('/admin/settings.php', ['section' => 'managemfa'])
        );
    }

    #[\Override]
    protected function get_action_url(array $params = []): \moodle_url {
        return new \moodle_url('/admin/tool/mfa/index.php', $params);
    }

    #[\Override]
    protected function get_dynamic_table_html_end(): string {
        global $PAGE;
        $PAGE->requires->js_call_amd('tool_mfa/factor_combinations_table', 'init', [$this->get_table_id()]);

        return parent::get_dynamic_table_html_end();
    }

    #[\Override]
    protected function get_column_list(): array {
        $columns = [
            'namedesc' => get_string('factor', 'tool_mfa'),
        ];

        if ($this->supports_disabling()) {
            $columns['enabled'] = get_string('pluginenabled', 'core_plugin');
        }

        if ($this->supports_ordering()) {
            $columns['order'] = get_string('order', 'core');
        }

        $columns['weight'] = get_string('weight', 'tool_mfa');
        $columns['settings'] = get_string('settings', 'core');

        return $columns;
    }

    #[\Override]
    protected function col_settings(\stdClass $row): string {
        if ($settingsurl = $row->plugininfo->get_settings_url()) {
            $factor = $row->plugininfo->get_factor($row->plugininfo->name);
            return \html_writer::link(
                url: $settingsurl,
                text: get_string('settings'),
                attributes: ["title" => get_string('editfactor', 'tool_mfa', $factor->get_display_name())],
            );
        }

        return '';
    }

    /**
     * Show the name & description column content.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_namedesc(\stdClass $row): string {
        global $OUTPUT;
        $factor = $row->plugininfo->get_factor($row->plugininfo->name);
        $params = [
            'name' => $factor->get_display_name(),
            'description' => $factor->get_short_description(),
        ];

        return $OUTPUT->render_from_template('core_admin/table/namedesc', $params);
    }

    /**
     * Show the weight column content.
     *
     * @param \stdClass $row
     * @return string
     */
    protected function col_weight(\stdClass $row): string {
        $factor = $row->plugininfo->get_factor($row->plugininfo->name);
        return $factor->get_weight();
    }
}
