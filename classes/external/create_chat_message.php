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
 * External API for getting response ia
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\external;

use context_course;
use core_date;
use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use external_multiple_structure;
use local_datacurso\httpclient\datacurso_ai_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class create_chat_message
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class create_chat_message extends external_api
{
    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 1),
            'lang' => new external_value(PARAM_TEXT, 'Language code', VALUE_DEFAULT, 'es'),
            'message' => new external_value(PARAM_RAW, 'Message or field id', VALUE_DEFAULT, 't63'),
        ]);
    }

    /**
     * Make request to DataCurso backend to get AI response for the given course/module/message.
     *
     * @param int $courseid Course ID.
     * @param string $lang Language code (e.g. 'es', 'en').
     * @param string $message Message text or field identifier.
     * @return array Associative array containing the AI response HTML.
     * @throws \invalid_parameter_exception
     */
    public static function execute($courseid, $lang, $message): array {
        GLOBAL $USER, $COURSE;
        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'lang' => $lang,
            'message' => $message,
        ]);

        $datacursoaiapi = new datacurso_ai_api();

        $user = [
            'email' => $USER->email,
            'username' => $USER->username,
            'fullName' => $USER->firstname . ' ' . $USER->lastname,
            'tenantId' => get_config('local_datacurso', 'tenantid'),
            'token' => get_config('local_datacurso', 'tenanttoken'),
        ];

        $tenantId = get_config('local_datacurso', 'tenantid');
        $token = get_config('local_datacurso', 'token');
        $role = (new create_chat_message)->get_user_role_in_course($USER->id, $COURSE->id ?? 0);

        $payload = [
            'message' => $params['message'],
            'user' => $user,
            'context' => [
                'tenant' => $tenantId,
                'course_id' => $COURSE->id ?? null,
                'role' => $role,
                'activity' => [
                    'cmid' => 0,
                    'type' => 0,
                    'name' => 0
                ],
                'locale' => current_language(),
                'timezone' => core_date::get_user_timezone($USER)
            ],
            'trace_id' => \core\uuid::generate()
        ];

        $response = $datacursoaiapi->post('/v1/chats', $payload, 'http://docker.for.mac.host.internal:1337/api');

        return [
            'sessionid' => $response['session_id'] ?? '',
            'streamurl' => $response['stream_url'] ?? '',
            'expiresat' => $response['expires_at'] ?? ''
        ];

    }

    /**
     * Returns description of method result value.
     *
     * @return \external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_TEXT, 'Chat session ID'),
            'streamurl' => new external_value(PARAM_TEXT, 'Streaming URL for chat responses'),
            'expiresat' => new external_value(PARAM_TEXT, 'Expiration timestamp for the chat session'),
        ]);
    }

    /**
     * Obtiene el rol principal del usuario en el contexto de un curso.
     *
     * @param int $userid
     * @param int $courseid
     * @return string 'teacher', 'student' o 'guest'
     */
    function get_user_role_in_course($userid, $courseid): string {
        $context = \context_course::instance($courseid);
        $roles = get_user_roles($context, $userid);

        foreach ($roles as $userrole) {
            if ($userrole->shortname === 'editingteacher' || $userrole->shortname === 'teacher') {
                return 'teacher';
            } else if ($userrole->shortname === 'student') {
                return 'student';
            }
        }
        return 'guest';
    }

}