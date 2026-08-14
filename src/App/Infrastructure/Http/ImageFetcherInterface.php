<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Http;

/**
 * Interface for reading image bytes to be sent inline to AI providers.
 *
 * Abstracts filesystem and HTTP access so providers stay testable.
 */
interface ImageFetcherInterface
{
    /**
     * Read an image and return its raw bytes base64-encoded.
     *
     * @param string $imageUrl The image URL
     *
     * @return string The base64-encoded image contents
     *
     * @throws \Exception If the image cannot be read
     */
    public function fetchAsBase64(string $imageUrl): string;
}
