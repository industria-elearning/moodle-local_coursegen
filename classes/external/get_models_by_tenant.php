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
 * Class get_models_by_tenant
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_models_by_tenant extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([]);
    }

    /**
     * Make request to datacurso backend to get auth token to this user
     *
     * @return array
     */
    public static function execute(): array {
        // Validate all of the parameters.
        $params = self::validate_parameters(self::execute_parameters(), []);

        $datacursoapi = new datacurso_api();

        return $datacursoapi->get('/models', [], false);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new \external_single_structure([
            'id' => new \external_value(PARAM_INT, 'The model id'),
            'name' => new \external_value(PARAM_TEXT, 'The model name', VALUE_OPTIONAL),
            'shortname' => new \external_value(PARAM_TEXT, 'The model short name', VALUE_OPTIONAL),
            'title' => new \external_value(PARAM_TEXT, 'The model title', VALUE_OPTIONAL),
            'image' => new \external_value(PARAM_TEXT, 'The model image', VALUE_OPTIONAL),
            'description' => new \external_value(PARAM_TEXT, 'The model description', VALUE_OPTIONAL),
            'default' => new \external_value(PARAM_BOOL, 'The model default', VALUE_OPTIONAL),
            'user_id' => new \external_value(PARAM_INT, 'The model user id', VALUE_OPTIONAL),
            'tenant_id' => new \external_value(PARAM_INT, 'The model tenant id', VALUE_OPTIONAL),
            'status' => new \external_value(PARAM_INT, 'The model status', VALUE_OPTIONAL),
            'schemauuid' => new \external_value(PARAM_TEXT, 'The model schema uuid', VALUE_OPTIONAL),
            'version' => new \external_value(PARAM_INT, 'The model version', VALUE_OPTIONAL),
            'parent_id' => new \external_value(PARAM_INT, 'The model parent id', VALUE_OPTIONAL),
            'visible' => new \external_value(PARAM_BOOL, 'The model visible', VALUE_OPTIONAL),
            ]),
            'List of models',
            VALUE_DEFAULT,
            []
        );
    }
}
