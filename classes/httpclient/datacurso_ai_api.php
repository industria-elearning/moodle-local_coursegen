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

use moodle_exception;
use local_datacurso\httpclient\datacurso_api;

/**
 * HTTP client for DataCurso AI API.
 *
 * Handles authentication and request/response processing
 * with the DataCurso backend AI endpoints.
 *
 * @package    local_datacurso
 * @copyright  2025 ...
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datacurso_ai_api {

    /**
     * Base URL for the API.
     * @var string
     */
    private $baseurl;

    /**
     * Authentication token for API calls.
     * @var string
     */
    private $token;

    /**
     * Constructor. Initializes API client and retrieves token.
     */
    public function __construct() {
        $this->baseurl = rtrim(get_config('local_datacurso', 'aiurl'), '/');

        // Reutilizar token del cliente normal.
        $backend = new datacurso_api();
        $this->token = $this->get_token_from_backend($backend);
    }

    /**
     * Retrieve token from backend client.
     *
     * @param datacurso_api $backend API backend client.
     * @return string Token string.
     */
    private function get_token_from_backend(datacurso_api $backend): string {
        // El backend ya guarda el token en cache → se lo pedimos con reflexión interna.
        $reflect = new \ReflectionClass($backend);
        $prop = $reflect->getProperty('token');
        $prop->setAccessible(true);
        return $prop->getValue($backend);
    }

    /**
     * Send POST request to AI backend.
     *
     * @param string $endpoint API endpoint.
     * @param array $data Payload to send.
     * @return array Response array.
     */
    public function post(string $endpoint, array $data = []): array {
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
            throw new \moodle_exception("cURL error: $error", 'local_datacurso');
        }

        if ($httpcode >= 400) {
            throw new \moodle_exception("Request failed with code $httpcode. Response: $response", 'local_datacurso');
        }

        $decoded = json_decode($response, true);

        // Si no es JSON, devolvemos como texto y lo procesamos.
        if ($decoded === null) {
            return [
                'data' => $this->parse_stream_response($response),
            ];
        }

        // Si es JSON pero contiene el formato stream en alguna clave, lo procesamos.
        return $this->process_response($decoded);
    }

    /**
     * Process the response to handle stream format.
     *
     * @param array $response The decoded API response, possibly containing stream-formatted data.
     * @return array Processed response with parsed stream data if applicable.
     */
    private function process_response(array $response): array {
        // Si la respuesta contiene 'data' con formato stream, lo procesamos.
        if (isset($response['data']) && is_string($response['data']) &&
            strpos($response['data'], 'id:') !== false &&
            strpos($response['data'], 'data:') !== false) {
            $response['data'] = $this->parse_stream_response($response['data']);
        }

        // Si la respuesta es un array de objetos con formato stream.
        if (isset($response[0]) && is_array($response[0]) &&
            isset($response[0]['data']) && is_string($response[0]['data']) &&
            strpos($response[0]['data'], 'id:') !== false) {

            $html = '';
            foreach ($response as $chunk) {
                if (isset($chunk['data'])) {
                    $html .= $this->parse_stream_response($chunk['data']);
                }
            }
            return ['data' => $html];
        }

        return $response;
    }

    /**
     * Parse the stream response format into HTML.
     *
     * Expected format example:
     *   id: 0
     *   data: "<div>"
     *
     * @param string $streamresponse Raw stream response as a string.
     * @return string Concatenated HTML extracted from the stream response.
     */
    private function parse_stream_response(string $streamresponse): string {
        $html = '';

        // Dividir por líneas y procesar.
        $lines = explode("\n", $streamresponse);

        foreach ($lines as $line) {
            $line = trim($line);

            if (strpos($line, 'data:') === 0) {
                // Extraer el contenido de data.
                $datacontent = trim(substr($line, 5));

                // Remover comillas alrededor si las tiene.
                if (preg_match('/^"(.*)"$/', $datacontent, $matches)) {
                    $datacontent = $matches[1];
                }

                // Reemplazar secuencias de escape.
                $decodedcontent = str_replace(
                    ['\\n', '\\t', '\\r', '\\"'],
                    ["\n", "\t", "\r", '"'],
                    $datacontent
                );

                // Saltar tokens especiales y vacíos.
                if ($decodedcontent !== '[DONE]' && $decodedcontent !== '') {
                    $html .= $decodedcontent;
                }
            }
        }

        return $html;
    }

    /**
     * Alternative parser for stream response using regex.
     *
     * @param string $streamresponse Raw stream response as a string.
     * @return string Concatenated HTML extracted from the stream response.
     */
    private function parse_stream_response_regex(string $streamresponse): string {
        $html = '';

        // Patrón para extraer todos los contenidos de data.
        $pattern = '/data:\s*"([^"]*)"/';

        if (preg_match_all($pattern, $streamresponse, $matches)) {
            foreach ($matches[1] as $datacontent) {
                // Reemplazar secuencias de escape.
                $decodedcontent = str_replace(
                    ['\\n', '\\t', '\\r', '\\"'],
                    ["\n", "\t", "\r", '"'],
                    $datacontent
                );

                // Saltar tokens especiales y vacíos.
                if ($decodedcontent !== '[DONE]' && $decodedcontent !== '') {
                    $html .= $decodedcontent;
                }
            }
        }

        return $html;
    }

}
