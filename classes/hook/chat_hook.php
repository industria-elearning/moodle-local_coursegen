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

namespace local_coursegen\hook;

use aiprovider_datacurso\httpclient\ai_course_api;
use core\hook\output\before_footer_html_generation;
use local_coursegen\ai_course;

/**
 * Hook para cargar el chat flotante
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat_hook {
    /**
     * Hook to add AI buttons and check AI course creation
     *
     * @param before_footer_html_generation $hook The hook event.
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        self::add_activity_ai_button();
        self::add_course_ai_button();
        self::check_ai_course_creation();
    }

    /**
     * Check if we are in a course context
     */
    private static function is_course_context(): bool {
        global $PAGE, $COURSE;

        // Check if we are in a course page.
        if (
            $PAGE->pagelayout === 'course' ||
            $PAGE->pagelayout === 'incourse' ||
            strpos($PAGE->pagetype, 'course-') === 0 ||
            strpos($PAGE->pagetype, 'mod-') === 0
        ) {
            return true;
        }

        // Check if we have a valid course.
        if (isset($COURSE) && $COURSE->id > 1) {
            return true;
        }

        // Check context.
        $context = $PAGE->context;
        if (!$context) {
            return false;
        }
        if (
            $context->contextlevel == CONTEXT_COURSE ||
            $context->contextlevel == CONTEXT_MODULE
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add activity AI button
     */
    private static function add_activity_ai_button(): void {
        global $PAGE, $COURSE;

        if (!self::is_course_context()) {
            return;
        }

        $context = \context_course::instance($COURSE->id);

        // Check if user has permission to update course or manage activities.
        if (
            !has_capability('moodle/course:update', $context) ||
            !has_capability('moodle/course:manageactivities', $context)
        ) {
            return;
        }

        $PAGE->requires->js_call_amd('local_coursegen/add_activity_ai_button', 'init', ['courseid' => $COURSE->id]);
    }

    /**
     * Add course AI button
     */
    private static function add_course_ai_button(): void {
        global $PAGE;
        $iseditpage = $PAGE->url->get_path() === '/course/edit.php';

        if (!$iseditpage) {
            return;
        }

        $courseid = $PAGE->url->get_param('id');
        if ($courseid) {
            return;
        }

        $PAGE->requires->js_call_amd('local_coursegen/add_course_ai_button', 'init', []);
    }

    /**
     * Check if course is being created with AI and open modal if needed
     */
    private static function check_ai_course_creation(): void {
        global $PAGE, $COURSE, $CFG, $SESSION;

        // Check if we are on course/view.php page.
        if ($PAGE->url->get_path() !== '/course/view.php') {
            return;
        }

        // Check if we have a valid course ID.
        if (!isset($COURSE) || $COURSE->id <= 1) {
            return;
        }

        // Get course session from database.
        $session = ai_course::get_course_session($COURSE->id);
        // If no session exists, return.
        if (!$session) {
            return;
        }

        // Check if session is in planning or creating status (1 or 2).
        if ($session->status == 1 || $session->status == 2) {
            if (!isset($SESSION->local_coursegen_modal_shown)) {
                $SESSION->local_coursegen_modal_shown = [];
            }

            $shown = $SESSION->local_coursegen_modal_shown[$COURSE->id] ?? false;

            if ($shown) {
                ai_course::update_session_status($session->id, 4);
                return;
            }

            $client = new ai_course_api();
            $streamingurl = $client->get_streaming_url_for_session($session->session_id);

            $PAGE->requires->js_call_amd('local_coursegen/add_course_ai_modal', 'init', [
                [
                    'streamingurl' => $streamingurl,
                    'courseid' => $COURSE->id,
                ],
            ]);

            $SESSION->local_coursegen_modal_shown[$COURSE->id] = true;
        }
    }
}
