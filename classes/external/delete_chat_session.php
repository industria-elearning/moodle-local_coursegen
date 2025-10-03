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
 * External API for deleting Tutor-IA chat sessions
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
use local_datacurso\httpclient\tutoria_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class delete_chat_session
 *
 * Deletes a Tutor-IA chat session to free up resources.
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_chat_session extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_TEXT, 'Chat session ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Delete a Tutor-IA chat session.
     *
     * @param string $sessionid Session ID to delete.
     * @return array Deletion status.
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function execute($sessionid): array {
        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
        ]);

        // Initialize Tutor-IA API client.
        $tutoriaapi = new tutoria_api();

        try {
            // Attempt to delete the session.
            $result = $tutoriaapi->delete_session($params['sessionid']);

            return [
                'deleted' => $result['deleted'] ?? false,
            ];
        } catch (\Exception $e) {
            // If deletion fails, log but don't throw (session might already be expired).
            debugging('Failed to delete Tutor-IA session: ' . $e->getMessage(), DEBUG_DEVELOPER);

            return [
                'deleted' => false,
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Session deleted successfully'),
        ]);
    }
}
