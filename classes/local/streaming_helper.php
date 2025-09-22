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

namespace local_datacurso\local;

/**
 * Helper utilities for streaming URLs.
 *
 * @package    local_datacurso
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class streaming_helper {
    /**
     * Build the streaming URL for a given session ID, adjusting base URL for localhost dev environments.
     *
     * @param string $sessionid
     * @return string streaming URL
     */
    public static function get_streaming_url_for_session(string $sessionid): string {
        global $CFG;

        // Get base URL from config.
        $baseurl = get_config('local_datacurso', 'baseurl');

        // Validate if this is a local site and adjust baseurl to localhost:port.
        if (strpos($CFG->wwwroot, 'http://localhost') === 0) {
            $port = parse_url($baseurl, PHP_URL_PORT) ?? 80;
            $baseurl = "http://localhost:$port";
        }

        // Build streaming URL with session ID.
        $baseurl = rtrim($baseurl, '/');
        return $baseurl . '/api/v1/moodle/plan-course/stream?session_id=' . urlencode($sessionid);
    }
}
