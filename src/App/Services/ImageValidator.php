<?php

namespace AATXT\App\Services;

use AATXT\App\AIProviders\Contracts\SupportsImageValidation;

/**
 * Service for validating images before alt text generation.
 *
 * This service centralizes image validation logic, checking MIME types
 * and other image properties before attempting to generate alt text.
 */
final class ImageValidator
{
    /**
     * Validate that a MIME type is in the allowed list.
     *
     * @param string $mimeType The MIME type to validate
     * @param array<string> $allowedTypes List of allowed MIME types
     * @return bool True if valid, false otherwise
     */
    public function validateMimeType(string $mimeType, array $allowedTypes): bool
    {
        return in_array($mimeType, $allowedTypes, true);
    }

    /**
     * Get the MIME type of a WordPress attachment.
     *
     * @param int $attachmentId The attachment post ID
     * @return string The MIME type, or empty string if not found
     */
    public function getImageMimeType(int $attachmentId): string
    {
        $mimeType = get_post_mime_type($attachmentId);
        return $mimeType !== false ? $mimeType : '';
    }

    /**
     * Check if an attachment is an image.
     *
     * @param int $attachmentId The attachment post ID
     * @return bool True if the attachment is an image
     */
    public function isImage(int $attachmentId): bool
    {
        return wp_attachment_is_image($attachmentId);
    }

    /**
     * Validate an image against a provider's supported MIME types.
     *
     * @param int $attachmentId The attachment post ID
     * @param SupportsImageValidation $provider The provider to validate against
     * @return bool True if the image is supported by the provider
     */
    public function validateForProvider(int $attachmentId, SupportsImageValidation $provider): bool
    {
        if (!$this->isImage($attachmentId)) {
            return false;
        }

        $mimeType = $this->getImageMimeType($attachmentId);

        if (empty($mimeType)) {
            return false;
        }

        return $provider->supportsImage($mimeType);
    }

    /**
     * Get a human-readable list of supported formats.
     *
     * @param array<string> $mimeTypes List of MIME types
     * @return string Comma-separated list of formats (e.g., "png, jpeg, gif")
     */
    public function formatMimeTypeList(array $mimeTypes): string
    {
        return str_replace('image/', '', implode(', ', $mimeTypes));
    }

    /**
     * Get the image URL for a WordPress attachment.
     *
     * @param int $attachmentId The attachment post ID
     * @return string The image URL, or empty string if not found
     */
    public function getImageUrl(int $attachmentId): string
    {
        $url = wp_get_attachment_url($attachmentId);
        return $url !== false ? $url : '';
    }

    /**
     * Get image dimensions.
     *
     * @param int $attachmentId The attachment post ID
     * @return array{width: int, height: int}|null Width and height, or null if not available
     */
    public function getImageDimensions(int $attachmentId): ?array
    {
        $metadata = wp_get_attachment_metadata($attachmentId);

        if (!$metadata || !isset($metadata['width'], $metadata['height'])) {
            return null;
        }

        return [
            'width' => (int) $metadata['width'],
            'height' => (int) $metadata['height'],
        ];
    }

    /**
     * Get the file size of an attachment in bytes.
     *
     * @param int $attachmentId The attachment post ID
     * @return int|null File size in bytes, or null if not available
     */
    public function getFileSize(int $attachmentId): ?int
    {
        $filePath = get_attached_file($attachmentId);

        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        $size = filesize($filePath);
        return $size !== false ? $size : null;
    }
}
