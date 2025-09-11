<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_datacurso
 * @category    string
 * @copyright   Josue <josue@datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['baseurl'] = 'API Base URL';
$string['baseurl_desc'] = 'Enter the base URL of the DataCurso API with version. e.g. https://api.datacurso.com/api/v3';
$string['cachedef_apitoken'] = 'Cache for API token obtained from DataCurso';
$string['course_content_field'] = 'Define el CONTENIDO de la asignatura.';
$string['course_description_field'] = 'Define una DESCRIPCIÓN DE LA ASIGNATURA para la asignatura.';
$string['course_structure_field'] = 'Define la estructuración de la asigntura.';
$string['course_summary_field'] = 'Define el resumen del curso.';
$string['custom_fields_header'] = 'Datacurso';
$string['custom_formation_select_field'] = 'Elige el NIVEL DE FORMACIÓN más adecuado para tu asignatura.';
$string['custom_model_select_field'] = 'Elige el modelo a utilizar';
$string['custom_program_text_field'] = 'Define el PROGRAMA al que esta asociado la asignatura.';
$string['custom_semester_select_field'] = 'Selecciona el SEMESTRE en el que se impartirá esta asignatura.';
$string['custom_text_field_help'] = 'Ingresa un texto personalizado para este curso.';
$string['error_invalid_number'] = 'Número invalido';
$string['error_text_too_short'] = 'El texto debe tener al menos 3 caracteres.';
$string['formation_option1'] = 'Técnica Profesional';
$string['formation_option2'] = 'Tecnológico';
$string['formation_option3'] = 'Profesional Universitario';
$string['generalsettings'] = 'General Settings';
$string['learning_outcomes_field'] = 'Define los RESULTADOS DE APRENDIZAJE esperados para la asignatura.';
$string['nocoursesavailable'] = 'No tienes cursos disponibles en este modelo.';
$string['number_of_modules_field'] = '¿Cuántos módulos o temas tendrá el curso?';
$string['option1'] = 'Modelo 1';
$string['option2'] = 'Modelo 2';
$string['option3'] = 'Modelo 3';
$string['pluginname'] = 'DataCurso';
$string['semester_option1'] = 'I';
$string['semester_option10'] = 'X';
$string['semester_option2'] = 'II';
$string['semester_option3'] = 'III';
$string['semester_option4'] = 'IV';
$string['semester_option5'] = 'V';
$string['semester_option6'] = 'VI';
$string['semester_option7'] = 'VII';
$string['semester_option8'] = 'VIII';
$string['semester_option9'] = 'IX';
$string['tenantid'] = 'Tenant ID';
$string['tenantid_desc'] = 'Enter your tenant ID here.';
$string['tenanttoken'] = 'Tenant Token';
$string['tenanttoken_desc'] = 'Enter your tenant token here.';
$string['addactivityai_arialabel'] = 'AI assistant to create resources/activities';
$string['addactivityai_created_cmid'] = 'Resource/activity created. (cmid: {$a})';
$string['addactivityai_created_named'] = 'Created: {$a->type} "{$a->name}"';
$string['addactivityai_done'] = 'Done! The resource/activity was created.';
$string['addactivityai_error'] = 'An error occurred while creating the resource. Please try again or modify the prompt.';
$string['addactivityai_faildefault'] = 'It was not possible to create the resource.';
$string['addactivityai_label'] = 'Describe what you need';
$string['addactivityai_modaltitle'] = 'Create resource/activity with AI';
$string['addactivityai_placeholder'] = 'Describe what resource or activity you want to create';
$string['addactivityai_warnings_prefix'] = 'Warnings: {$a}';
$string['addactivityai_welcome'] = 'Hi! Tell me what resource or activity you need and I will create it in your course. 😊';
$string['addactivitywithia'] = 'Add activity or resource with AI';
$string['send'] = 'Send';
$string['training_objective_field'] = 'Define el OBJETIVO DE LA FORMACIÓN en esta asignatura.';
$string['unauthorized'] = 'Unauthorized access';
$string['error_generating_resource'] = 'There was a problem generating the requested resource. Please try again later.';
$string['resource_created'] = 'Resource {$a} created successfully.';
$string['choosemodel'] = 'Choose model';
$string['error_file_too_large'] = 'The file size exceeds the maximum allowed (10MB).';
$string['error_http_code'] = 'HTTP error code: {$a}';
$string['error_no_file_found'] = 'No file was found to upload.';
$string['error_not_pdf'] = 'The uploaded file must be a PDF.';
$string['error_upload_failed'] = 'Failed to upload syllabus to AI service: {$a}';
$string['syllabus_pdf_field'] = 'Upload Syllabus PDF';
$string['syllabus_pdf_field_help'] = 'Upload a PDF file containing the course syllabus. This will be sent to the AI for context analysis. Maximum file size: 10MB.';
$string['datacurso:view_syllabus'] = 'View syllabus';
$string['apitoken'] = 'API Token';
$string['apitoken_desc'] = 'Enter your API token here.';
$string['error_file_too_large'] = 'El tamaño del archivo excede el máximo permitido (10 MB).';
$string['error_http_code'] = 'Código de error HTTP: {$a}';
$string['error_no_file_found'] = 'No se encontró ningún archivo para subir.';
$string['error_not_pdf'] = 'El archivo cargado debe ser un PDF.';
$string['error_upload_failed'] = 'Error al subir el plan de estudios al servicio de IA: {$a}';

$string['syllabus_pdf_field'] = 'Subir plan de estudios (PDF)';
$string['syllabus_pdf_field_help'] = 'Sube un archivo PDF que contenga el plan de estudios del curso. Este será enviado a la IA para análisis de contexto. Tamaño máximo del archivo: 10 MB.';

$string['datacurso:view_syllabus'] = 'Ver plan de estudios';

$string['apitoken'] = 'Token de la API';
$string['apitoken_desc'] = 'Introduce aquí tu token de API.';

