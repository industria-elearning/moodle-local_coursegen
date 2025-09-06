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

/**
 * External API for getting response ia
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\external;

use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use external_multiple_structure;
use local_datacurso\httpclient\datacurso_ai_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class get_response_ia
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_response_ia extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 1),
            'modulenumber' => new external_value(PARAM_INT, 'Module number', VALUE_DEFAULT, 0),
            'lang' => new external_value(PARAM_TEXT, 'Language code', VALUE_DEFAULT, 'es'),
            'question' => new external_value(PARAM_RAW, 'Question or field id', VALUE_DEFAULT, 't63'),
            'formtab' => new external_value(PARAM_TEXT, 'Form tab', VALUE_DEFAULT, 'modules'),
            'formaccordeon' => new external_value(PARAM_TEXT, 'Form accordion', VALUE_DEFAULT, 'module_info'),
        ]);
    }

    /**
     * Make request to DataCurso backend to get AI response for the given course/module/question.
     *
     * @param int $courseid Course ID.
     * @param int $modulenumber Module number within the course.
     * @param string $lang Language code (e.g. 'es', 'en').
     * @param string $question Question text or field identifier.
     * @param string $formtab Form tab identifier.
     * @param string $formaccordeon Form accordion identifier.
     * @return array Associative array containing the AI response HTML.
     */
    public static function execute($courseid, $modulenumber, $lang, $question, $formtab = '', $formaccordeon = ''): array {
        // Validate all of the parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'modulenumber' => $modulenumber,
            'lang' => $lang,
            'question' => $question,
            'formtab' => $formtab,
            'formaccordeon' => $formaccordeon,
        ]);

        $datacursoaiapi = new datacurso_ai_api();

        $payload = [
            'course_id' => $params['courseid'],
            'moduleNumber' => $params['modulenumber'],
            'lang' => $params['lang'],
            'question' => $params['question'],
            'formTab' => $params['formtab'],
            'formAccordeon' => $params['formaccordeon'],
        ];

        $response = $datacursoaiapi->post('/v2/ai-assistance', $payload);

        // Ahora el cliente HTTP ya devuelve el HTML limpio.
        $html = $response['data'] ?? '';

        return [
            'html' => $html,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Full HTML response from IA'),
        ]);
    }
}
