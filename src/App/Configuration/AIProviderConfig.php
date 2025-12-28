<?php

declare(strict_types=1);

namespace AATXT\App\Configuration;

/**
 * Configuration interface for AI providers.
 *
 * Defines the contract for all AI provider configuration objects,
 * implementing the Dependency Inversion Principle by depending on
 * abstractions rather than concrete implementations.
 */
interface AIProviderConfig
{
    /**
     * Get the API key for the AI provider.
     *
     * @return string The API key
     */
    public function getApiKey(): string;

    /**
     * Get the prompt template for generating alt text.
     *
     * @return string The prompt template
     */
    public function getPrompt(): string;

    /**
     * Get the model identifier to use.
     *
     * @return string The model identifier (e.g., 'gpt-4o', 'claude-sonnet-4')
     */
    public function getModel(): string;
}
