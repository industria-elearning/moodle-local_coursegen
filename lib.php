<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License, version 3 or later.

defined('MOODLE_INTERNAL') || die();

use local_datacurso\httpclient\datacurso_api;

/**
 * Nombre de la categoría y clave de config donde guardamos su ID.
 */
const DATACURSO_CF_CATEGORY_NAME = 'Datacurso Custom Fields';
const DATACURSO_CF_CATEGORY_CONFIGKEY = 'datacurso_customfield_catid';

/**
 * Crea una categoría de Custom Fields para cursos.
 *
 * @param string $categoryname
 * @return int category id
 * @throws moodle_exception
 */
function datacurso_create_customfield_category(string $categoryname): int {
    // Handler de campos personalizados para cursos.
    $handler = \core_customfield\handler::get_handler('core_course', 'course', 0);

    if (!$handler->can_configure()) {
        if (!CLI_SCRIPT) {
            throw new moodle_exception('nopermissionconfigure', 'core_customfield');
        } else {
            \core\session\manager::set_user(get_admin());
        }
    }
    return $handler->create_category($categoryname);
}

/**
 * Devuelve el payload de configuración para un campo personalizado.
 *
 * @param int $categoryid
 * @param string $fieldname
 * @param string $fieldtype checkbox|date|select|text|textarea
 * @param array $options   (opcional) claves específicas del tipo
 * @param string $description (opcional) html/texto descriptivo
 * @return stdClass
 * @throws moodle_exception
 */
function datacurso_get_customfield_data(int $categoryid, string $fieldname, string $fieldtype,
                                       array $options = [], string $description = ''): \stdClass {
    $data = new \stdClass();

    $data->name = $fieldname;
    $data->description = $description;
    $data->descriptionformat = FORMAT_HTML;

    $replacefor = [' ', '(', ')'];
    $replacewith = ['', '', ''];
    $filteredname = str_replace($replacefor, $replacewith, $fieldname);
    $data->shortname = 'dc' . strtolower($filteredname);

    $data->mform_isexpanded_id_header_specificsettings = 1;
    $data->mform_isexpanded_id_course_handler_header = 1;

    $data->categoryid = $categoryid;
    $data->type = $fieldtype;
    $data->id = 0;

    // Config común (puedes ajustar defaults).
    $configdata = [
        'required' => 0,
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2, 
    ];

    switch ($fieldtype) {
        case 'checkbox':
            $configdata['checkbydefault'] = 0;
            break;
        case 'date':
            $configdata['includetime'] = 0;
            // Estos límites son opcionales; déjalos en 0 (sin límite).
            $configdata['mindate'] = 0;
            $configdata['maxdate'] = 0;
            break;
        case 'select':
            // Debes enviar 'options' como string separado por líneas y 'defaultvalue'.
            $configdata['options'] = "Opción 1\nOpción 2";
            $configdata['defaultvalue'] = 'Opción 1';
            break;
        case 'text':
            $configdata['defaultvalue'] = '';
            $configdata['displaysize'] = 50;
            $configdata['maxlength'] = 1333;
            $configdata['ispassword'] = 0;
            break;
        case 'textarea':
            $configdata['defaultvalue_editor'] = [];
            break;
        default:
            throw new \moodle_exception('invalidfieldtype', 'core_customfield');
    }

    // Sobrescribe con opciones personalizadas si se pasan.
    foreach ($options as $k => $v) {
        $configdata[$k] = $v;
    }

    $data->configdata = $configdata;
    return $data;
}

/**
 * Crea (si no existe) un campo personalizado en la categoría dada.
 *
 * @param int $categoryid
 * @param string $fieldname
 * @param string $fieldtype
 * @param array $options
 * @param string $description
 * @return void
 */
