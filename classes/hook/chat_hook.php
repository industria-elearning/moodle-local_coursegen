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
     * Hook para cargar el chat antes del footer.
     *
     * @param before_footer_html_generation $hook El hook del evento.
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;
        self::add_activity_ai_button();
        self::add_course_ai_button();
        self::check_ai_course_creation();
    }

    /**
     * Verifica si estamos en un contexto de curso
     */
    private static function is_course_context(): bool {
        global $PAGE, $COURSE;

        // Verificar si estamos en una página de curso.
        if ($PAGE->pagelayout === 'course' ||
            $PAGE->pagelayout === 'incourse' ||
            strpos($PAGE->pagetype, 'course-') === 0 ||
            strpos($PAGE->pagetype, 'mod-') === 0) {
            return true;
        }

        // Verificar si hay un curso válido.
        if (isset($COURSE) && $COURSE->id > 1) {
            return true;
        }

        // Verificar contexto.
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
     * Add activity AI button
     */
    private static function add_activity_ai_button(): void {
        global $PAGE, $COURSE;

        if (!self::is_course_context()) {
            return;
        }

        $context = \context_course::instance($COURSE->id);

        // Verificar si es profesor o tiene permisos de edición.
        if (!has_capability('moodle/course:update', $context) ||
            !has_capability('moodle/course:manageactivities', $context)) {
            return;
        }

        $PAGE->requires->js_call_amd('local_datacurso/add_activity_ai_button', 'init', ['courseid' => $COURSE->id]);
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

        $PAGE->requires->js_call_amd('local_datacurso/add_course_ai_button', 'init', []);
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
