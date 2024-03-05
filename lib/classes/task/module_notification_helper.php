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

namespace core\task;

use stdClass;

/**
 * Helper for sending module related notifications to a filtered set of users.
 *
 * @package    core
 * @copyright  2024 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_notification_helper {
    /**
     * @var int Default date threshold of 48 hours.
     */
    private const DEFAULT_DATE_THRESHOLD = (DAYSECS * 2);

    /**
     * @var string Override type of 'group'.
     */
    private const OVERRIDE_TYPE_GROUP = 'group';

    /**
     * @var string Override type of 'user'.
     */
    private const OVERRIDE_TYPE_USER = 'user';

    /**
     * @var string Override type of 'none'.
     */
    public const OVERRIDE_TYPE_NONE = 'none';

    /**
     * Get the date threshold.
     *
     * @param int|null $date Provide a date as the threshold (optional).
     * @return int The timenow value plus the date threshold.
     */
    public static function get_date_threshold(?int $date = null): int {
        $date = $date ?? self::DEFAULT_DATE_THRESHOLD;
        return time() + $date;
    }

    /**
     * Check if a date is within the current timenow value and the date threshold.
     *
     * @param int $date Date as timestamp.
     * @return boolean
     */
    public static function is_date_within_threshold(int $date): bool {
        return ($date > time() && $date < self::get_date_threshold());
    }

    /**
     * Update user's recorded date based on the override date.
     * In the case of an assignment, this could be the due date.
     * In the case of a quiz, this could be the time open date.
     *
     * @param array $overrides The overrides to check.
     * @param stdClass $user The user records we will be updating.
     * @param string $datekey Key to match on both records.
     * @return array $users Return the updated array.
     */
    public static function update_user_with_date_overrides(array $overrides, stdClass &$user, string $datekey): stdClass {
        global $DB;

        foreach ($overrides as $override) {
            // Group overrides.
            if (!empty($override->groupid) && !empty($override->$datekey)) {
                $groupmembers = $DB->get_records('groups_members', ['groupid' => $override->groupid]);
                foreach ($groupmembers as $groupmember) {
                    if ($user->id != $groupmember->userid) {
                        continue;
                    }
                    $user->$datekey = $override->$datekey;
                    $user->overridetype = self::OVERRIDE_TYPE_GROUP;
                }
            }
            // User overrides.
            if (!empty($override->userid) && !empty($override->$datekey)) {
                if ($user->id != $override->userid) {
                    continue;
                }
                $user->$datekey = $override->$datekey;
                $user->overridetype = self::OVERRIDE_TYPE_USER;
            }
        }

        return $user;
    }

    /**
     * Check if a user has been sent a notification already.
     *
     * @param int $userid The user id.
     * @param string $match The custom data string to match on.
     * @return bool Returns true if already sent.
     */
    public static function has_user_been_sent_a_notification_already(int $userid, string $match): bool {
        global $DB;

        $sql = "SELECT COUNT(n.id)
                  FROM {notifications} n
                 WHERE " . $DB->sql_compare_text('n.customdata') . " = " . $DB->sql_compare_text(':match') . "
                   AND n.useridto = :userid";

        $result = $DB->count_records_sql($sql, ['userid' => $userid, 'match' => $match]);

        return ($result > 0);
    }
}
