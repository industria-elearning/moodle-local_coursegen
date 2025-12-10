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

namespace local_coursegen;

use aiprovider_datacurso\httpclient\ai_course_api;


/**
 * AI Course class for managing AI-generated course planning sessions.
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_course {
    /**
     * Start AI course planning session by calling the /plan-course/start endpoint..
     *
     * @param int $courseid Course ID
     * @param string $contexttype Context type (model, syllabus or customprompt)
     * @param string|null $modelname Model name for AI processing
     * @param string $coursename Course name
     * @param string|null $promptmessage Plain text prompt summary when context type is customprompt
     * @param int $generateimages 1 indicates AI could generate images, 0 indicates AI could not generate images
     */
    public static function start_course_planning(
        int $courseid,
        string $contexttype,
        ?string $modelname,
        string $coursename,
        ?string $promptmessage = null,
        int $generateimages = 0
    ): void {
        global $CFG;

        // Prepare request data.
        $requestdata = [
            'course_id' => $courseid,
            'site_id' => md5($CFG->wwwroot),
            'context_type' => $contexttype,
            'course_name' => $coursename,
        ];

        if (!empty($modelname)) {
            $requestdata['model_name'] = $modelname;
        }

        if ($contexttype === ai_context::CONTEXT_TYPE_CUSTOM_PROMPT) {
            if (!empty($promptmessage)) {
                $requestdata['prompt_message'] = $promptmessage;
            }
        }

        // Whether the AI service should generate images for this course.
        $requestdata['generate_images'] = $generateimages === 1;

        $client = new ai_course_api();
        $result = $client->request('POST', '/course/v2/start', $requestdata);

        if (!isset($result['session_id'])) {
            throw new \moodle_exception('error_starting_course_planning', 'local_coursegen');
        }

        $sessionid = $result['session_id'];

        // Store session_id in database.
        $success = self::save_course_session($courseid, $sessionid);

        if (!$success) {
            throw new \moodle_exception('error_saving_session', 'local_coursegen');
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
            $existingsession = $DB->get_record('local_coursegen_course_sessions', ['courseid' => $courseid]);

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
                return $DB->update_record('local_coursegen_course_sessions', $sessiondata);
            } else {
                // Create new session record.
                $sessiondata->timecreated = time();
                return $DB->insert_record('local_coursegen_course_sessions', $sessiondata);
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
            return $DB->get_record('local_coursegen_course_sessions', ['courseid' => $courseid]);
        } catch (\Exception $e) {
            debugging("Error getting course session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the status of a course session.
     *
     * @param int $sessionid Session ID
     * @param int $status Status code (1=planning, 2=creating, 3=created, 4=failed)
     */
    public static function update_session_status($sessionid, $status) {
        global $DB;

        $updatedata = new \stdClass();
        $updatedata->id = $sessionid;
        $updatedata->status = $status;
        $updatedata->timemodified = time();

        $DB->update_record('local_coursegen_course_sessions', $updatedata);
    }
}
