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
use core_course\hook\after_form_submission;
use local_datacurso\ai_context;
use local_datacurso\model;
use moodle_url;

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
        global $PAGE;
        $mform = $hook->mform;

        // Agregar una sección para los campos personalizados.
        $mform->addElement('header', 'local_datacurso_header',
            get_string('custom_fields_header', 'local_datacurso'));

        // Agregar selector de tipo de contexto.
        $contexttypes = [
            'model' => get_string('context_type_model', 'local_datacurso'),
            'syllabus' => get_string('context_type_syllabus', 'local_datacurso'),
        ];
        $mform->addElement(
            'select',
            'local_datacurso_context_type',
            get_string('context_type_field', 'local_datacurso'),
            $contexttypes
        );

        // Obtener modelos de la base de datos.
        $models = model::get_all();
        foreach ($models as $model) {
            $modeloptions[$model->id] = $model->name;
        }

        // Agregar selector de modelo instruccional.
        $mform->addElement(
            'select',
            'local_datacurso_select_model',
            get_string('custom_model_select_field', 'local_datacurso'),
            $modeloptions
        );
        $mform->hideIf('local_datacurso_select_model', 'local_datacurso_context_type', 'neq', 'model');

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
        $mform->hideIf('local_datacurso_syllabus_pdf', 'local_datacurso_context_type', 'neq', 'syllabus');
    }

    /**
     * Hooks to set default data to the form fields
     *
     * @param after_form_definition_after_data $hook Hook object with the form.
     */
    public static function after_form_definition_after_data(after_form_definition_after_data $hook): void {
        global $DB;
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

            // Get saved context type and model selection from database.
            $contexttype = '';
            $selectedmodel = '';

            // Get existing course context data from database.
            $contextdata = $DB->get_record('local_datacurso_course_context', ['courseid' => $courseid]);
            if ($contextdata) {
                $contexttype = $contextdata->context_type;
                $selectedmodel = $contextdata->model_id;
            }

            $editform = $hook->formwrapper;

            $editform->set_data([
                'local_datacurso_syllabus_pdf' => $draftitemid,
                'local_datacurso_context_type' => $contexttype,
                'local_datacurso_select_model' => $selectedmodel,
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

        $aicreation = optional_param('ai_creation', 0, PARAM_INT);

        $data = $hook->get_data();
        $courseid = $data->id;

        // Get the context type selection.
        $contexttype = isset($data->local_datacurso_context_type) ? $data->local_datacurso_context_type : '';

        if ($contexttype === 'syllabus') {
            // Handle syllabus upload.
            $draftitemid = $data->local_datacurso_syllabus_pdf;

            // Save syllabus PDF from draft area.
            $success = ai_context::save_syllabus_from_draft($courseid, $draftitemid);

            if ($success) {
                ai_context::upload_syllabus_to_ai($courseid);
            }
        }

        // Store the context type and selected option in the database.
        ai_context::save_course_context($courseid, $contexttype, $data->local_datacurso_select_model);

        if ($aicreation) {
            redirect(new moodle_url('/course/view.php', ['id' => $courseid, 'ai_creation' => 1]));
        }
    }
}
