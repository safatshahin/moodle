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

namespace mod_quiz\task;

use stdClass;

/**
 * Helper for sending quiz related notifications.
 *
 * @package    mod_quiz
 * @copyright  2024 David Woloszyn <david.woloszyn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_notification_helper {

    /**
     * @var int Default date threshold of 48 hours.
     */
    private const DEFAULT_DATE_THRESHOLD = (DAYSECS * 2);

    /**
     * Get all quizzes that have an approaching open date (includes users and groups with open date overrides).
     *
     * @return array Returns the matching quiz records.
     */
    public static function get_quizzes_within_date_threshold(): array {
        global $DB;

        $timenow = time();
        $futuretime = $timenow + self::DEFAULT_DATE_THRESHOLD;

        $sql = "SELECT DISTINCT q.id AS quizid,
                       q.timeopen,
                       q.timeclose,
                       q.name AS quizname,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.id AS cmid
                  FROM {quiz} q
                  JOIN {course} c ON q.course = c.id
                  JOIN {course_modules} cm ON q.id = cm.instance
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
             LEFT JOIN {quiz_overrides} qo ON q.id = qo.quiz
                 WHERE (q.timeopen < :futuretime OR qo.timeopen < :qo_futuretime)
                   AND (q.timeopen > :timenow OR qo.timeopen > :qo_timenow);";

        $params = [
            'timenow' => $timenow,
            'futuretime' => $futuretime,
            'qo_timenow' => $timenow,
            'qo_futuretime' => $futuretime,
            'modulename' => 'quiz',
        ];

        return $DB->get_records_sql($sql, $params);
    }

    public static function get_quiz_overrides(int $quizid): array {
        global $DB;

        // Check for any override dates.
        $sql = "SELECT qo.userid,
                       qo.timeopen,
                       qo.timeclose,
                       qo.groupid
                  FROM {quiz_overrides} qo
                 WHERE quiz = :quizid
                   AND (timeopen < :futuretime OR timeclose < :futuretime)
                   AND (timeopen > :timenow OR timeclose > :timenow);";

        return $DB->get_records_sql($sql, [
            'quizid' => $quizid,
            'timenow' => time(),
            'futuretime' => time() + self::DEFAULT_DATE_THRESHOLD,
        ]);
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

    /**
     * Get all users that have an approaching open date within a quiz.
     *
     * @param stdClass $quiz The quiz data.
     * @return array The users after all filtering has been applied.
     */
    public static function get_users_within_quiz(stdClass $quiz): array {
        $modulecontext = \context_module::instance($quiz->cmid);
        $users = get_enrolled_users($modulecontext, 'mod/quiz:attempt', 0, 'u.id, u.firstname');
        $course = get_course($quiz->course);
        $overrides = self::get_quiz_overrides($quiz->quizid);

        foreach ($users as $key => $user) {
            // Time open and time close dates can be user specific with an override.
            // We begin by assuming it is the same as recorded in the quiz.
            $user->timeopen = $quiz->timeopen;
            $user->timeclose = $quiz->timeclose;
            // Set the override type to 'none' to begin with.
            $user->overridetype = 'none';

            // Check if the $user is in $overrides and get that user's override.
            array_map(static function($override) use ($user, $course) {
                if ($override->userid !== $user->id) {
                    return;
                }
                $user->timeopen = $override->timeopen;
                $user->timeclose = $override->timeclose;
                if (
                    $course->groupmode !== NOGROUPS
                    && !empty($override->groupid)
                    && groups_is_member($override->groupid, $user->id)
                ) {
                    $user->overridetype = 'group';
                } else {
                    $user->overridetype = 'user';
                }
            }, $overrides);

            // Check if the user has already received this notification.
            $match = [
                'quizid' => $quiz->quizid,
                'timeopen' => $user->timeopen,
                'overridetype' => $user->overridetype,
            ];
            if (
                self::has_user_been_sent_a_notification_already($user->id, json_encode($match, JSON_THROW_ON_ERROR))
            ) {
                unset($users[$key]);
            }
        }

        return $users;
    }

    /**
     * Send the notification to the user.
     *
     * @param stdClass $user The user data.
     * @param stdClass $quiz The quiz data.
     */
    public static function send_quiz_open_soon_notification(stdClass $user, stdClass $quiz): void {
        // URL to user's quiz.
        $urlparams = [
            'id' => $quiz->cmid,
            'action' => 'view',
        ];
        $url = new \moodle_url('/mod/quiz/view.php', $urlparams);

        $stringparams = [
            'firstname' => $user->firstname,
            'quizname' => $quiz->quizname,
            'coursename' => $quiz->coursename,
            'timeopen' => userdate($user->timeopen),
            'timeclose' => !empty($user->timeclose) ? userdate($user->timeclose) : '',
            'url' => $url,
        ];

        $messagedata = [
            'user' => \core_user::get_user($user->id),
            'url' => $url->out(false),
            'subject' => get_string('quizopendatesoonsubject', 'mod_quiz', $stringparams),
            'quizname' => $quiz->quizname,
            'html' => get_string('quizopendatesoonhtml', 'mod_quiz', $stringparams),
        ];

        // Prepare message object.
        $message = new \core\message\message();
        $message->component = 'mod_quiz';
        $message->name = 'quiz_open_soon';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $messagedata['user'];
        $message->subject = $messagedata['subject'];
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessage = html_to_text($messagedata['html']);
        $message->fullmessagehtml = $messagedata['html'];
        $message->smallmessage = $messagedata['subject'];
        $message->notification = 1;
        $message->contexturl = $messagedata['url'];
        $message->contexturlname = $messagedata['quizname'];
        // Use custom data to avoid future notifications being sent again.
        $message->customdata = [
            'quizid' => $quiz->quizid,
            'timeopen' => $user->timeopen,
            'overridetype' => $user->overridetype,
        ];

        message_send($message);
    }
}
