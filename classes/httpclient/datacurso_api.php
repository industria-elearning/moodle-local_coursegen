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
 * DataCurso API HTTP client for external service communication
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\httpclient;

use cache;
use moodle_exception;

/**
 * Class datacurso_api
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datacurso_api {
    /** @var string $baseurl Base URL of the DataCurso API. */
    private $baseurl;

    /** @var string $tokenendpoint Endpoint for fetching JWT token. */
    private $tokenendpoint;

    /** @var string|null $token JWT token currently in use. */
    private $token = null;

    /** @var cache $cache Cache instance for storing token and expiration. */
    private $cache;

    /**
     * Constructor to initialize the API client.
     */
    public function __construct() {
        $this->baseurl = rtrim(get_config('local_datacurso', 'baseurl'), '/');
        $this->tokenendpoint = '/v3/auth/moodle-login';
        $this->cache = cache::make('local_datacurso', 'apitoken');
        $this->token = $this->get_valid_token();
    }

    /**
     * Send a GET request to the API.
     *
     * @param string $endpoint The API endpoint (relative path).
     * @param array $queryparamms Optional query parameters to include in the request.
     * @param array $headers Optional additional headers.
     * @param bool $authrequired Whether to include the Authorization header.
     * @return array The API response.
     */
    public function get(string $endpoint, array $queryparamms =[] , array $headers = [], bool $authrequired = true): array {
        return $this->request_with_token_refresh('GET', $endpoint, $queryparamms, $headers, $authrequired);
    }

    /**
     * Send a POST request to the API.
     *
     * @param string $endpoint The API endpoint (relative path).
     * @param array $data The data to send in the request body.
     * @param array $headers Optional additional headers.
     * @param bool $authrequired Whether to include the Authorization header.
     * @return array The API response.
     */
    public function post(string $endpoint, array $data = [], array $headers = [], bool $authrequired = true): array {
        return $this->request_with_token_refresh('POST', $endpoint, $data, $headers, $authrequired);
    }

    /**
     * Attempt to make a request. If unauthorized and auth is required, refresh the token and retry once.
     *
     * @param string $method HTTP method ('GET' or 'POST').
     * @param string $endpoint API endpoint.
     * @param array|null $data Request payload (for POST).
     * @param array $headers HTTP headers.
     * @param bool $authrequired Whether authentication is needed.
     * @return array The API response.
     * @throws moodle_exception On repeated failure or other errors.
     */
    private function request_with_token_refresh(string $method, string $endpoint, ?array $data, array $headers,
            bool $authrequired): array {
        try {
            return $this->make_request($method, $endpoint, $data, $headers, $authrequired);
        } catch (moodle_exception $e) {
            // Validate if the error is due to an expired or invalid token.
            if ($authrequired && $e->errorcode === 'unauthorized' && !$this->is_token_valid()) {
                debugging("Token expired or unauthorized, fetching new token and retrying.", DEBUG_DEVELOPER);
                $this->token = $this->fetch_token();
                return $this->make_request($method, $endpoint, $data, $headers, $authrequired);
            }
            debugging("Request failed: " . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }
    }

    /**
     * Prepares and sends a request, optionally adding auth headers.
     *
     * @param string $method HTTP method ('GET' or 'POST').
     * @param string $endpoint API endpoint.
     * @param array|null $data Payload for POST requests.
     * @param array $headers Optional headers.
     * @param bool $authrequired Whether to attach the JWT token.
     * @return array The API response.
     */
    private function make_request(string $method, string $endpoint, ?array $data, array $headers, bool $authrequired): array {
        $url = $this->baseurl . '/' . ltrim($endpoint, '/');

        if ($authrequired) {
            $this->ensure_valid_token();
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }

        return $this->curl_request($url, $method, $data, $headers, $authrequired);
    }

    /**
     * Execute the actual cURL request with the given parameters.
     *
     * @param string $url Full request URL.
     * @param string $method HTTP method.
     * @param array|null $data Request payload.
     * @param array $headers HTTP headers.
     * @param bool $authrequired Whether token is required for retry logic.
     * @return array Response of the request.
     * @throws moodle_exception On HTTP error or cURL failure.
     */
    private function curl_request(string $url, string $method, ?array $data, array $headers, bool $authrequired): array {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if ($method === 'GET') {
            if ($data) {
                $query = http_build_query($data);
                $url .= '?'. $query;
            }
        }

        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            debugging("cURL error: $error", DEBUG_DEVELOPER);
            throw new moodle_exception("cURL error: $error");
        }

        curl_close($ch);

        if ($authrequired && $httpcode === 401) {
            debugging("Received 401 Unauthorized", DEBUG_DEVELOPER);
            throw new moodle_exception('unauthorized', 'local_datacurso');
        }

        return json_decode($response, true);
    }

    /**
     * Fetch a new JWT token and store it along with its expiration date in cache.
     *
     * @return string The JWT token.
     * @throws moodle_exception If the token endpoint is misconfigured or the response is invalid.
     */
    public function fetch_token(): string {
        global $USER;

        if (empty($this->tokenendpoint)) {
            throw new moodle_exception('Token endpoint not configured');
        }

        $credentials = [
            'email' => $USER->email,
            'username' => $USER->username,
            'fullName' => $USER->firstname . ' ' . $USER->lastname,
            'tenantId' => get_config('local_datacurso', 'tenantid'),
            'token' => get_config('local_datacurso', 'tenanttoken'),
        ];

        $response = $this->post($this->tokenendpoint, $credentials, [], false);

        if (empty($response['jwt']) || empty($response['expirationDate'])) {
            throw new moodle_exception('Invalid token response: missing jwt or expirationDate');
        }

        $this->cache->set('jwt', $response['jwt']);
        $this->cache->set('expiration', $response['expirationDate']);

        return $response['jwt'];
    }

    /**
     * Return a valid cached token, or null if expired or missing.
     *
     * @return string|null Cached token if valid.
     */
    private function get_valid_token(): ?string {
        $token = $this->cache->get('jwt');
        $expiration = $this->cache->get('expiration');

        if ($token && $expiration && strtotime($expiration) > time() + 60) {
            return $token;
        }

        return null;
    }

    /**
     * Ensure a valid token is available, fetching a new one if necessary.
     */
    private function ensure_valid_token(): void {
        if (!$this->is_token_valid()) {
            debugging("Token is missing or expired. Fetching a new one.", DEBUG_DEVELOPER);
            $this->token = $this->fetch_token();
        }
    }

    /**
     * Check if the current token is valid based on its expiration.
     *
     * @return bool True if still valid for at least 60 seconds.
     */
    private function is_token_valid(): bool {
        $expiration = $this->cache->get('expiration');
        $token = $this->cache->get('jwt');
        return $token && $expiration && strtotime($expiration) > (time() + 60);
    }
}
