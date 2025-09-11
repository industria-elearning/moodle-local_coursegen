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

use core_course\hook\after_form_definition;
use core_course\hook\after_form_definition_after_data;
use core_course\hook\after_form_validation;
use core_course\hook\after_form_submission;

/**
 * Hook para extender el formulario de curso con campos personalizados.
 *
 * @package    local_datacurso
 * @copyright  2025 Datacurso <josue@datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_form_hook {

    /**
     * Hook para agregar campos personalizados al formulario de curso.
     *
     * @param after_form_definition $hook Objeto del hook con el formulario.
     */
    public static function after_form_definition(after_form_definition $hook): void {
        $mform = $hook->mform;

        // Agregar una sección para los campos personalizados.
        $mform->addElement('header', 'local_datacurso_header',
            get_string('custom_fields_header', 'local_datacurso'));

        $modeloptions = [
            get_string('choosemodel', 'local_datacurso'),
            'option1' => 'Libre',
            'option2' => 'Robert Gagne',
            'option3' => 'ADDIE',
        ];
        $mform->addElement(
            'select',
            'local_datacurso_custom_select_model',
            get_string('custom_model_select_field', 'local_datacurso'),
            $modeloptions
        );

        // Agregar campo para subir PDF del sílabo.
        $mform->addElement(
            'filepicker',
            'local_datacurso_syllabus_pdf',
            get_string('syllabus_pdf_field', 'local_datacurso'),
            null,
            [
                'accepted_types' => ['.pdf'],
                'maxfiles' => 1,
                'subdirs' => 0,
            ]
        );
        $mform->addHelpButton('local_datacurso_syllabus_pdf', 'syllabus_pdf_field', 'local_datacurso');
    }

    /**
     * Hooks to set default data to the form fields
     *
     * @param after_form_definition_after_data $hook Hook object with the form.
     */
    public static function after_form_definition_after_data(after_form_definition_after_data $hook): void {
        $courseid = optional_param('id', 0, PARAM_INT);
        if (!empty($courseid)) {
            $context = \context_course::instance($courseid);

            // Create a draft area for the filepicker.
            $draftitemid = file_get_submitted_draft_itemid('local_datacurso_syllabus_pdf');

            // Copy existing file to draft area.
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'local_datacurso',
                'syllabus',
                0,
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
            );

            $editform = $hook->formwrapper;

            $editform->set_data([
                'local_datacurso_syllabus_pdf' => $draftitemid,
            ]);
        }
    }

    /**
     * Hook to process the form submission.
     *
     * @param after_form_submission $hook Hook object with the form data.
     */
    public static function after_form_submission(after_form_submission $hook): void {
        global $DB;

        $data = $hook->get_data();

        $courseid = $data->id;

        $draftitemid = $data->local_datacurso_syllabus_pdf;

        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                \context_course::instance($courseid)->id,
                'local_datacurso',
                'syllabus',
                0,
                [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => ['.pdf'],
                ]
            );
            self::upload_syllabus_to_ai($courseid);
        }
    }

    /**
     * Sube el archivo de sílabo al endpoint de IA.
     *
     * @param int $courseid ID del curso.
     */
    private static function upload_syllabus_to_ai(int $courseid): void {
        global $CFG;

        try {
            $fs = get_file_storage();
            $context = \context_course::instance($courseid);

            $files = $fs->get_area_files($context->id, 'local_datacurso', 'syllabus', 0, 'itemid', false);

            if (empty($files)) {
                return;
            }

            $file = reset($files);
            if (!$file) {
                return;
            }

            $siteid = md5($CFG->wwwroot);

            // Guardar el archivo temporalmente.
            $tempfile = $file->copy_content_to_temp();

            // Preparar el archivo como recurso CURL.
            $cfile = new \CURLFile($tempfile, $file->get_mimetype(), $file->get_filename());

            // Preparar los datos del POST.
            $postdata = [
                'title' => $file->get_filename(),
                'file' => $cfile,
                'body' => $siteid,
                'site_id' => $siteid,
                'course_id' => $courseid,
            ];

            $apitoken = get_config('local_datacurso', 'apitoken');
            $baseurl = get_config('local_datacurso', 'baseurl');

            // Realizar la petición HTTP con cURL.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseurl, '/') . '/context/upload');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apitoken]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Verificar la respuesta.
            if ($error) {
                throw new \moodle_exception('error_upload_failed', 'local_datacurso', '', $error);
            }

            if ($httpcode !== 200) {
                throw new \moodle_exception('error_upload_failed', 'local_datacurso', '',
                    get_string('error_http_code', 'local_datacurso', $httpcode));
            }

            // Log del éxito.
            debugging("DataCurso: Syllabus uploaded successfully for course {$courseid}", DEBUG_NORMAL);

        } catch (\Exception $e) {
            // Log del error.
            debugging("DataCurso: Error uploading syllabus for course {$courseid}: " . $e->getMessage(), DEBUG_NORMAL);

            // Mostrar notificación de error al usuario.
            \core\notification::error(get_string('error_upload_failed', 'local_datacurso', $e->getMessage()));
        }
    }
}
