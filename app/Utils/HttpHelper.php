<?php
/**
 * HTTP Request Helper
 * Handles API requests and responses
 */

namespace App\Utils;

use App\Utils\Logger;

class HttpHelper {
    
    /**
     * Make HTTP request using cURL
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $url Full URL to request
     * @param array $headers HTTP headers
     * @param string|array|null $body Request body
     * @param int $timeout Timeout in seconds
     * @return array Response data ['status' => int, 'body' => string, 'headers' => array]
     */
    public static function request($method = 'GET', $url, $headers = [], $body = null, $timeout = 30) {
        try {
            $ch = curl_init();
            
            // Set basic options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // Set method
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

            // Add body if present
            if ($body !== null) {
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            // Set headers
            $defaultHeaders = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PaymentHub/1.0'
            ];
            
            $allHeaders = array_merge($defaultHeaders, $headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

            // Execute request
            $response = curl_exec($ch);
            
            if ($response === false) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            // Parse response
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            return [
                'status' => $statusCode,
                'body' => $responseBody,
                'headers' => static::parseHeaders($responseHeaders)
            ];
            
        } catch (\Exception $e) {
            Logger::logError("HTTP Request Error: " . $e->getMessage(), $e);
            throw $e;
        }
    }

    /**
     * Parse HTTP header string into array
     * 
     * @param string $headerString Raw header string
     * @return array Parsed headers
     */
    private static function parseHeaders($headerString) {
        $headers = [];
        $headerLines = explode("\n", $headerString);
        
        foreach ($headerLines as $line) {
            $line = trim($line);
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        return $headers;
    }

    /**
     * Make GET request
     * 
     * @param string $url URL
     * @param array $params Query parameters
     * @param array $headers Custom headers
     * @return array Response
     */
    public static function get($url, $params = [], $headers = []) {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return self::request('GET', $url, $headers);
    }

    /**
     * Make POST request
     * 
     * @param string $url URL
     * @param array|string $body POST body
     * @param array $headers Custom headers
     * @return array Response
     */
    public static function post($url, $body = null, $headers = []) {
        return self::request('POST', $url, $headers, $body);
    }

    /**
     * Make PUT request
     * 
     * @param string $url URL
     * @param array|string $body PUT body
     * @param array $headers Custom headers
     * @return array Response
     */
    public static function put($url, $body = null, $headers = []) {
        return self::request('PUT', $url, $headers, $body);
    }

    /**
     * Make DELETE request
     * 
     * @param string $url URL
     * @param array $headers Custom headers
     * @return array Response
     */
    public static function delete($url, $headers = []) {
        return self::request('DELETE', $url, $headers);
    }

    /**
     * Make request with Bearer token authentication
     * 
     * @param string $method HTTP method
     * @param string $url URL
     * @param string $token Bearer token
     * @param array|string|null $body Request body
     * @return array Response
     */
    public static function authenticatedRequest($method, $url, $token, $body = null) {
        $headers = [
            'Authorization: Bearer ' . $token,
        ];
        return self::request($method, $url, $headers, $body);
    }

    /**
     * Decode JSON response
     * 
     * @param string $json JSON string
     * @param bool $assoc Return as associative array
     * @return mixed Decoded data
     */
    public static function decodeJson($json, $assoc = true) {
        return json_decode($json, $assoc);
    }

    /**
     * Check if response status indicates success
     * 
     * @param int $status HTTP status code
     * @return bool True if 2xx status
     */
    public static function isSuccess($status) {
        return $status >= 200 && $status < 300;
    }

    /**
     * Check if response status indicates client error
     * 
     * @param int $status HTTP status code
     * @return bool True if 4xx status
     */
    public static function isClientError($status) {
        return $status >= 400 && $status < 500;
    }

    /**
     * Check if response status indicates server error
     * 
     * @param int $status HTTP status code
     * @return bool True if 5xx status
     */
    public static function isServerError($status) {
        return $status >= 500;
    }
}
