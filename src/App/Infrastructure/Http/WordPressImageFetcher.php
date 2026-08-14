<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Http;

use Exception;

/**
 * WordPress implementation of the image fetcher.
 *
 * Providers that accept only inline image data cannot download the image
 * themselves, so its bytes have to travel with the request. When the URL points
 * inside the uploads directory the file is read straight from disk, which keeps
 * generation working on local installs, password-protected staging sites and
 * sites behind HTTP auth - none of which are reachable from the outside.
 * Anything else falls back to an HTTP request.
 */
class WordPressImageFetcher implements ImageFetcherInterface
{
    /**
     * Timeout for the HTTP fallback, in seconds.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Read an image and return its raw bytes base64-encoded.
     *
     * @param string $imageUrl The image URL
     *
     * @return string The base64-encoded image contents
     *
     * @throws Exception If the image cannot be read
     */
    public function fetchAsBase64(string $imageUrl): string
    {
        $contents = $this->readFromUploads($imageUrl);

        if ($contents === null) {
            $contents = $this->readFromHttp($imageUrl);
        }

        return base64_encode($contents);
    }

    /**
     * Read the file from the uploads directory when the URL belongs to it.
     *
     * @param string $imageUrl The image URL
     *
     * @return string|null The raw bytes, or null when the URL is not a local
     *                     upload or the file is not readable
     */
    private function readFromUploads(string $imageUrl): ?string
    {
        $uploads = wp_get_upload_dir();

        if (!empty($uploads['error']) || empty($uploads['baseurl']) || empty($uploads['basedir'])) {
            return null;
        }

        // Compare without the scheme: a http/https mismatch between the stored
        // URL and the current request must not force a needless HTTP round trip.
        $baseUrl = $this->stripScheme($uploads['baseurl']);
        $url = $this->stripScheme($imageUrl);

        if (strpos($url, $baseUrl) !== 0) {
            return null;
        }

        // Drop any query string (e.g. cache busting parameters) before
        // touching the filesystem.
        $relativePath = (string) strtok(substr($url, strlen($baseUrl)), '?');
        $path = realpath($uploads['basedir'] . $relativePath);
        $baseDir = realpath($uploads['basedir']);

        // Defensive: never read outside the uploads directory, whatever the URL contains.
        if ($path === false || $baseDir === false || strpos($path, $baseDir) !== 0) {
            return null;
        }

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    /**
     * Download the image over HTTP.
     *
     * @param string $imageUrl The image URL
     *
     * @return string The raw bytes
     *
     * @throws Exception If the download fails or returns an empty body
     */
    private function readFromHttp(string $imageUrl): string
    {
        $response = wp_remote_get($imageUrl, ['timeout' => self::DEFAULT_TIMEOUT]);

        if (is_wp_error($response)) {
            throw new Exception(
                sprintf('Unable to download the image: %s', $response->get_error_message())
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new Exception(
                sprintf('Unable to download the image: HTTP status %d', $statusCode)
            );
        }

        $body = wp_remote_retrieve_body($response);
        if ($body === '') {
            throw new Exception('The downloaded image is empty');
        }

        return $body;
    }

    /**
     * Remove the http/https scheme from a URL.
     */
    private function stripScheme(string $url): string
    {
        return (string) preg_replace('#^https?://#i', '', $url);
    }
}
