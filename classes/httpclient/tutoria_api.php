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
 * Tutor-IA HTTP client for chat API communication
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\httpclient;

use cache;
use moodle_exception;

/**
 * HTTP client for Tutor-IA Chat API
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tutoria_api {

    /** @var string $baseurl Base URL of the Tutor-IA API. */
    private $baseurl;

    /** @var string $token Authentication token for Tutor-IA API. */
    private $token;

    /** @var cache $cache Cache instance for storing sessions. */
    private $cache;

    /**
     * Constructor to initialize the Tutor-IA API client.
     */
    public function __construct() {
        $this->baseurl = rtrim(get_config('local_datacurso', 'tutoraiapiurl'), '/');
        $this->token = get_config('local_datacurso', 'tutoraitoken');
        $this->cache = cache::make('local_datacurso', 'tutoria_sessions');
    }

    /**
     * Start a new chat session or retrieve an existing one from cache.
     *
     * @param string $siteid Site identifier.
     * @param int $courseid Course ID.
     * @return array Session data including session_id, ready status, and TTL.
     * @throws moodle_exception If the session creation fails.
     */
    public function start_session(string $siteid, int $courseid): array {
        // Check cache first.
        $cachekey = "session_{$siteid}_{$courseid}";
        $cached = $this->cache->get($cachekey);

        if ($cached && $this->is_session_valid($cached)) {
            return $cached;
        }

        // Create new session.
        $response = $this->post('/chat/start', [
            'site_id' => $siteid,
            'course_id' => (string)$courseid,
        ]);

        // Add creation timestamp for validation.
        $response['created_at'] = time();

        // Store in cache with TTL (session TTL minus 1 hour margin).
        $ttl = ($response['session_ttl_seconds'] ?? 604800) - 3600;
        $this->cache->set($cachekey, $response);

        return $response;
    }

    /**
     * Send a message to an existing chat session.
     *
     * @param string $sessionid Session ID.
     * @param string $content Message content.
     * @param array $meta Optional metadata.
     * @return array Response indicating if message was enqueued.
     * @throws moodle_exception If sending fails.
     */
    public function send_message(string $sessionid, string $content, array $meta = []): array {
        return $this->post('/chat/message', [
            'session_id' => $sessionid,
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    /**
     * Build the SSE stream URL for a chat session.
     *
     * @param string $sessionid Session ID.
     * @return string Full stream URL with session parameter.
     */
    public function get_stream_url(string $sessionid): string {
        return $this->baseurl . '/chat/stream?session_id=' . urlencode($sessionid);
    }

    /**
     * Delete a chat session.
     *
     * @param string $sessionid Session ID to delete.
     * @return array Response with deletion status.
     * @throws moodle_exception If deletion fails.
     */
    public function delete_session(string $sessionid): array {
        return $this->delete("/chat/{$sessionid}");
    }

    /**
     * Execute a POST request to the Tutor-IA API.
     *
     * @param string $endpoint API endpoint (relative path).
     * @param array $data Request payload.
     * @return array Decoded response.
     * @throws moodle_exception On request failure.
     */
    private function post(string $endpoint, array $data): array {
        $url = $this->baseurl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new moodle_exception('error_api_request_failed', 'local_datacurso', '', $error);
        }

        if ($httpcode >= 400) {
            throw new moodle_exception('error_http_code', 'local_datacurso', '', $httpcode);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new moodle_exception('error_invalid_api_response', 'local_datacurso');
        }

        return $decoded;
    }

    /**
     * Execute a DELETE request to the Tutor-IA API.
     *
     * @param string $endpoint API endpoint (relative path).
     * @return array Decoded response.
     * @throws moodle_exception On request failure.
     */
    private function delete(string $endpoint): array {
        $url = $this->baseurl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization: Bearer ' . $this->token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new moodle_exception('error_api_request_failed', 'local_datacurso', '', $error);
        }

        if ($httpcode >= 400) {
            throw new moodle_exception('error_http_code', 'local_datacurso', '', $httpcode);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new moodle_exception('error_invalid_api_response', 'local_datacurso');
        }

        return $decoded;
    }

    /**
     * Check if a cached session is still valid.
     *
     * @param array $session Cached session data.
     * @return bool True if session is still valid.
     */
    private function is_session_valid(array $session): bool {
        if (!isset($session['created_at']) || !isset($session['session_ttl_seconds'])) {
            return false;
        }

        $elapsed = time() - $session['created_at'];
        $ttl = $session['session_ttl_seconds'];

        return $elapsed < ($ttl - 3600); // 1 hour margin.
    }
}
