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


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class get_auth_token
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_auth_token extends external_api {
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
        global $USER;
        // Validate all of the parameters.
        $params = self::validate_parameters(self::execute_parameters(), []);

        $tenantid = get_config('local_datacurso', 'tenantid');
        $tenanttoken = get_config('local_datacurso', 'tenanttoken');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://webhook:3001/auth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'tenant-token: ' . $tenanttoken,
            'tenant-id: ' . $tenantid,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'user' => $USER->id,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $jsonresponse = json_decode($response);

        return [
            'token' => $jsonresponse->token,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'token' => new \external_value(PARAM_RAW, 'The auth token'),
        ]);
    }
}

