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
            'message' => new external_value(PARAM_TEXT, 'Message to create module'),
            'section' => new external_value(PARAM_INT, 'Section number'),
        ]);
    }

    /**
     * Create course context for ask question to chatbot based in that information.
     *
     * @param string $courseid
     * @param string $message
     * @param int $section
     *
     * @return array
     */
    public static function execute(int $courseid, string $message, int $section) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'message' => $message,
            'section' => $section,
        ]);

        $courseid = $params['courseid'];
        $message = $params['message'];
        $section = $params['section'];

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        $context = context_course::instance($course->id);
        self::validate_context($context);

        // $modmoodleform = "$CFG->dirroot/mod/$modname/mod_form.php";
        // if (file_exists($modmoodleform)) {
        // require_once($modmoodleform);
        // } else {
        // throw new \moodle_exception('noformdesc');
        // }

        $lowermsg = strtolower($message);

        $modsdirectory = $CFG->dirroot . '/mod';

        $modfolders = scandir($modsdirectory);

        $modname = '';
        foreach ($modfolders as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }

            $modmoodleform = $modsdirectory . '/' . $folder . '/mod_form.php';

            if (strpos($lowermsg, strtolower($folder)) !== false && file_exists($modmoodleform)) {
                require_once($modmoodleform);
                $modname = $folder;
                break;
            }
        }

        list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($course, $modname, $section);

        $mformclassname = 'mod_'.$modname.'_mod_form';
        $mform = new $mformclassname($data, $cw->section, $cm, $course);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://server:3000/api/v1/moodle/generate/mod');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'courseId' => $courseid,
            'message' => $message,
            'section' => $section,
        ]));
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            throw new \moodle_exception('error_curl');
        }
        $result = json_decode($result, true);

        add_moduleinfo((object)$result['response'], $course, $mform);

        $url = course_get_url($course, $cw->section);

        return [
            'ok' => true,
            'message' => 'Modulo '. $modname . ' creado correctamente',
            'courseurl' => $url->out(false),
        ];
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
        ]);
    }
}
