<?php
namespace local_datacurso\hook;

use \core_course\hook\after_form_definition;
use \core_course\hook\before_form_validation;
use local_datacurso\httpclient\datacurso_api;

class course_form_hook {

    /**
     * Hook para agregar campos personalizados al formulario de curso
     */
    public static function after_form_definition(after_form_definition $hook): void {
        $mform = $hook->mform;

        // Agregar una sección para los campos personalizados
        $mform->addElement('header', 'local_datacurso_header',
            get_string('custom_fields_header', 'local_datacurso'));

        $modeloptions = ['' => get_string('choose', 'core')];

        try {
            $client = new datacurso_api();

            $models = $client->get('/v3/pedagogic-model');

            if (is_array($models)) {
                foreach ($models as $model) {
                    $id = $model['id'] ?? null;
                    if (!$id) {
                        continue;
                    }
                    // Elegimos el mejor label disponible.
                    $label = $model['title'];
                    // Limpieza básica por si viniera con HTML.
                    $label = format_string($label);
                    $modeloptions[$id] = $label;
                }

            }
        } catch (\Throwable $e) {
            // En caso de error, deja opciones de respaldo
            debugging('Error cargando modelos desde DataCurso API: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $modeloptions += [
                'option1' => get_string('option1', 'local_datacurso'),
                'option2' => get_string('option2', 'local_datacurso'),
                'option3' => get_string('option3', 'local_datacurso'),
            ];
        }

        // Select de modelos
        $mform->addElement(
            'select',
            'local_datacurso_custom_select_model',
            get_string('custom_model_select_field', 'local_datacurso'),
            $modeloptions
        );
        
        // Program definition text
        $mform->addElement('text', 'local_datacurso_custom_text_program',
            get_string('custom_program_text_field', 'local_datacurso'));
        $mform->setType('local_datacurso_custom_text_program', PARAM_TEXT);
        
        // Semester selection
        $options = [
            '' => get_string('choose', 'core'),
            'semester_option1' => get_string('semester_option1', 'local_datacurso'),
            'semester_option2' => get_string('semester_option2', 'local_datacurso'),
            'semester_option3' => get_string('semester_option3', 'local_datacurso'),
            'semester_option4' => get_string('semester_option4', 'local_datacurso'),
            'semester_option5' => get_string('semester_option5', 'local_datacurso'),
            'semester_option6' => get_string('semester_option6', 'local_datacurso'),
            'semester_option7' => get_string('semester_option7', 'local_datacurso'),
            'semester_option8' => get_string('semester_option8', 'local_datacurso'),
            'semester_option9' => get_string('semester_option9', 'local_datacurso'),
            'semester_option10' => get_string('semester_option10', 'local_datacurso')
        ];
        $mform->addElement('select', 'local_datacurso_custom_select_semester',
            get_string('custom_semester_select_field', 'local_datacurso'), $options);

        // Training level selection
        $options = [
            '' => get_string('choose', 'core'),
            'formation_option1' => get_string('formation_option1', 'local_datacurso'),
            'formation_option2' => get_string('formation_option2', 'local_datacurso'),
            'formation_option3' => get_string('formation_option3', 'local_datacurso'),
        ];
        $mform->addElement('select', 'local_datacurso_custom_select_formation',
            get_string('custom_formation_select_field', 'local_datacurso'), $options);

        // Training objective textarea
        $mform->addElement('editor', 'local_datacurso_training_objective',
            get_string('training_objective_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_training_objective', PARAM_RAW);

        // Course description textarea
        $mform->addElement('editor', 'local_datacurso_course_description',
            get_string('course_description_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_course_description', PARAM_RAW);

        // Course content textarea
        $mform->addElement('editor', 'local_datacurso_course_content',
            get_string('course_content_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_course_content', PARAM_RAW);

        // Learning outcomes textarea
        $mform->addElement('editor', 'local_datacurso_learning_outcomes',
            get_string('learning_outcomes_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_learning_outcomes', PARAM_RAW);

        // Course summary textarea
        $mform->addElement('editor', 'local_datacurso_course_summary',
            get_string('course_summary_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_course_summary', PARAM_RAW);

        // Course structure textarea
        $mform->addElement('editor', 'local_datacurso_course_structure',
            get_string('course_structure_field', 'local_datacurso'),
            null, ['rows' => 10, 'cols' => 60]);
        $mform->setType('local_datacurso_course_structure', PARAM_RAW);

        // Number of modules text field
        $mform->addElement('text', 'local_datacurso_number_of_modules',
            get_string('number_of_modules_field', 'local_datacurso'));
        $mform->setType('local_datacurso_number_of_modules', PARAM_INT);
    }

    /**
     * Hook para validar los campos personalizados
     */
    public static function before_form_validation(before_form_validation $hook): void {
        $form = $hook->get_form();
        $data = $hook->get_data();
        $errors = $hook->get_errors();

        // Validación personalizada para el campo de texto del programa
        if (!empty($data['local_datacurso_custom_text_program'])) {
            if (strlen($data['local_datacurso_custom_text_program']) < 3) {
                $errors['local_datacurso_custom_text_program'] = get_string('error_text_too_short', 'local_datacurso');
            }
        }

        // Validación para la cantidad de módulos
        if (!empty($data['local_datacurso_number_of_modules'])) {
            if (!is_numeric($data['local_datacurso_number_of_modules']) || $data['local_datacurso_number_of_modules'] < 1) {
                $errors['local_datacurso_number_of_modules'] = get_string('error_invalid_number', 'local_datacurso');
            }
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
            // Establecer valores por defecto para cada campo
            $mform->setDefault('local_datacurso_custom_select_model', $customdata->custom_select_model);
            $mform->setDefault('local_datacurso_custom_text_program', $customdata->custom_text_program);
            $mform->setDefault('local_datacurso_custom_select_semester', $customdata->custom_select_semester);
            $mform->setDefault('local_datacurso_custom_select_formation', $customdata->custom_select_formation);
            $mform->setDefault('local_datacurso_number_of_modules', $customdata->number_of_modules);
            
            // Para campos editor (necesitan formato especial)
            if (isset($customdata->training_objective)) {
                $mform->setDefault('local_datacurso_training_objective', [
                    'text' => $customdata->training_objective,
                    'format' => FORMAT_HTML
                ]);
            }
            
            if (isset($customdata->course_description)) {
                $mform->setDefault('local_datacurso_course_description', [
                    'text' => $customdata->course_description,
                    'format' => FORMAT_HTML
                ]);
            }
            
            if (isset($customdata->course_content)) {
                $mform->setDefault('local_datacurso_course_content', [
                    'text' => $customdata->course_content,
                    'format' => FORMAT_HTML
                ]);
            }
            
            if (isset($customdata->learning_outcomes)) {
                $mform->setDefault('local_datacurso_learning_outcomes', [
                    'text' => $customdata->learning_outcomes,
                    'format' => FORMAT_HTML
                ]);
            }
            
            if (isset($customdata->course_summary)) {
                $mform->setDefault('local_datacurso_course_summary', [
                    'text' => $customdata->course_summary,
                    'format' => FORMAT_HTML
                ]);
            }
            
            if (isset($customdata->course_structure)) {
                $mform->setDefault('local_datacurso_course_structure', [
                    'text' => $customdata->course_structure,
                    'format' => FORMAT_HTML
                ]);
            }
        }
    }
}
