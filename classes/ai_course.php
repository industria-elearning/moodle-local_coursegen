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

namespace local_datacurso;

use aiprovider_datacurso\httpclient\ai_course_api;


/**
 * AI Course class for managing AI-generated course planning sessions.
 *
 * @package    local_datacurso
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_course {
    /**
     * Start AI course planning session by calling the /plan-course/start endpoint..
     *
     * @param int $courseid Course ID
     * @param string $contexttype Context type (model or syllabus)
     * @param string $modelname Model name for AI processing
     * @param string $coursename Course name
     * @return array Response from the AI service
     */
    public static function start_course_planning($courseid, $contexttype, $modelname, $coursename) {
        global $CFG, $DB;

        try {
            // Prepare request data.
            $requestdata = [
                'course_id' => $courseid,
                'site_id' => md5($CFG->wwwroot),
                'context_type' => $contexttype,
                'model_name' => $modelname,
                'course_name' => $coursename,
            ];

            // This request may take a long time depending on the AI processing..
            \core_php_time_limit::raise();
            raise_memory_limit(MEMORY_EXTRA);
            // Release the session so other tabs in the same session are not blocked.
            \core\session\manager::write_close();

            $client = new ai_course_api();
            $result = $client->request('POST', '/course/start', $requestdata);

            if (!isset($result['session_id'])) {
                return [
                    'ok' => false,
                    'message' => get_string('error_starting_course_planning', 'local_datacurso'),
                    'log' => "Invalid response from AI service. Response: " . json_encode($result),
                ];
            }

            $sessionid = $result['session_id'];

            // Store session_id in database.
            $success = self::save_course_session($courseid, $sessionid);

            if (!$success) {
                return [
                    'ok' => false,
                    'message' => get_string('error_saving_session', 'local_datacurso'),
                    'log' => 'Failed to save session ID to database',
                ];
            }

            return [
                'ok' => true,
                'session_id' => $sessionid,
                'message' => get_string('course_planning_started', 'local_datacurso'),
            ];
        } catch (\Exception $e) {
            debugging("Unexpected error while starting course planning: " . $e->getMessage());
            return [
                'ok' => false,
                'message' => get_string('error_starting_course_planning', 'local_datacurso'),
                'log' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save course planning session to database.
     *
     * @param int $courseid Course ID
     * @param string $sessionid Session ID from AI service
     * @return bool Success status
     */
    public static function save_course_session($courseid, $sessionid) {
        global $DB, $USER;

        try {
            // Check if a session already exists for this course.
            $existingsession = $DB->get_record('local_datacurso_course_sessions', ['courseid' => $courseid]);

            $sessiondata = new \stdClass();
            $sessiondata->courseid = $courseid;
            $sessiondata->session_id = $sessionid;
            $sessiondata->userid = $USER->id;
            // Status: 1 planning, 2 creating, 3 created, 4 failed.
            $sessiondata->status = 1;
            $sessiondata->timemodified = time();

            if ($existingsession) {
                // Update existing session.
                $sessiondata->id = $existingsession->id;
                return $DB->update_record('local_datacurso_course_sessions', $sessiondata);
            } else {
                // Create new session record.
                $sessiondata->timecreated = time();
                return $DB->insert_record('local_datacurso_course_sessions', $sessiondata);
            }
        } catch (\Exception $e) {
            debugging("Error saving course session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course planning session by course ID.
     *
     * @param int $courseid Course ID
     * @return object|false Session record or false if not found
     */
    public static function get_course_session($courseid) {
        global $DB;

        try {
            return $DB->get_record('local_datacurso_course_sessions', ['courseid' => $courseid]);
        } catch (\Exception $e) {
            debugging("Error getting course session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update course session status.
     *
     * @param int $courseid Course ID
     * @param string $status New status (planning, completed, failed)
     * @return bool Success status
     */
    public static function update_session_status($courseid, $status) {
        global $DB;

        try {
            $session = self::get_course_session($courseid);
            if (!$session) {
                return false;
            }

            $session->status = $status;
            $session->timemodified = time();

            return $DB->update_record('local_datacurso_course_sessions', $session);
        } catch (\Exception $e) {
            debugging("Error updating session status: " . $e->getMessage());
            return false;
        }
    }
}
