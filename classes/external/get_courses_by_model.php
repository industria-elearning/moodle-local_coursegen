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
 * Class assign_course_to_user
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
     * Make request to datacurso backend to assign a course to the user
     *
     * @param int $pedagogic_model The pedagogic model id.
     * @return array
     */
    public static function execute(int $pedagogicmodel): array {
        // Validate all of the parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $pedagogicmodel,
        ]);

        $datacursoapi = new datacurso_api();

        // Make the request to the backend API to assign the course
        return $datacursoapi->get('/v3/course-administrator', [
            'id' => $pedagogicmodel,
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new \external_value(PARAM_TEXT, 'The status of the course assignment'),
            'message' => new \external_value(PARAM_TEXT, 'Additional message regarding the assignment'),
        ]);
    }

}
