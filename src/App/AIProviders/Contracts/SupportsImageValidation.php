<?php

namespace AATXT\App\AIProviders\Contracts;

/**
 * Interface for AI providers that support image MIME type validation.
 *
 * Following the Interface Segregation Principle, this interface defines
 * only the methods related to image validation, separate from the main
 * AIProviderInterface.
 */
interface SupportsImageValidation
{
    /**
     * Get the list of supported MIME types for this provider.
     *
     * @return array<string> List of supported MIME types (e.g., ['image/jpeg', 'image/png'])
     */
    public function getSupportedMimeTypes(): array;

    /**
     * Check if a specific MIME type is supported by this provider.
     *
     * @param string $mimeType The MIME type to check
     * @return bool True if supported, false otherwise
     */
    public function supportsImage(string $mimeType): bool;
}
