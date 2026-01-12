<?php

namespace AATXT\App\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when an image is invalid or has unsupported MIME type.
 *
 * This exception is thrown when attempting to generate alt text for an image
 * that doesn't meet the provider's requirements (e.g., unsupported format).
 */
class InvalidImageException extends Exception
{
    /**
     * Constructor
     *
     * @param int $imageId The WordPress attachment ID
     * @param string $mimeType The MIME type of the image
     * @param array $supportedTypes List of supported MIME types
     */
    public function __construct(int $imageId, string $mimeType, array $supportedTypes = [])
    {
        $supportedList = !empty($supportedTypes)
            ? implode(', ', $supportedTypes)
            : 'none specified';

        parent::__construct(
            sprintf(
                'Image ID %d has unsupported MIME type "%s". Supported types: %s',
                $imageId,
                $mimeType,
                $supportedList
            )
        );
    }
}
