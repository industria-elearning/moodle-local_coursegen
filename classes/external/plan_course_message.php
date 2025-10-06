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
 * External API for sending messages to AI course planning sessions.
 *
 * @package    local_datacurso
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\external;

use aiprovider_datacurso\httpclient\ai_course_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use moodle_exception;
use local_datacurso\local\streaming_helper;

/**
 * External API for sending messages to AI course planning sessions.
 */
class plan_course_message extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'text' => new external_value(PARAM_TEXT, 'Message text to send to AI'),
        ]);
    }

    /**
     * Send message to AI course planning session.
     *
     * @param int $courseid Course ID
     * @param string $text Message text
     * @return array Result of the message sending
     * @throws moodle_exception
     */
    public static function execute($courseid, $text) {
        global $CFG, $DB, $USER;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'courseid' => $courseid,
                'text' => $text,
            ]);

            $courseid = $params['courseid'];
            $text = $params['text'];

            // Validate context and permissions.
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $context = context_course::instance($courseid);
            self::validate_context($context);
            // Check if user has permission to edit the course.
            require_capability('moodle/course:update', $context);

            // Validate that a session exists for this user and course.
            $session = $DB->get_record('local_datacurso_course_sessions', [
                'courseid' => $courseid,
                'userid' => $USER->id,
            ]);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => get_string('error_no_session_found', 'local_datacurso', $courseid),
                    'status' => null,
                ];
            }

            // Get API configuration.
            $apitoken = get_config('local_datacurso', 'apitoken');
            $baseurl = get_config('local_datacurso', 'baseurl');

            if (empty($apitoken) || empty($baseurl)) {
                return [
                    'success' => false,
                    'message' => get_string('error_api_not_configured', 'local_datacurso'),
                    'status' => null,
                ];
            }

            // Prepare the request data.
            $requestdata = [
                'session_id' => $session->session_id,
                'text' => $text,
            ];

            $client = new ai_course_api();
            $result = $client->request('POST', '/planning/plan-course/message', $requestdata);

            // Build streaming URL and return success response with API status.
            $streamingurl = $client->get_streaming_url_for_session($session->session_id);
            return [
                'success' => true,
                'message' => get_string('message_sent_successfully', 'local_datacurso'),
                'data' => [
                    'status' => $result['status'],
                    'streamingurl' => $streamingurl,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Returns description of method return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'data' => new external_single_structure([
                'status' => new external_value(PARAM_TEXT, 'API status response', VALUE_OPTIONAL),
                'streamingurl' => new external_value(PARAM_URL, 'Streaming URL to reconnect', VALUE_OPTIONAL),
            ], 'Api response data', VALUE_OPTIONAL),
        ]);
    }
}
