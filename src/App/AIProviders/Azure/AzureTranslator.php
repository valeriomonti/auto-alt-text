<?php

namespace AATXT\App\AIProviders\Azure;

use AATXT\App\AIProviders\AITranslatorInterface;
use AATXT\App\Configuration\AzureConfig;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;

/**
 * Azure Translator provider for translating text.
 *
 * This class uses dependency injection to receive HTTP client and configuration,
 * removing static dependencies on PluginOptions.
 */
class AzureTranslator implements AITranslatorInterface
{
    private HttpClientInterface $httpClient;
    private AzureConfig $config;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AzureConfig $config Azure configuration with API keys and endpoints
     */
    public function __construct(HttpClientInterface $httpClient, AzureConfig $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * Translate a string sending a request to the Azure translation Api
     * @param string $text
     * @param string $language
     * @return string
     * @throws AzureTranslateInstanceException
     */
    public function translate(string $text, string $language): string
    {
        $apiKey = $this->config->getTranslationApiKey();
        $region = $this->config->getRegion();
        $endpoint = $this->config->getTranslationEndpoint();

        if (empty($apiKey) || empty($region) || empty($endpoint)) {
            return $text;
        }

        $route = "translate?api-version=3.0&from=en&to=" . $language;
        $url = $endpoint . $route;

        $headers = [
            'Content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $apiKey,
            'Ocp-Apim-Subscription-Region' => $region,
        ];

        $payload = [
            [
                'Text' => $text
            ]
        ];

        try {
            $bodyResult = $this->httpClient->post($url, $headers, $payload);
        } catch (\Exception $e) {
            throw new AzureTranslateInstanceException('HTTP request failed: ' . $e->getMessage());
        }

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult[0]['translations'][0]['text'];
    }

    /**
     * Get the list of supported languages from Azure Api
     * @return array
     * @throws AzureTranslateInstanceException
     */
    public function supportedLanguages(): array
    {
        $apiKey = $this->config->getTranslationApiKey();
        if (empty($apiKey)) {
            return [];
        }
        $endpoint = $this->config->getTranslationEndpoint();
        if (empty($endpoint)) {
            return [];
        }

        $route = 'languages?api-version=3.0';
        $url = $endpoint . $route;

        $headers = [
            'Content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $apiKey
        ];

        try {
            $bodyResult = $this->httpClient->get($url, $headers);
        } catch (\Exception $e) {
            throw new AzureTranslateInstanceException(
                esc_html__('No language retrieved: maybe the translation endpoint is wrong. Please check it out and try again.', 'auto-alt-text')
            );
        }

        if (empty($bodyResult)) {
            throw new AzureTranslateInstanceException(
                esc_html__('No language retrieved: maybe the translation endpoint is wrong. Please check it out and try again.', 'auto-alt-text')
            );
        }

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult['translation'];
    }
}