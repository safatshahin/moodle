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

namespace core\check;

use core_table\output\html_table_row;
use core\output\html_writer;

/**
 * A table of check results.
 *
 * @package    core
 * @category   check
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table implements \core\output\renderable {

    /**
     * @var \moodle_url $url
     */
    protected $url = '';

    /**
     * @var string $type What type of checks
     */
    protected $type = '';

    /**
     * @var check $detail a specific check to focus on
     */
    public $detail = '';

    /**
     * The name of the check that was requested.
     *
     * @var string
     */
    protected string $checkname = '';

    /**
     * @var array $checks shown in this table
     */
    public $checks = [];

    /**
     * Constructor
     *
     * @param string $type of check
     * @param string $url of report
     * @param string $detail check to focus on
     */
    public function __construct($type, $url, $detail = '') {
        // We may need a bit more memory and this may take a long time to process.
        \raise_memory_limit(MEMORY_EXTRA);
        \core_php_time_limit::raise();

        $this->type = $type;
        $this->url = $url;
        $this->checks = \core\check\manager::get_checks($type);
        $this->checkname = $detail;

        if ($detail) {
            $this->checks = array_filter($this->checks, function($check) use ($detail) {
                return $detail == $check->get_ref();
            });
            if (!empty($this->checks)) {
                $this->detail = reset($this->checks);
            }
        }
    }

    /**
     * Render a table of checks
     *
     * @param \core\output\renderer $output to use
     * @return string html output
     */
    public function render($output) {
        $html = '';

        $table = new \core_table\output\html_table();
        $waiting = $output->pix_icon('i/loading', get_string('loading'));

        $table->data = [];
        $table->head = [get_string('status')];
        $table->colclasses = ['rightalign status'];

        if (empty($this->checkname)) {
            $table->head[] = get_string('check');
            $table->colclasses[] = 'leftalign check';
        } else {
            $html .= html_writer::tag('h3', $this->detail->get_name());
        }

        $table->head[] = get_string('summary');
        $table->colclasses[] = 'leftalign summary';
        $table->head[] = get_string('action');
        $table->colclasses[] = 'leftalign action';

        $table->id = $this->type . 'reporttable';
        $table->attributes = ['class' => 'admintable ' . $this->type . 'report table generaltable checktable'];

        $fails = [];
        foreach ($this->checks as $check) {
            $ref = $check->get_ref();
            $actionlink = $check->get_action_link();

            $link = new \moodle_url($this->url, ['detail' => $ref]);

            $row = [];
            $row[] = html_writer::tag('span', $waiting, ['class' => 'status']);

            if (empty($this->checkname)) {
                $row[] = $output->action_link($link, $check->get_name());
            }

            $row[] = html_writer::tag('span', $waiting, ['class' => 'summarytext'])
                . '<br>'
                . html_writer::start_tag('small')
                . $output->action_link($link, get_string('moreinfo'))
                . html_writer::end_tag('small');
            if ($actionlink) {
                $row[] = $output->render($actionlink);
            } else {
                $row[] = '';
            }

            $tablerow = new html_table_row($row);
            $tablerow->id = 'row_' . $ref;
            $table->data[] = $tablerow;
        }

        $html .= html_writer::table($table);

        if ($this->detail) {
            // Just render a placeholder for the details.
            $html .= $output->heading(get_string('details'), 3);
            $loading = html_writer::tag('div', '', ['class' => 'bg-pulse-grey', 'style' => 'min-height: 4em']);
            $html .= $output->box($loading, 'generalbox boxwidthnormal boxaligncenter', 'checkdetails');
            $html .= $output->continue_button($this->url);
        }

        return $html;
    }


    /**
     * Runs the checks asynchronously
     * @param renderer $output page renderer
     * @return void
     */
    public function run_checks($output): void {
        foreach ($this->checks as $check) {
            $ref = $check->get_ref();
            $id = 'row_' . $ref;
            $link = new \moodle_url($this->url, ['detail' => $ref]);

            if ($this->detail) {
                // Detail page: call get_results() to preserve the per-sub-result breakdown.
                // The placeholder has one <tr>; replace its outerHTML with one row per result.
                $results = $check->get_results();
                $fails = [];
                $rowshtml = '';

                foreach ($results as $result) {
                    if ($result->get_status() !== result::OK) {
                        $fails[] = $result;
                    }

                    $actionlink = $result->get_action_link() ?? $check->get_action_link();
                    $cells  = html_writer::tag('td', $output->check_result($result), ['class' => 'rightalign status']);
                    $cells .= html_writer::tag('td',
                        $result->get_summary()
                        . '<br>'
                        . html_writer::start_tag('small')
                        . $output->action_link($link, get_string('moreinfo'))
                        . html_writer::end_tag('small'),
                        ['class' => 'leftalign summary'],
                    );
                    $cells .= html_writer::tag('td',
                        $actionlink ? $output->render($actionlink) : '',
                        ['class' => 'leftalign action'],
                    );
                    $rowshtml .= html_writer::tag('tr', $cells);
                }

                // Replace the single placeholder row with all sub-result rows.
                echo $output->select_element_for_replace("#$id", $rowshtml, true);

                // Build and stream the details section from all failing sub-results.
                $details = array_filter(array_map(fn($r) => $r->get_details(), $fails));
                if (count($details) === 1) {
                    $detailhtml = reset($details);
                } elseif (count($details) > 1) {
                    $detailhtml  = html_writer::start_tag('ul');
                    foreach ($details as $detail) {
                        $detailhtml .= html_writer::tag('li', $detail);
                    }
                    $detailhtml .= html_writer::end_tag('ul');
                } else {
                    $detailhtml = '';
                }
                echo $output->select_element_for_replace('#checkdetails', $detailhtml);

            } else {
                // On the summmary page all checks has one result, even if an aggregate result
                // of many results like the router check.
                $result = $check->get_result();
                echo $output->select_element_for_replace("#$id .status", $output->check_result($result));
                echo $output->select_element_for_replace("#$id .summarytext", $result->get_summary());
            }

            flush();
        }
    }
}
