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

/**
 * Fixture for the keepalive cron tests.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2026 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core;

defined('MOODLE_INTERNAL') || die();

/**
 * Testable subclass of cron used to verify the keepalive scheduling behaviour.
 *
 * It overrides run_scheduled_tasks(), run_adhoc_tasks(), sleep(), and
 * get_next_scheduled_task_time() so that:
 *   - The two task-runner methods record their invocations rather than executing real tasks.
 *   - sleep() advances the injected fake clock instead of blocking the process.
 *   - get_next_scheduled_task_time() returns controlled values without hitting the database.
 *
 * For these overrides to be reachable from run_main_process() the production code must
 * call all four via static:: (late static binding) rather than self:: or bare built-ins.
 *
 * @package core
 * @copyright 2026 Brendan Heywood <brendan@catalyst-au.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_keepalive_cron extends cron {
    /** @var array Ordered log of every method invocation. */
    public static array $methodcalls = [];

    /**
     * Queue of values to return from get_next_scheduled_task_time(), one per call.
     * If the queue is empty, PHP_INT_MAX is returned (no scheduled tasks due).
     *
     * @var int[]
     */
    public static array $nextscheduledtimes = [];

    /**
     * Reset all recorded invocations and the next-scheduled-times queue.
     */
    public static function reset_tracking(): void {
        self::$methodcalls = [];
        self::$nextscheduledtimes = [];
    }

    /**
     * Record the call rather than running real scheduled tasks.
     *
     * @param int $startruntime
     * @param int|null $startprocesstime
     */
    public static function run_scheduled_tasks(
        int $startruntime,
        ?int $startprocesstime = null,
    ): void {
        self::$methodcalls[] = ['method' => 'run_scheduled_tasks', 'time' => $startruntime];
    }

    /**
     * Record the call rather than running real adhoc tasks.
     *
     * @param int $startruntime
     * @param int $keepalive
     * @param bool $checklimits
     * @param int|null $startprocesstime
     * @param int|null $maxtasks
     * @param string|null $classname
     */
    public static function run_adhoc_tasks(
        int $startruntime,
        $keepalive = 0,
        $checklimits = true,
        ?int $startprocesstime = null,
        ?int $maxtasks = null,
        ?string $classname = null,
    ): void {
        self::$methodcalls[] = ['method' => 'run_adhoc_tasks', 'time' => $startruntime];
    }

    /**
     * Advance the injected clock instead of blocking the process.
     *
     * @param int $seconds
     */
    protected static function sleep(int $seconds): void {
        \core\di::get(\core\clock::class)->bump($seconds);
    }

    /**
     * Return the next scheduled time from the pre-configured queue rather than
     * querying the database. Returns PHP_INT_MAX when the queue is exhausted
     * (no scheduled tasks due within any keepalive window).
     *
     * @return int|null
     */
    protected static function get_next_scheduled_task_time(): ?int {
        self::$methodcalls[] = ['method' => 'get_next_scheduled_task_time'];
        if (!empty(self::$nextscheduledtimes)) {
            return array_shift(self::$nextscheduledtimes);
        }
        return PHP_INT_MAX;
    }
}
