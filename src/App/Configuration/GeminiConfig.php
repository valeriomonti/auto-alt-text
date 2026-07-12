<?php

declare(strict_types=1);

namespace AATXT\App\Configuration;

/**
 * Configuration object for Google Gemini provider.
 *
 * Immutable value object that holds configuration data for Gemini API calls.
 * This class implements the Dependency Injection pattern, removing the need
 * for static calls to PluginOptions.
 */
final class GeminiConfig implements AIProviderConfig
{
    private string $apiKey;
    private string $prompt;
    private string $model;

    /**
     * @param string $apiKey The Gemini API key
     * @param string $prompt The prompt template for generating alt text
     * @param string $model The Gemini model identifier (e.g., 'gemini-3.5-flash')
     */
    public function __construct(
        string $apiKey,
        string $prompt,
        string $model
    ) {
        $this->model = $model;
        $this->prompt = $prompt;
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
