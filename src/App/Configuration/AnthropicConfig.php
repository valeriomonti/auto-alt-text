<?php

declare(strict_types=1);

namespace AATXT\App\Configuration;

/**
 * Configuration object for Anthropic Claude provider.
 *
 * Immutable value object that holds configuration data for Anthropic API calls.
 * This class implements the Dependency Injection pattern, removing the need
 * for static calls to PluginOptions.
 */
final class AnthropicConfig implements AIProviderConfig
{
    private string $apiKey;
    private string $prompt;
    private string $model;

    /**
     * @param string $apiKey The Anthropic API key
     * @param string $prompt The prompt template for generating alt text
     * @param string $model The Anthropic model identifier (e.g., 'claude-3-5-haiku-20241022', 'claude-sonnet-4-20250514')
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
