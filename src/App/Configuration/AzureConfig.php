<?php

declare(strict_types=1);

namespace AATXT\App\Configuration;

/**
 * Configuration object for Azure Computer Vision provider.
 *
 * Immutable value object that holds configuration data for Azure Computer Vision
 * and Azure Translator API calls. This class implements the Dependency Injection
 * pattern, removing the need for static calls to PluginOptions.
 *
 * Azure services used:
 * - Computer Vision: for generating image captions
 * - Translator (optional): for translating captions to different languages
 */
final class AzureConfig implements AIProviderConfig
{
    private string $apiKey;
    private string $endpoint;
    private string $model = '';
    private string $prompt = '';
    private string $translationApiKey = '';
    private string $translationEndpoint = '';
    private string $translationRegion = '';
    private string $translationLanguage = 'en';

    /**
     * @param string $apiKey The Azure Computer Vision API key
     * @param string $endpoint The Azure Computer Vision endpoint URL
     * @param string $model The API version (e.g., '2023-10-01')
     * @param string $prompt Not used by Azure (empty string)
     * @param string $translationApiKey The Azure Translator API key (optional)
     * @param string $translationEndpoint The Azure Translator endpoint URL (optional)
     * @param string $translationRegion The Azure Translator region (e.g., 'westeurope')
     * @param string $translationLanguage The target language code (e.g., 'it', 'fr', 'de')
     */
    public function __construct(
        string $apiKey,
        string $endpoint,
        string $model = '',
        string $prompt = '',
        string $translationApiKey = '',
        string $translationEndpoint = '',
        string $translationRegion = '',
        string $translationLanguage = 'en'
    ) {
        $this->translationLanguage = $translationLanguage;
        $this->translationRegion = $translationRegion;
        $this->translationEndpoint = $translationEndpoint;
        $this->translationApiKey = $translationApiKey;
        $this->prompt = $prompt;
        $this->model = $model;
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritDoc}
     * Returns the Computer Vision API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * {@inheritDoc}
     * Azure does not use custom prompts, returns empty string.
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * {@inheritDoc}
     * Returns the API version or empty string if not specified.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the Azure Computer Vision endpoint URL.
     *
     * @return string The endpoint URL (e.g., 'https://computer-vision-france-central.cognitiveservices.azure.com/')
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Get the Azure Translator API key.
     *
     * @return string The translator API key (empty if translation is not configured)
     */
    public function getTranslationApiKey(): string
    {
        return $this->translationApiKey;
    }

    /**
     * Get the Azure Translator endpoint URL.
     *
     * @return string The translator endpoint URL (empty if translation is not configured)
     */
    public function getTranslationEndpoint(): string
    {
        return $this->translationEndpoint;
    }

    /**
     * Get the Azure Translator region.
     *
     * @return string The region (e.g., 'westeurope', empty if translation is not configured)
     */
    public function getRegion(): string
    {
        return $this->translationRegion;
    }

    /**
     * Get the target language for translation.
     *
     * @return string The language code (e.g., 'it', 'fr', 'de', defaults to 'en')
     */
    public function getTranslationLanguage(): string
    {
        return $this->translationLanguage;
    }

    /**
     * Check if translation is configured and should be used.
     *
     * @return bool True if translation is configured and target language is not English
     */
    public function shouldTranslate(): bool
    {
        return !empty($this->translationApiKey)
            && !empty($this->translationEndpoint)
            && $this->translationLanguage !== 'en';
    }
}
