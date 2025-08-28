<?php
namespace local_datacurso\hook;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_standard_head_html_generation;

/**
 * Hook para cargar el chat flotante
 *
 * @package    local_datacurso
 * @copyright  2025 Datacurso <josue@datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat_hook {

    /**
     * Hook para cargar el chat antes del footer
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $COURSE, $USER;
        
        // Solo cargar en contextos de curso
        if (!self::is_course_context()) {
            return;
        }

        // Cargar JavaScript del chat
        $PAGE->requires->js_call_amd('local_datacurso/chat', 'init');
        
        // Agregar datos del contexto para JavaScript
        $chatdata = [
            'courseid' => $COURSE->id ?? 0,
            'userid' => $USER->id,
            'userrole' => self::get_user_role_in_course(),
            'contextlevel' => $PAGE->context->contextlevel ?? 0
        ];
        
        $PAGE->requires->data_for_js('datacurso_chat_config', $chatdata);
    }

    /**
     * Hook para agregar CSS y metadatos en el head
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        global $PAGE, $CFG;
        
        // Solo cargar en contextos de curso
        if (!self::is_course_context()) {
            return;
        }
        
        // Cargar CSS del chat
        $PAGE->requires->css('/local/datacurso/styles/chat.css');
        
        // Agregar metadatos para el chat
        $hook->add_html('<meta name="datacurso-chat-enabled" content="true">');
    }

    /**
     * Verifica si estamos en un contexto de curso
     */
    private static function is_course_context(): bool {
        global $PAGE, $COURSE;
        
        // Verificar si estamos en una página de curso
        if ($PAGE->pagelayout === 'course' || 
            $PAGE->pagelayout === 'incourse' ||
            strpos($PAGE->pagetype, 'course-') === 0 ||
            strpos($PAGE->pagetype, 'mod-') === 0) {
            return true;
        }
        
        // Verificar si hay un curso válido
        if (isset($COURSE) && $COURSE->id > 1) {
            return true;
        }
        
        // Verificar contexto
        $context = $PAGE->context;
        if ($context->contextlevel == CONTEXT_COURSE || 
            $context->contextlevel == CONTEXT_MODULE) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtiene el rol del usuario en el curso actual
     */
    private static function get_user_role_in_course(): string {
        global $PAGE, $COURSE, $USER;
        
        if (!isset($COURSE) || $COURSE->id <= 1) {
            return 'Estudiante';
        }
        
        $context = \context_course::instance($COURSE->id);
        
        // Verificar si es profesor o tiene permisos de edición
        if (has_capability('moodle/course:update', $context) ||
            has_capability('moodle/course:manageactivities', $context)) {
            return 'Profesor';
        }
        
        // Verificar roles específicos
        $roles = get_user_roles($context, $USER->id);
        foreach ($roles as $role) {
            if (in_array($role->shortname, ['teacher', 'editingteacher', 'manager', 'coursecreator'])) {
                return 'Profesor';
            }
        }
        
        return 'Estudiante';
    }
}

