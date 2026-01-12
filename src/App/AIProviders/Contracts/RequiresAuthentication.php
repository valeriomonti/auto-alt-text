<?php

namespace AATXT\App\AIProviders\Contracts;

/**
 * Interface for AI providers that require API key authentication.
 *
 * Following the Interface Segregation Principle, this interface defines
 * only the methods related to authentication validation.
 */
interface RequiresAuthentication
{
    /**
     * Validate that the provider has valid credentials configured.
     *
     * This method checks if the API key and any other required
     * credentials are present and properly formatted.
     *
     * @return bool True if credentials are valid, false otherwise
     */
    public function validateCredentials(): bool;

    /**
     * Check if the provider has an API key configured.
     *
     * @return bool True if API key is set, false otherwise
     */
    public function hasApiKey(): bool;
}
