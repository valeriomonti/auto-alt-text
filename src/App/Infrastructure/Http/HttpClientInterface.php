<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Http;

/**
 * Interface for HTTP client implementations.
 *
 * Abstracts HTTP communication to allow dependency injection and testing.
 * Concrete implementations should wrap WordPress HTTP functions or other HTTP libraries.
 */
interface HttpClientInterface
{
    /**
     * Perform an HTTP POST request.
     *
     * @param string $url The URL to send the request to
     * @param array<string, string> $headers HTTP headers as key-value pairs
     * @param array<string, mixed> $body Request body data
     *
     * @return array<string, mixed> The response data
     *
     * @throws \Exception If the HTTP request fails
     */
    public function post(string $url, array $headers, array $body): array;

    /**
     * Perform an HTTP GET request.
     *
     * @param string $url The URL to send the request to
     * @param array<string, string> $headers HTTP headers as key-value pairs
     *
     * @return array<string, mixed> The response data
     *
     * @throws \Exception If the HTTP request fails
     */
    public function get(string $url, array $headers = []): array;
}
