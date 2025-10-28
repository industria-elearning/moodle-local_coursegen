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

use core_course\hook\after_form_definition;
use core_course\hook\after_form_definition_after_data;
use core_course\hook\after_form_submission;
use core_course\hook\after_form_validation;
use local_coursegen\ai_context;
use local_coursegen\ai_course;
use local_coursegen\model;

/**
 * Hook para extender el formulario de curso con campos personalizados.
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_form_hook {
    /**
     * Hook para agregar campos personalizados al formulario de curso.
     *
     * @param after_form_definition $hook Objeto del hook con el formulario.
     * @throws \coding_exception
     */
    public static function after_form_definition(after_form_definition $hook): void {
        global $PAGE;
        $mform = $hook->mform;

        // Add a section for custom fields.
        $mform->addElement(
            'header',
            'local_coursegen_header',
            get_string('custom_fields_header', 'local_coursegen')
        );

        // Add context type selector.
        $contexttypes = [
            '' => get_string('choosedots'),
            ai_context::CONTEXT_TYPE_MODEL => get_string('context_type_model', 'local_coursegen'),
            ai_context::CONTEXT_TYPE_SYLLABUS => get_string('context_type_syllabus', 'local_coursegen'),
        ];
        $mform->addElement(
            'select',
            'local_coursegen_context_type',
            get_string('context_type_field', 'local_coursegen'),
            $contexttypes
        );
        $mform->setDefault('local_coursegen_context_type', '');

        // Obtener modelos de la base de datos.
        $models = model::get_all();
        $hasmodels = !empty($models);
        if ($hasmodels) {
            foreach ($models as $model) {
                $modeloptions[$model->id] = $model->name;
            }

            // Agregar selector de modelo instruccional.
            $mform->addElement(
                'select',
                'local_coursegen_select_model',
                get_string('custom_model_select_field', 'local_coursegen'),
                $modeloptions
            );
            $mform->hideIf(
                'local_coursegen_select_model',
                'local_coursegen_context_type',
                'neq',
                ai_context::CONTEXT_TYPE_MODEL
            );
        } else {
            // Mostrar aviso cuando no hay modelos configurados (solo si el contexto seleccionado es modelo).
            $managemodelsurl = (new \moodle_url('/local/coursegen/manage_models.php'))->out();
            $mform->addElement(
                'static',
                'local_coursegen_select_model_notice',
                get_string('custom_model_select_field', 'local_coursegen'),
                get_string('no_models_configured_notice', 'local_coursegen', $managemodelsurl)
            );
            $mform->hideIf(
                'local_coursegen_select_model_notice',
                'local_coursegen_context_type',
                'neq',
                ai_context::CONTEXT_TYPE_MODEL
            );
        }

        // Agregar campo para subir PDF del sílabo.
        $mform->addElement(
            'filepicker',
            'local_coursegen_syllabus_pdf',
            get_string('syllabus_pdf_field', 'local_coursegen'),
            null,
            [
                'accepted_types' => ['.pdf'],
                'maxfiles' => 1,
                'subdirs' => 0,
            ]
        );
        $mform->addHelpButton('local_coursegen_syllabus_pdf', 'syllabus_pdf_field', 'local_coursegen');
        $mform->hideIf('local_coursegen_syllabus_pdf', 'local_coursegen_context_type', 'neq', ai_context::CONTEXT_TYPE_SYLLABUS);

        // Add hidden field for AI creation to identify the form submission.
        $mform->addElement('hidden', 'local_coursegen_create_ai_course', 0);
        $mform->setType('local_coursegen_create_ai_course', PARAM_INT);
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
            $draftitemid = file_get_submitted_draft_itemid('local_coursegen_syllabus_pdf');

            // Copy existing file to draft area.
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'local_coursegen',
                ai_context::CONTEXT_TYPE_SYLLABUS,
                0,
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
            );

            // Get saved context type and model selection from database.
            $contexttype = '';
            $selectedmodel = '';

            // Get existing course context data from database.
            $contextdata = $DB->get_record('local_coursegen_course_context', ['courseid' => $courseid]);
            if ($contextdata) {
                $contexttype = $contextdata->context_type;
                $selectedmodel = $contextdata->model_id;
            }

            $editform = $hook->formwrapper;

            $editform->set_data([
                'local_coursegen_syllabus_pdf' => $draftitemid,
                'local_coursegen_context_type' => $contexttype,
                'local_coursegen_select_model' => $selectedmodel,
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
        $createaicourse = $data->local_coursegen_create_ai_course;

        // Get the context type selection.
        $contexttype = isset($data->local_coursegen_context_type) ? $data->local_coursegen_context_type : '';

        try {
            if ($contexttype === ai_context::CONTEXT_TYPE_SYLLABUS) {
                // Handle syllabus upload.
                $draftitemid = $data->local_coursegen_syllabus_pdf;

                // Save syllabus PDF from draft area.
                $success = ai_context::save_syllabus_from_draft($courseid, $draftitemid);

                if ($success) {
                    ai_context::upload_syllabus_to_ai($courseid);
                }
            }

            // Store the context type and selected option in the database.
            ai_context::save_course_context($courseid, $contexttype, $data->local_coursegen_select_model);

            if (!empty($createaicourse)) {
                ai_course::start_course_planning(
                    $courseid,
                    $contexttype,
                    $data->local_coursegen_select_model,
                    $data->fullname
                );
            }
        } catch (\Exception $e) {
            \core\notification::error(get_string('error_upload_failed', 'local_coursegen', $e->getMessage()));
        }
    }

    /**
     * Hook to validate the form data.
     *
     * @param after_form_validation $hook Hook object with the form data.
     */
    public static function after_form_validation(after_form_validation $hook): void {
        $data = $hook->get_data();

        $createaicourse = isset($data['local_coursegen_create_ai_course']) ? (int)$data['local_coursegen_create_ai_course'] : 0;
        if ($createaicourse !== 1) {
            return;
        }

        $errors = [];
        $contexttype = isset($data['local_coursegen_context_type']) ? (string)$data['local_coursegen_context_type'] : '';
        $allowed = [ai_context::CONTEXT_TYPE_MODEL, ai_context::CONTEXT_TYPE_SYLLABUS];
        if ($contexttype === '' || !in_array($contexttype, $allowed, true)) {
            $errors['local_coursegen_context_type'] = get_string('error_context_type_required', 'local_coursegen');
        } else if ($contexttype === ai_context::CONTEXT_TYPE_MODEL) {
            $models = model::get_all();
            if (empty($models)) {
                $errors['local_coursegen_context_type'] = get_string('error_no_models_configured', 'local_coursegen');
            } else {
                $modelid = $data['local_coursegen_select_model'] ?? null;
                if (empty($modelid)) {
                    $errors['local_coursegen_select_model'] = get_string('error_model_required', 'local_coursegen');
                }
            }
        } else if ($contexttype === ai_context::CONTEXT_TYPE_SYLLABUS) {
            $draftitemid = $data['local_coursegen_syllabus_pdf'] ?? 0;
            $info = $draftitemid ? file_get_draft_area_info($draftitemid) : null;
            $filecount = is_array($info) && array_key_exists('filecount', $info) ? (int)$info['filecount'] : 0;
            if (empty($draftitemid) || $filecount < 1) {
                $errors['local_coursegen_syllabus_pdf'] = get_string('error_syllabus_pdf_required', 'local_coursegen');
            }
        }

        if (!empty($errors)) {
            $hook->add_errors($errors);
        }
    }
}
