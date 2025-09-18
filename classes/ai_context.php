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

namespace local_datacurso;

/**
 * Class ai_context
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_context {
    /**
     * Sube el contenido del modelo instruccional al endpoint de IA.
     *
     * @param model $model Modelo instruccional seleccionado.
     */
    public static function upload_model_to_ai(model $model): void {
        global $CFG;

        try {
            $siteid = md5($CFG->wwwroot);

            // Preparar los datos del POST con el contenido del modelo.
            $postdata = [
                'model_name' => $model->name,
                'model_context' => $model->content,
                'site_id' => $siteid,
            ];

            $apitoken = get_config('local_datacurso', 'apitoken');
            $baseurl = get_config('local_datacurso', 'baseurl');

            // Realizar la petición HTTP con cURL.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseurl, '/') . '/context/upload-model-context');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apitoken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
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

        } catch (\Exception $e) {

            // Mostrar notificación de error al usuario.
            \core\notification::error(get_string('error_upload_failed', 'local_datacurso', $e->getMessage()));
        }
    }

    /**
     * Sube el archivo de sílabo al endpoint de IA.
     *
     * @param int $courseid ID del curso.
     */
    public static function upload_syllabus_to_ai(int $courseid): void {
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

        } catch (\Exception $e) {

            // Mostrar notificación de error al usuario.
            \core\notification::error(get_string('error_upload_failed', 'local_datacurso', $e->getMessage()));
        }
    }
}
