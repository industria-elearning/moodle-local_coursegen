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

namespace local_datacurso\external;

use context_course;
use core_course_external;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_datacurso\mod_settings\base_settings;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/externallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

/**
 * Class create_mod
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_mod extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'prompt' => new external_value(PARAM_TEXT, 'Prompt to create module'),
            'generateimages' => new external_value(PARAM_INT, '1 to generate images, 0 to not generate images', VALUE_OPTIONAL),
            'beforemod' => new external_value(PARAM_INT, 'Before module id', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Create course context for ask question to chatbot based in that information.
     *
     * @param string $courseid Course id where the module will be created
     * @param int $sectionnum Section number where the module will be created
     * @param string $prompt Prompt to create module
     * @param int $generateimages 1 indicates AI could generate images, 0 indicates AI could not generate images
     * @param int $beforemod Before module id where the module will be created
     *
     * @return array
     */
    public static function execute(int $courseid, int $sectionnum, string $prompt, int $generateimages, ?int $beforemod) {
        global $CFG, $DB, $COURSE;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'courseid' => $courseid,
                'sectionnum' => $sectionnum,
                'prompt' => $prompt,
                'generateimages' => $generateimages,
                'beforemod' => $beforemod,
            ]);

            $courseid = $params['courseid'];
            $sectionnum = $params['sectionnum'];
            $prompt = $params['prompt'];
            $generateimages = $params['generateimages'];
            $beforemod = $params['beforemod'];

            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            self::validate_context($context);

            $apitoken = get_config('local_datacurso', 'apitoken');
            $baseurl = get_config('local_datacurso', 'baseurl');

            // This request may take a long time depending on the complexity of the prompt that the AI ​​has to resolve.
            \core_php_time_limit::raise();
            raise_memory_limit(MEMORY_EXTRA);
            // Release the session so other tabs in the same session are not blocked.
            \core\session\manager::write_close();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseurl, '/') . '/resources/create-mod');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . $apitoken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'site_id' => md5($CFG->wwwroot),
                'course_id' => $courseid,
                'message' => $prompt,
                'timezone' => \core_date::get_user_timezone(),
                'generate_images' => $generateimages == 1,
            ]));
            $result = curl_exec($ch);

            if (!$result) {
                $curlerror = curl_error($ch);
                debugging("CURL request failed while creating resource. Error: {$curlerror}");
                curl_close($ch);
                return [
                    'ok' => false,
                    'message' => get_string('error_generating_resource', 'local_datacurso'),
                    'log' => "CURL request failed while creating resource. Error: {$curlerror}",
                ];
            }
            curl_close($ch);

            $result = json_decode($result, true);
            if (!isset($result['result']['resource_type'])) {
                debugging("Invalid response from AI service. Response: " . json_encode($result));
                return [
                    'ok' => false,
                    'message' => get_string('error_generating_resource', 'local_datacurso'),
                    'log' => "Invalid response from AI service. Response: " . json_encode($result),
                ];
            }

            $modname = $result['result']['resource_type'];

            $modmoodleform = "$CFG->dirroot/mod/$modname/mod_form.php";
            if (!file_exists($modmoodleform)) {
                debugging("Form file not found for module: {$modname}");
                return [
                    'ok' => false,
                    'message' => get_string('error_generating_resource', 'local_datacurso'),
                    'log' => "Form file not found for module: {$modname}",
                ];
            }
            require_once($modmoodleform);

            list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($course, $modname, $sectionnum);

            $mformclassname = 'mod_'.$modname.'_mod_form';
            $mform = new $mformclassname($data, $cw->section, $cm, $course);

            if (!isset($result['result']['parameters'])) {
                debugging("Missing parameters in service response: " . json_encode($result));
                return [
                    'ok' => false,
                    'message' => get_string('error_generating_resource', 'local_datacurso'),
                    'log' => "Missing parameters in service response: " . json_encode($result),
                ];
            }

            $parameters = (object)$result['result']['parameters'];
            $parameters->section = $sectionnum;
            $parameters->beforemod = $beforemod;

            $newcm = add_moduleinfo($parameters, $course, $mform);

            $modsettings = $parameters->mod_settings;

            $classpath = '\\local_datacurso\\mod_settings\\' . $modname . '_settings';
            if (!empty($modsettings) && class_exists($classpath)) {
                if (is_subclass_of($classpath, base_settings::class)) {

                    /** @var base_settings $modsettingsinstance */
                    $modsettingsinstance = new $classpath($newcm, $modsettings);
                    $modsettingsinstance->add_settings();
                } else {
                    debugging("{$classpath} is not a subclass of \local_datacurso\mod_settings\base_settings");
                }
            }

            $url = course_get_url($course, $cw->section);

            return [
                'ok' => true,
                'message' => get_string('resource_created', 'local_datacurso', $modname),
                'courseurl' => $url->out(false),
            ];

        } catch (\Exception $e) {
            debugging("Unexpected error while creating resource: " . $e->getMessage());
            return [
                'ok' => false,
                'message' => get_string('error_generating_resource', 'local_datacurso'),
                'log' => $e->getMessage(),
            ];
        }
    }


    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Response status from server'),
            'message' => new external_value(PARAM_TEXT, 'Response message from server', VALUE_OPTIONAL),
            'courseurl' => new external_value(PARAM_URL, 'Course url from server', VALUE_OPTIONAL),
            'log' => new external_value(PARAM_RAW, 'Log from server', VALUE_OPTIONAL),
        ]);
    }
}
