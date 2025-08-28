<?php
namespace local_datacurso\hook;

use \core_course\hook\after_form_definition;
use \core_course\hook\before_form_validation;


class course_form_hook {

    /**
     * Hook para agregar campos personalizados al formulario de curso
     */
    public static function after_form_definition(after_form_definition $hook): void {
        $mform = $hook->mform;
        //$mform = $form->_form;

        // Agregar una sección para los campos personalizados
        $mform->addElement('header', 'local_datacurso_header',
            get_string('custom_fields_header', 'local_datacurso'));

        // Campo de texto personalizado
        $mform->addElement('text', 'local_datacurso_custom_text',
            get_string('custom_text_field', 'local_datacurso'));
        $mform->setType('local_datacurso_custom_text', PARAM_TEXT);
        $mform->addHelpButton('local_datacurso_custom_text', 'custom_text_field', 'local_datacurso');

        // Campo select personalizado
        $options = [
            '' => get_string('choose', 'core'),
            'option1' => get_string('option1', 'local_datacurso'),
            'option2' => get_string('option2', 'local_datacurso'),
            'option3' => get_string('option3', 'local_datacurso'),
        ];
        $mform->addElement('select', 'local_datacurso_custom_select',
            get_string('custom_select_field', 'local_datacurso'), $options);

        // Campo checkbox personalizado
        $mform->addElement('checkbox', 'local_datacurso_custom_checkbox',
            get_string('custom_checkbox_field', 'local_datacurso'));

        // Campo textarea personalizado
        $mform->addElement('textarea', 'local_datacurso_custom_textarea',
            get_string('custom_textarea_field', 'local_datacurso'),
            'wrap="virtual" rows="5" cols="50"');
        $mform->setType('local_datacurso_custom_textarea', PARAM_TEXT);

        // Campo de fecha personalizado
        $mform->addElement('date_selector', 'local_datacurso_custom_date',
            get_string('custom_date_field', 'local_datacurso'),
            array('optional' => true));

        // Si estamos editando un curso existente, cargar los valores
        /*if (!empty($hook->get_course()->id)) {
            self::load_custom_data($form, $mform);
        }*/
    }

    /**
     * Hook para validar los campos personalizados
     */
    public static function before_form_validation(before_form_validation $hook): void {
        $form = $hook->get_form();
        $data = $hook->get_data();
        $errors = $hook->get_errors();

        // Validación personalizada para el campo de texto
        if (!empty($data['local_datacurso_custom_text'])) {
            if (strlen($data['local_datacurso_custom_text']) < 3) {
                $errors['local_datacurso_custom_text'] = get_string('error_text_too_short', 'local_datacurso');
            }
        }

        // Validación para el campo select
        if (empty($data['local_datacurso_custom_select'])) {
            // Opcional: hacer el campo requerido
            // $errors['local_datacurso_custom_select'] = get_string('required');
        }

        $hook->set_errors($errors);
    }

    /**
     * Cargar datos personalizados para un curso existente
     */
    private static function load_custom_data($form, $mform): void {
        global $DB;

        $courseid = $form->get_course()->id;

        // Buscar datos personalizados en tu tabla personalizada
        $customdata = $DB->get_record('local_datacurso_course_data',
            ['courseid' => $courseid]);

        if ($customdata) {
            $mform->setDefault('local_datacurso_custom_text', $customdata->custom_text);
            $mform->setDefault('local_datacurso_custom_select', $customdata->custom_select);
            $mform->setDefault('local_datacurso_custom_checkbox', $customdata->custom_checkbox);
            $mform->setDefault('local_datacurso_custom_textarea', $customdata->custom_textarea);
            $mform->setDefault('local_datacurso_custom_date', $customdata->custom_date);
        }
    }
}