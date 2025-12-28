<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Http;

use Exception;

/**
 * WordPress HTTP client implementation.
 *
 * Wraps WordPress HTTP API functions (wp_remote_post, wp_remote_get) to provide
 * a clean interface for dependency injection and testing.
 */
class WordPressHttpClient implements HttpClientInterface
{
    /**
     * Default timeout for HTTP requests in seconds.
     * Set to 90 seconds to accommodate AI provider API calls which can take longer.
     */
    private const DEFAULT_TIMEOUT = 90;

    /**
     * Perform an HTTP POST request using WordPress HTTP API.
     *
     * @param string $url The URL to send the request to
     * @param array<string, string> $headers HTTP headers as key-value pairs
     * @param array<string, mixed> $body Request body data (will be JSON-encoded if array)
     *
     * @return array<string, mixed> The decoded JSON response body
     *
     * @throws Exception If the HTTP request fails or returns a non-2xx status code
     */
    public function post(string $url, array $headers, array $body): array
    {
        $args = [
            'headers' => $headers,
            'body' => is_array($body) ? json_encode($body) : $body,
            'timeout' => self::DEFAULT_TIMEOUT,
            'data_format' => 'body',
        ];

        $response = wp_remote_post($url, $args);

        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            throw new Exception(
                sprintf('HTTP request failed: %s', $response->get_error_message())
            );
        }

        // Check HTTP status code
        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception(
                sprintf(
                    'HTTP request returned status %d: %s',
                    $statusCode,
                    $body ?: 'No response body'
                )
            );
        }

        // Retrieve and decode response body
        $responseBody = wp_remote_retrieve_body($response);
        if (empty($responseBody)) {
            throw new Exception('Empty response body received from server');
        }

        $decodedBody = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                sprintf('Failed to decode JSON response: %s', json_last_error_msg())
            );
        }

        return $decodedBody;
    }

    /**
     * Perform an HTTP GET request using WordPress HTTP API.
     *
     * @param string $url The URL to send the request to
     * @param array<string, string> $headers HTTP headers as key-value pairs
     *
     * @return array<string, mixed> The decoded JSON response body
     *
     * @throws Exception If the HTTP request fails or returns a non-2xx status code
     */
    public function get(string $url, array $headers = []): array
    {
        $args = [
            'headers' => $headers,
            'timeout' => self::DEFAULT_TIMEOUT,
        ];

        $response = wp_remote_get($url, $args);

        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            throw new Exception(
                sprintf('HTTP request failed: %s', $response->get_error_message())
            );
        }

        // Check HTTP status code
        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception(
                sprintf(
                    'HTTP request returned status %d: %s',
                    $statusCode,
                    $body ?: 'No response body'
                )
            );
        }

        // Retrieve and decode response body
        $responseBody = wp_remote_retrieve_body($response);
        if (empty($responseBody)) {
            throw new Exception('Empty response body received from server');
        }

        $decodedBody = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                sprintf('Failed to decode JSON response: %s', json_last_error_msg())
            );
        }

        return $decodedBody;
    }
}
