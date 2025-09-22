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

use core_course_external;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_datacurso\httpclient\datacurso_api;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/externallib.php');

/**
 * Class create_course_context
 *
 * @package    local_datacurso
 * @copyright  2025 Wilber Narvaez <wilber@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_course_context extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Create course context for ask question to chatbot based in that information.
     *
     * @param int $courseid
     *
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $courseid) {
        global $CFG;
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);
        $datacursoapi = new datacurso_api();
        $courseid = $params['courseid'];
        $coursecontent = self::get_course_content($courseid);

        $payload = [
            'course_id' => $courseid,
            'site' => md5($CFG->wwwroot),
            'course_content' => $coursecontent,
            'tenant_id' => get_config('local_datacurso', 'tenantid'),
        ];

        $response = $datacursoapi->post('/v1/courses', ['data' => $payload]);

        if(empty($response['data'])) {
            throw new \invalid_parameter_exception('Error creating course context in DataCurso backend');
        }

        return ['ok' => true];
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Response status from server'),
        ]);
    }


    /**
     * Get course content based in course id.
     *
     * @param string $courseid
     *
     * @return array
     * @throws \moodle_exception
     */
    public static function get_course_content($courseid) {
        $coursecontent = core_course_external::get_course_contents($courseid);
        $clean = [];
        foreach ($coursecontent as $content) {
            $clean[] = [
                'sectionnumber' => $content['section'],
                'sectionname' => self::clean_text($content['name']),
                'sectionsummary' => self::clean_text($content['summary']),
                'modules' => self::clean_coursemodules($content['modules']),
            ];
        }
        return $clean;
    }

    /**
     * Clean course modules.
     *
     * @param array $modules
     *
     * @return array
     */
    private static function clean_coursemodules($modules) {
        $clean = [];
        foreach ($modules as $module) {
            $clean[] = [
                'id' => $module['id'],
                'name' => self::clean_text($module['name']),
                'description' => self::clean_text($module['description']),
                'modname' => self::clean_text($module['modname']),
            ];
        }
        return $clean;
    }

    /**
     * Clean text.
     *
     * @param string $text
     *
     * @return string
     */
    private static function clean_text($text) {
        $clean = preg_replace('/\s+/', ' ', strip_tags($text));
        return trim($clean);
    }
}