function datacurso_create_custom_field(int $categoryid, string $fieldname, string $fieldtype,
                                       array $options = [], string $description = ''): void {
    try {
        $config = datacurso_get_customfield_data($categoryid, $fieldname, $fieldtype, $options, $description);

        $category = \core_customfield\category_controller::create($categoryid);
        $field = \core_customfield\field_controller::create(0, (object)['type' => $fieldtype], $category);

        $handler = $field->get_handler();
        $handler->save_field_configuration($field, $config);
    } catch (\Throwable $e) {
        debugging('datacurso_create_custom_field error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Crea/actualiza la categoría "Datacurso Custom Fields" y provisiona los campos.
 *
 * @param string $mode 'install'|'update'
 * @return void
 */
function datacurso_course_custom_fields(string $mode = 'update'): void {
    global $DB;

    // 1) Crear/Ubicar categoría.
    $categoryid = (int)get_config('local_datacurso', DATACURSO_CF_CATEGORY_CONFIGKEY);

    if ($mode === 'install') {
        if (!$DB->record_exists('customfield_category', ['name' => DATACURSO_CF_CATEGORY_NAME])) {
            $categoryid = datacurso_create_customfield_category(DATACURSO_CF_CATEGORY_NAME);
            set_config(DATACURSO_CF_CATEGORY_CONFIGKEY, $categoryid, 'local_datacurso');
        } else {
            $cat = $DB->get_record('customfield_category', ['name' => DATACURSO_CF_CATEGORY_NAME], '*', MUST_EXIST);
            $categoryid = (int)$cat->id;
            set_config(DATACURSO_CF_CATEGORY_CONFIGKEY, $categoryid, 'local_datacurso');
        }
    } else {
        if ($DB->record_exists('customfield_category', ['name' => DATACURSO_CF_CATEGORY_NAME])) {
            $cat = $DB->get_record('customfield_category', ['name' => DATACURSO_CF_CATEGORY_NAME], '*', MUST_EXIST);
            $categoryid = (int)$cat->id;
            set_config(DATACURSO_CF_CATEGORY_CONFIGKEY, $categoryid, 'local_datacurso');
        } else {
            // Si alguien borró la categoría a mano, la recreamos.
            $categoryid = datacurso_create_customfield_category(DATACURSO_CF_CATEGORY_NAME);
            set_config(DATACURSO_CF_CATEGORY_CONFIGKEY, $categoryid, 'local_datacurso');
        }
    }
}

/**
 * Devuelve la definición "fuente de verdad" de los campos que DEBEN existir.
 * Aquí pones la lista de campos que quieres mantener en cada upgrade.
 */
function datacurso_target_customfields_def(): array {
    return [
        [
            'fieldname'   => 'Elige el modelo a utilizar',
            'type'        => 'select',
            'options'     => [
                'options'      => "Cargando modelos...",
                'defaultvalue' => "Cargando modelos...",
            ],
            'description' => '',
        ],
        [
            'fieldname'   => 'Define el PROGRAMA al que esta asociado la asignatura.',
            'type'        => 'text',
            'description' => '',
        ],
        [
            'fieldname'   => 'Selecciona el SEMESTRE en el que se impartirá esta asignatura.',
            'type'        => 'select',
            'options'     => [
                'options'      => "I\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX",
                'defaultvalue' => 'I',
            ],
            'description' => '',
        ],
        [
            'fieldname'   => 'Elige el NIVEL DE FORMACIÓN más adecuado para tu asignatura.',
            'type'        => 'select',
            'options'     => [
                'options'      => "Técnica Profesional\nTecnológico\nProfesional Universitario",
                'defaultvalue' => 'Técnica Profesional',
            ],
            'description' => '',
        ],
        [
            'fieldname'   => 'Define el OBJETIVO DE LA FORMACIÓN en esta asignatura.',
            'type'        => 'textarea',
            'description' => '',
        ],
        [
            'fieldname'   => 'Define una DESCRIPCIÓN DE LA ASIGNATURA para la asignatura.',
            'type'        => 'textarea',
            'description' => '',
        ],
        [
            'fieldname'   => 'Define el CONTENIDO de la asignatura.',
            'type'        => 'textarea',
            'description' => '',
        ],
        [
            'fieldname'   => 'Define los RESULTADOS DE APRENDIZAJE esperados para la asignatura.',
            'type'        => 'textarea',
            'description' => '',
        ],
        [
            'fieldname'   => 'Este campo generará un resumen basado en la información proporcionada previamente, ofreciendo un contexto claro para las siguientes sugerencias de la inteligencia artificial.',
            'type'        => 'textarea',
            'description' => '',
        ],
        [
            'fieldname'   => 'Banner principal de la asignatura',
            'type'        => 'textarea',
            'description' => '',
        ]
    ];
}

/**
 * Sincroniza TODOS los custom fields de la categoría Datacurso:
 * - Crea los que falten
 * - Actualiza nombre/opciones/descripcion de los existentes
 * - Elimina los que ya no están en la definición objetivo
 */
function datacurso_sync_customfields(): void {
    global $DB;

    $categoryid = (int)get_config('local_datacurso', DATACURSO_CF_CATEGORY_CONFIGKEY);
    if (!$categoryid) {
        // Si alguien borró la categoría, la recreamos.
        $categoryid = datacurso_create_customfield_category(DATACURSO_CF_CATEGORY_NAME);
        set_config(DATACURSO_CF_CATEGORY_CONFIGKEY, $categoryid, 'local_datacurso');
    }

    $target = datacurso_target_customfields_def();

    // Build mapa de shortnames objetivo => definición.
    $want = [];
    foreach ($target as $cf) {
        $sn = 'dc' . strtolower(str_replace([' ', '(', ')'], ['', '', ''], $cf['fieldname']));
        $want[$sn] = $cf;
    }

    // Campos existentes en la categoría.
    $existing = $DB->get_records('customfield_field', ['categoryid' => $categoryid], '', 'id, type, name, shortname, description, categoryid');

    // 1) ELIMINAR los que ya no están en $want.
    $category = \core_customfield\category_controller::create($categoryid);
    foreach ($existing as $rec) {
        if (!array_key_exists($rec->shortname, $want)) {
            $field = \core_customfield\field_controller::create($rec->id, (object)['type' => $rec->type], $category);
            try {
                $field->delete();
            } catch (\Throwable $e) {
                debugging('No se pudo eliminar el campo '.$rec->shortname.': '.$e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    $existing = $DB->get_records('customfield_field', ['categoryid' => $categoryid], '', 'id, type, name, shortname, categoryid');

    foreach ($want as $shortname => $cfg) {
        $options = $cfg['options'] ?? [];
        $desc    = $cfg['description'] ?? '';

        $found = null;
        foreach ($existing as $rec) {
            if ($rec->shortname === $shortname) {
                $found = $rec;
                break;
            }
        }

        if ($found) {
            $fieldtype = $cfg['type'];
            $category  = \core_customfield\category_controller::create($categoryid);
            $field     = \core_customfield\field_controller::create($found->id, (object)['type' => $fieldtype], $category);

            $payload = datacurso_get_customfield_data($categoryid, $cfg['fieldname'], $fieldtype, $options, $desc);
            $payload->id = $found->id;

            $handler = $field->get_handler();
            try {
                $handler->save_field_configuration($field, $payload);

                if ($fieldtype === 'select') {
                    global $DB;
                    $rec = $DB->get_record('customfield_field', ['id' => $found->id], '*', MUST_EXIST);
                    $rec->configdata = json_encode($payload->configdata); // guardamos nuevo configdata
                    $DB->update_record('customfield_field', $rec);
                }
            } catch (\Throwable $e) {
                debugging('No se pudo actualizar el campo '.$shortname.': '.$e->getMessage(), DEBUG_DEVELOPER);
            }

        }

    }
}


/**
 * Obtiene el rol del usuario en el curso actual
 */
function local_datacurso_get_user_role_in_course() {
    global $PAGE, $COURSE, $USER;
    
    if (!isset($COURSE) || $COURSE->id <= 1) {
        return 'Estudiante';
    }
    
    $context = context_course::instance($COURSE->id);
    
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

/**
 * Verifica si estamos en un contexto de curso
 */
function local_datacurso_is_course_context() {
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

