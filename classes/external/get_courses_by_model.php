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

use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use external_multiple_structure;
use local_datacurso\httpclient\datacurso_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class get_courses_by_model
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_courses_by_model extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'id' => new \external_value(PARAM_INT, 'The pedagogic model id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Make request to datacurso backend to get courses by model id
     *
     * @param int $pedagogicmodel The pedagogic model id.
     * @return array List of courses
     */
    public static function execute(int $pedagogicmodel): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $pedagogicmodel,
        ]);

        $datacursoapi = new datacurso_api();

        $res = $datacursoapi->get('/v1/course-administrator', [
            'pedagogic_model' => $pedagogicmodel,
        ]);

        return $res;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                "courses" => new \external_multiple_structure(
                    new \external_single_structure([
                        'id' => new \external_value(PARAM_INT, 'The course id'),
                        'user_id' => new \external_value(PARAM_INT, 'The user id', VALUE_OPTIONAL),
                        'tenant_id' => new \external_value(PARAM_INT, 'The tenant id', VALUE_OPTIONAL),
                        'pedagogic_model' => new \external_value(PARAM_INT, 'The pedagogic_model id', VALUE_OPTIONAL),
                        'title' => new \external_value(PARAM_TEXT, 'The tile course', VALUE_OPTIONAL),
                        'status' => new \external_value(PARAM_INT, 'The course status', VALUE_OPTIONAL),
                        'review_status' => new \external_value(PARAM_TEXT, 'The review status', VALUE_OPTIONAL),
                        'full_name' => new \external_value(PARAM_TEXT, 'The fullname course', VALUE_OPTIONAL),
                        'username' => new \external_value(PARAM_TEXT, 'The username user', VALUE_OPTIONAL),
                        'updated_at' => new \external_value(PARAM_TEXT, 'The update course', VALUE_OPTIONAL),
                        'created_at' => new \external_value(PARAM_TEXT, 'The created course', VALUE_OPTIONAL),
                        'assisted_by_ai' => new \external_value(PARAM_BOOL, 'The assisted by ai course', VALUE_OPTIONAL),
                        'format' => new \external_value(PARAM_TEXT, 'The format course', VALUE_OPTIONAL),
                        'image' => new \external_value(PARAM_TEXT, 'The image course', VALUE_OPTIONAL),
                    ]), '', VALUE_DEFAULT, []
                ),

                "pagination" => new \external_single_structure([
                    'count' => new \external_value(PARAM_INT, 'The count courses', VALUE_OPTIONAL),
                    'currentPage' => new \external_value(PARAM_INT, 'The currentPage courses', VALUE_OPTIONAL),
                ]),
            ]
        );
    }
}
