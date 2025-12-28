<?php

namespace AATXT\App\AIProviders\Azure;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Contracts\RequiresAuthentication;
use AATXT\App\AIProviders\Contracts\SupportsImageValidation;
use AATXT\App\AIProviders\Contracts\SupportsTranslation;
use AATXT\App\Configuration\AzureConfig;
use AATXT\App\Exceptions\Azure\AzureComputerVisionException;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;

/**
 * Azure Computer Vision provider for generating image captions.
 *
 * Implements SupportsImageValidation, RequiresAuthentication, and SupportsTranslation
 * interfaces following the Interface Segregation Principle.
 */
class AzureComputerVisionCaptionsResponse implements
    AIProviderInterface,
    SupportsImageValidation,
    RequiresAuthentication,
    SupportsTranslation
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var AzureConfig
     */
    private $config;

    /**
     * @var AzureTranslator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AzureConfig $config Azure configuration with API keys and endpoints
     * @param AzureTranslator $translator Azure translator for caption translation
     */
    public function __construct(
        HttpClientInterface $httpClient,
        AzureConfig $config,
        AzureTranslator $translator
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->translator = $translator;
    }

    /**
     * Get the list of supported MIME types for Azure Computer Vision.
     *
     * @return array<string> List of supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return Constants::AATXT_AZURE_ALLOWED_MIME_TYPES;
    }

    /**
     * Check if a specific MIME type is supported.
     *
     * @param string $mimeType The MIME type to check
     * @return bool True if supported, false otherwise
     */
    public function supportsImage(string $mimeType): bool
    {
        return in_array($mimeType, $this->getSupportedMimeTypes(), true);
    }

    /**
     * Validate that valid credentials are configured.
     *
     * @return bool True if credentials are valid
     */
    public function validateCredentials(): bool
    {
        $apiKey = $this->config->getApiKey();
        $endpoint = $this->config->getEndpoint();

        return !empty($apiKey) && !empty($endpoint);
    }

    /**
     * Check if an API key is configured.
     *
     * @return bool True if API key is set
     */
    public function hasApiKey(): bool
    {
        return !empty($this->config->getApiKey());
    }

    /**
     * Translate text to the specified target language.
     *
     * @param string $text The text to translate
     * @param string $targetLanguage The target language code
     * @return string The translated text
     * @throws AzureTranslateInstanceException
     */
    public function translate(string $text, string $targetLanguage): string
    {
        return $this->translator->translate($text, $targetLanguage);
    }

    /**
     * Check if translation is enabled and configured.
     *
     * @return bool True if translation is available
     */
    public function isTranslationEnabled(): bool
    {
        $language = $this->config->getTranslationLanguage();
        return !empty($language) && $language !== Constants::AATXT_AZURE_DEFAULT_LANGUAGE;
    }

    /**
     * Get the configured target language for translation.
     *
     * @return string The language code, or empty string if not configured
     */
    public function getTargetLanguage(): string
    {
        return $this->config->getTranslationLanguage();
    }

    /**
     * Make a request to Azure Computer Vision APIs to retrieve the contents of the uploaded image.
     * If necessary, translate the description into the requested language.
     *
     * @param string $imageUrl
     * @return string
     * @throws AzureComputerVisionException
     * @throws AzureTranslateInstanceException
     */
    public function response(string $imageUrl): string
    {
        $endpoint = $this->config->getEndpoint();
        $url = $endpoint . 'computervision/imageanalysis:analyze?api-version='
            . Constants::AATXT_AZURE_COMPUTER_VISION_API_VERSION
            . '&features=caption&language=en&gender-neutral-caption=False';

        $headers = [
            'content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->config->getApiKey(),
        ];

        $payload = [
            'url' => $imageUrl,
        ];

        try {
            $bodyResult = $this->httpClient->post($url, $headers, $payload);
        } catch (\Exception $e) {
            throw new AzureComputerVisionException('HTTP request failed: ' . $e->getMessage());
        }

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureComputerVisionException(
                "Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']
            );
        }

        $altText = $bodyResult['captionResult']['text'];

        // If translation is not enabled, return the original text
        if (!$this->isTranslationEnabled()) {
            return $altText;
        }

        return $this->translate($altText, $this->getTargetLanguage());
    }
}
