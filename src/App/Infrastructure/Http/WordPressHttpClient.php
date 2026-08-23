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
     * Filter controlling whether outbound requests are pinned to IPv4.
     *
     * Disable with: add_filter('aatxt_force_ipv4', '__return_false');
     */
    private const FILTER_FORCE_IPV4 = 'aatxt_force_ipv4';

    /**
     * Fragments AI providers use when they refuse a request because of the
     * caller's geolocation. The wording differs per provider and per endpoint,
     * so the body is matched loosely.
     */
    private const LOCATION_ERROR_MARKERS = [
        'not available in your current location',
        'user location is not supported',
    ];

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

        return $this->send($url, $args, true);
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

        return $this->send($url, $args, false);
    }

    /**
     * Dispatch the request and decode the response.
     *
     * @param string $url The URL to send the request to
     * @param array<string, mixed> $args Arguments for the WordPress HTTP API
     * @param bool $isPost True to POST, false to GET
     *
     * @return array<string, mixed> The decoded JSON response body
     *
     * @throws Exception If the HTTP request fails or returns a non-2xx status code
     */
    private function send(string $url, array $args, bool $isPost): array
    {
        $ipv4Callback = $this->pinToIpv4($url);

        try {
            $response = $isPost ? wp_remote_post($url, $args) : wp_remote_get($url, $args);
        } finally {
            if ($ipv4Callback !== null) {
                remove_action('http_api_curl', $ipv4Callback, 10);
            }
        }

        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            throw new Exception(
                sprintf('HTTP request failed: %s', $response->get_error_message())
            );
        }

        // Check HTTP status code
        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new Exception(
                $this->describeStatusError((int)$statusCode, wp_remote_retrieve_body($response))
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
     * Pin the next outbound request to IPv4 for the duration of the call.
     *
     * cURL prefers the AAAA record, and several providers - Google most visibly -
     * geolocate datacenter IPv6 ranges (OVH and Hetzner among them) outside the
     * regions where their API is offered. The same request then succeeds over
     * IPv4 and fails over IPv6 with an HTTP 400 about the caller's location, so
     * generation breaks depending on the host's network rather than on anything
     * the user configured. Pinning to IPv4 keeps the behaviour predictable.
     *
     * @param string $url The URL about to be requested
     *
     * @return callable|null The registered callback, to be removed after the
     *                       request, or null when the filter opted out
     */
    private function pinToIpv4(string $url): ?callable
    {
        if (!apply_filters(self::FILTER_FORCE_IPV4, true, $url)) {
            return null;
        }

        $callback = static function ($handle) {
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
        };

        add_action('http_api_curl', $callback, 10, 1);

        return $callback;
    }

    /**
     * Build the exception message for a non-2xx response, adding a hint when the
     * provider refused the request because of where it was called from. The raw
     * message points at the account or the region and sends people looking in the
     * wrong place, while the actual cause is almost always the outbound IP.
     *
     * @param int $statusCode The HTTP status code received
     * @param string $body The raw response body
     *
     * @return string The message to report
     */
    private function describeStatusError(int $statusCode, string $body): string
    {
        $message = sprintf(
            'HTTP request returned status %d: %s',
            $statusCode,
            $body ?: 'No response body'
        );

        foreach (self::LOCATION_ERROR_MARKERS as $marker) {
            if (stripos($body, $marker) !== false) {
                return $message . ' | The provider refused the request based on the geolocation of the '
                    . 'IP this server sent it from, not on your API key or plan. This usually means the '
                    . 'call left over IPv6 from a datacenter range the provider maps outside its supported '
                    . 'regions. The plugin pins requests to IPv4 to avoid this; if the "'
                    . self::FILTER_FORCE_IPV4 . '" filter has been disabled, re-enable it, and otherwise '
                    . 'check that this server\'s outbound IPv4 address is in a supported country.';
            }
        }

        return $message;
    }
}
