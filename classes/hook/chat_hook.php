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

namespace local_datacurso\hook;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_standard_head_html_generation;
use local_datacurso\ai_course;
use local_datacurso\local\streaming_helper;

/**
 * Hook para cargar el chat flotante
 *
 * @package    local_datacurso
 * @copyright  2025 Datacurso <josue@datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat_hook {

    /**
     * Hook to load the floating chat before the footer.
     * Calls functions to add AI buttons and check AI course creation.
     *
     * @param before_footer_html_generation $hook The hook event.
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        self::add_activity_ai_button();
        self::add_course_ai_button();
        self::check_ai_course_creation();
        self::add_float_chat();
    }

    /**
     * Hook to add CSS and metadata in the head.
     *
     * @param before_standard_head_html_generation $hook The hook event.
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        global $PAGE;

        // Only load in course contexts.
        if (!self::is_course_context()) {
            return;
        }

        // Load chat CSS.
        $PAGE->requires->css('/local/datacurso/styles/chat.css');

        // Add metadata for the chat.
        $hook->add_html('<meta name="datacurso-chat-enabled" content="true">');
    }

    /**
     * Checks if we are in a course context.
     * Returns true if the current page or context is related to a course or module.
     *
     * @return bool
     */
    private static function is_course_context(): bool {
        global $PAGE, $COURSE;

        // Check if we are on a course page.
        if ($PAGE->pagelayout === 'course' ||
            $PAGE->pagelayout === 'incourse' ||
            strpos($PAGE->pagetype, 'course-') === 0 ||
            strpos($PAGE->pagetype, 'mod-') === 0) {
            return true;
        }

        // Check if there is a valid course.
        if (isset($COURSE) && $COURSE->id > 1) {
            return true;
        }

        // Check context.
        $context = $PAGE->context;
        if(!$context) {
            return false;
        }
        if ($context->contextlevel == CONTEXT_COURSE ||
            $context->contextlevel == CONTEXT_MODULE) {
            return true;
        }

        return false;
    }

    /**
     * Adds the activity AI button to the course page for teachers/editors.
     */
    private static function add_activity_ai_button(): void {
        global $PAGE, $COURSE;

        if (!self::is_course_context()) {
            return;
        }

        $context = \context_course::instance($COURSE->id);

        // Only show for users with editing capabilities.
        if (!has_capability('moodle/course:update', $context) ||
            !has_capability('moodle/course:manageactivities', $context)) {
            return;
        }

        $PAGE->requires->js_call_amd('local_datacurso/add_activity_ai_button', 'init', ['courseid' => $COURSE->id]);
    }

    /**
     * Adds the course AI button on the course edit page.
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

        $PAGE->requires->js_call_amd('local_datacurso/add_course_ai_button', 'init', []);
    }

    /**
     * Adds the floating chat to course pages for all users.
     */
    private static function add_float_chat(): void {
        global $PAGE, $COURSE, $USER;

        if (!self::is_course_context()) {
            return;
        }

        $PAGE->requires->js_call_amd('local_datacurso/chat', 'init');

        $chatdata = [
            'courseid' => $COURSE->id ?? 0,
            'userid' => $USER->id,
            'userrole' => self::get_user_role_in_course(),
            'contextlevel' => $PAGE->context->contextlevel ?? 0,
        ];

        $PAGE->requires->data_for_js('datacurso_chat_config', $chatdata);
    }

    /**
     * Determines the user's role in the current course context.
     */
    private static function get_user_role_in_course(): string {
        global $COURSE, $USER;

        if (!isset($COURSE) || $COURSE->id <= 1) {
            return 'student';
        }

        $context = \context_course::instance($COURSE->id);

        if (has_capability('moodle/course:update', $context) ||
            has_capability('moodle/course:manageactivities', $context)) {
            return 'teacher';
        }

        // Verificar roles específicos.
        $roles = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            if (in_array($role->shortname, ['teacher', 'editingteacher', 'manager', 'coursecreator'])) {
                return 'teacher';
            }
        }

        return 'student';
    }

    /**
     * Check if course is being created with AI and open modal if needed
     */
    private static function check_ai_course_creation(): void {
        global $PAGE, $COURSE, $CFG;

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
            // Build streaming URL with session ID using helper.
            $streamingurl = streaming_helper::get_streaming_url_for_session($session->session_id);

            // Load the AI course modal with streaming URL.
            $PAGE->requires->js_call_amd('local_datacurso/add_course_ai_modal', 'init', [
                [
                    'streamingurl' => $streamingurl,
                    'courseid' => $COURSE->id,
                ],
            ]);
        }
    }
}
