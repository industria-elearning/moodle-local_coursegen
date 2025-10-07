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
 * External API for Tutor-IA chat message creation
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\external;

use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_datacurso\httpclient\tutoria_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class create_chat_message
 *
 * Creates a chat message and returns streaming URL for Tutor-IA responses.
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_chat_message extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'message' => new external_value(PARAM_RAW, 'User message', VALUE_REQUIRED),
            'meta' => new external_value(PARAM_RAW, 'Optional metadata (JSON)', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Create chat message and initialize Tutor-IA session.
     *
     * @param int $courseid Course ID.
     * @param string $message User message text.
     * @param string $meta Optional metadata as JSON string.
     * @return array Session data with streaming URL.
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function execute($courseid, $message, $meta = '{}'): array {
        global $CFG, $SITE;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'message' => $message,
            'meta' => $meta,
        ]);

        // Check if chat is enabled globally.
        if (!get_config('local_datacurso', 'enablechat')) {
            throw new \moodle_exception('error_api_not_configured', 'local_datacurso');
        }

        // Validate course access permissions.
        $context = \context_course::instance($params['courseid']);
        require_capability('moodle/course:view', $context);

        // Initialize Tutor-IA API client.
        $tutoriaapi = new tutoria_api();

        // Get or create session using site identifier.
        $siteid = self::get_site_identifier();
        $session = $tutoriaapi->start_session($siteid, $params['courseid']);

        if (!isset($session['ready']) || !$session['ready']) {
            throw new \moodle_exception('sessionnotready', 'local_datacurso');
        }

        // Parse metadata.
        $metaarray = json_decode($params['meta'], true);
        if ($metaarray === null) {
            $metaarray = [];
        }

        // Send message to Tutor-IA.
        $tutoriaapi->send_message($session['session_id'], $params['message'], $metaarray);

        // Build streaming URL with authentication.
        $streamurl = $tutoriaapi->get_stream_url($session['session_id']);
        $token = get_config('local_datacurso', 'tutoraitoken');

        // Add token as query parameter for SSE authentication.
        if (!empty($token)) {
            $streamurl .= '&token=' . urlencode($token);
        }

        return [
            'session_id' => $session['session_id'],
            'stream_url' => $streamurl,
            'expires_at' => time() + ($session['session_ttl_seconds'] ?? 604800),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'session_id' => new external_value(PARAM_TEXT, 'Tutor-IA session ID'),
            'stream_url' => new external_value(PARAM_URL, 'SSE streaming URL with authentication'),
            'expires_at' => new external_value(PARAM_INT, 'Session expiration timestamp'),
        ]);
    }

    /**
     * Generate a unique identifier for this Moodle site.
     *
     * @return string Site identifier hash.
     */
    private static function get_site_identifier(): string {
        global $CFG, $SITE;

        // Use hash of wwwroot + site ID for unique identification.
        return md5($CFG->wwwroot . '_' . $SITE->id);
    }
}
