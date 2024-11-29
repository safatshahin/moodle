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

namespace tool_mfa\local;

use tool_mfa\local\factor\object_factor_base;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting to show available combinations of factors.
 *
 * @package     tool_mfa
 * @author      Mikhail Golenkov <golenkovm@gmail.com>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_factor_combinations extends \admin_setting {

    /**
     * Calls parent::__construct with specific arguments.
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('mfaui', get_string('mfasettings', 'tool_mfa'), '', '');
    }

    #[\Override]
    public function get_setting(): bool {
        return true;
    }

    #[\Override]
    public function write_setting($data): string {
        return '';
    }

    #[\Override]
    public function output_html($data, $query=''): string {
        global $OUTPUT;

        $return = $OUTPUT->heading(get_string('settings:combinations', 'tool_mfa'), 3);
        $return .= $OUTPUT->box_start('generalbox', 'mfacombinations-wrapper');
        $return .= $this->define_factor_combinations_table();
        $return .= $OUTPUT->box_end();

        return highlight($query, $return);
    }

    /**
     * Defines supplementary table that shows available combinations of factors enough for successful authentication.
     *
     * @return string HTML code
     */
    public function define_factor_combinations_table(): string {
        global $OUTPUT;

        $factors = \tool_mfa\plugininfo\factor::get_enabled_factors();
        $combinations = $this->get_factor_combinations($factors, 0, count($factors) - 1);

        if (empty($combinations)) {
            return $OUTPUT->notification(get_string('error:notenoughfactors', 'tool_mfa'), 'notifyproblem');
        }

        $txt = get_strings(['combination', 'totalweight'], 'tool_mfa');
        $table = new \html_table();
        $table->id = 'mfacombinations';
        $table->attributes['class'] = 'admintable generaltable table table-bordered';
        $table->head  = [$txt->combination, $txt->totalweight];
        $table->data  = [];

        $factorstringconnector = get_string('connector', 'tool_mfa');
        foreach ($combinations as $combination) {
            $factorstrings = array_map(static function(object_factor_base $factor): string {
                return $factor->get_summary_condition() . ' <sup>' . $factor->get_weight() . '</sup>';
            }, $combination['combination']);

            $string = implode(" {$factorstringconnector} ", $factorstrings);
            $table->data[] = new \html_table_row([$string, $combination['totalweight']]);
        }

        return \html_writer::table($table);
    }

    /**
     * Recursive method to get all possible combinations of given factors.
     * Output is filtered by combination total weight (should be greater than 100).
     *
     * @param array $allfactors initial array of factor objects
     * @param int $start start position in initial array
     * @param int $end end position in initial array
     * @param int $totalweight total weight of combination
     * @param array $combination combination candidate
     * @param array $result array that includes combination total weight and subarray of factors combination
     *
     * @return array
     */
    public function get_factor_combinations($allfactors, $start = 0, $end = 0,
        $totalweight = 0, $combination = [], $result = []): array {

        if ($totalweight >= 100) {
            // Ensure this is a valid combination before appending result.
            $valid = true;
            foreach ($combination as $factor) {
                if (!$factor->check_combination($combination)) {
                    $valid = false;
                }
            }
            if ($valid) {
                $result[] = ['totalweight' => $totalweight, 'combination' => $combination];
            }
            return $result;
        } else if ($start > $end) {
            return $result;
        }

        $combinationnext = $combination;
        $combinationnext[] = $allfactors[$start];

        $result = $this->get_factor_combinations(
            $allfactors,
            $start + 1,
            $end,
            $totalweight + $allfactors[$start]->get_weight(),
            $combinationnext,
            $result);

        $result = $this->get_factor_combinations(
            $allfactors,
            $start + 1,
            $end,
            $totalweight,
            $combination,
            $result);

        return $result;
    }
}
