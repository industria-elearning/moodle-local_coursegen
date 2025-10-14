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

namespace aiplacement_coursegen\hook;

use core_course\hook\after_form_definition;
use core_course\hook\after_form_definition_after_data;
use core_course\hook\after_form_submission;
use aiplacement_coursegen\ai_context;
use aiplacement_coursegen\ai_course;
use aiplacement_coursegen\model;

/**
 * Hook para extender el formulario de curso con campos personalizados.
 *
 * @package    aiplacement_coursegen
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
            'aiplacement_coursegen_header',
            get_string('custom_fields_header', 'aiplacement_coursegen')
        );

        // Add context type selector.
        $contexttypes = [
            'model' => get_string('context_type_model', 'aiplacement_coursegen'),
            'syllabus' => get_string('context_type_syllabus', 'aiplacement_coursegen'),
        ];
        $mform->addElement(
            'select',
            'aiplacement_coursegen_context_type',
            get_string('context_type_field', 'aiplacement_coursegen'),
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
            'aiplacement_coursegen_select_model',
            get_string('custom_model_select_field', 'aiplacement_coursegen'),
            $modeloptions
        );
        $mform->hideIf('aiplacement_coursegen_select_model', 'aiplacement_coursegen_context_type', 'neq', 'model');

        // Agregar campo para subir PDF del sílabo.
        $mform->addElement(
            'filepicker',
            'aiplacement_coursegen_syllabus_pdf',
            get_string('syllabus_pdf_field', 'aiplacement_coursegen'),
            null,
            [
                'accepted_types' => ['.pdf'],
                'maxfiles' => 1,
                'subdirs' => 0,
            ]
        );
        $mform->addHelpButton('aiplacement_coursegen_syllabus_pdf', 'syllabus_pdf_field', 'aiplacement_coursegen');
        $mform->hideIf('aiplacement_coursegen_syllabus_pdf', 'aiplacement_coursegen_context_type', 'neq', 'syllabus');

        // Add hidden field for AI creation to identify the form submission.
        $mform->addElement('hidden', 'aiplacement_coursegen_create_ai_course', 0);
        $mform->setType('aiplacement_coursegen_create_ai_course', PARAM_INT);
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
            $draftitemid = file_get_submitted_draft_itemid('aiplacement_coursegen_syllabus_pdf');

            // Copy existing file to draft area.
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'aiplacement_coursegen',
                'syllabus',
                0,
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
            );

            // Get saved context type and model selection from database.
            $contexttype = '';
            $selectedmodel = '';

            // Get existing course context data from database.
            $contextdata = $DB->get_record('aiplacement_coursegen_course_context', ['courseid' => $courseid]);
            if ($contextdata) {
                $contexttype = $contextdata->context_type;
                $selectedmodel = $contextdata->model_id;
            }

            $editform = $hook->formwrapper;

            $editform->set_data([
                'aiplacement_coursegen_syllabus_pdf' => $draftitemid,
                'aiplacement_coursegen_context_type' => $contexttype,
                'aiplacement_coursegen_select_model' => $selectedmodel,
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
        $createaicourse = $data->aiplacement_coursegen_create_ai_course;

        // Get the context type selection.
        $contexttype = isset($data->aiplacement_coursegen_context_type) ? $data->aiplacement_coursegen_context_type : '';

        if ($contexttype === 'syllabus') {
            // Handle syllabus upload.
            $draftitemid = $data->aiplacement_coursegen_syllabus_pdf;

            // Save syllabus PDF from draft area.
            $success = ai_context::save_syllabus_from_draft($courseid, $draftitemid);

            if ($success) {
                ai_context::upload_syllabus_to_ai($courseid);
            }
        }

        // Store the context type and selected option in the database.
        ai_context::save_course_context($courseid, $contexttype, $data->aiplacement_coursegen_select_model);

        if (!empty($createaicourse)) {
            ai_course::start_course_planning(
                $courseid,
                $contexttype,
                $data->aiplacement_coursegen_select_model,
                $data->fullname
            );
        }
    }
}
