<?php

namespace AATXT\App\AIProviders\OpenAI;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Contracts\RequiresAuthentication;
use AATXT\App\AIProviders\Contracts\SupportsImageValidation;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;

/**
 * Abstract base class for OpenAI-based providers.
 *
 * Implements SupportsImageValidation and RequiresAuthentication interfaces
 * following the Interface Segregation Principle.
 */
abstract class OpenAIResponse implements AIProviderInterface, SupportsImageValidation, RequiresAuthentication
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var AIProviderConfig
     */
    protected $config;

    /**
     * Constructor for OpenAI-based providers.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AIProviderConfig $config Configuration object with API key, prompt, and model
     */
    public function __construct(HttpClientInterface $httpClient, AIProviderConfig $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    abstract public function response(string $imageUrl): string;

    /**
     * Get the list of supported MIME types for OpenAI Vision.
     *
     * @return array<string> List of supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return Constants::AATXT_OPENAI_ALLOWED_MIME_TYPES;
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
        return !empty($apiKey) && strlen($apiKey) > 10;
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
     * Send the request to the OpenAI APIs and return the decoded response
     *
     * @param array $requestBody
     * @param string $endpoint
     * @return array
     * @throws OpenAIException
     */
    protected function decodedResponseBody(array $requestBody, string $endpoint): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->config->getApiKey(),
            'Content-Type' => 'application/json; charset=utf-8',
        ];

        try {
            $decodedBody = $this->httpClient->post($endpoint, $headers, $requestBody);
        } catch (\Exception $e) {
            throw new OpenAIException("HTTP request failed: " . $e->getMessage());
        }

        // Check for OpenAI-specific errors in the response
        if (isset($decodedBody['error'])) {
            throw new OpenAIException(
                'Error type: ' . $decodedBody['error']['type'] .
                ' - Error code: ' . $decodedBody['error']['code'] .
                ' - ' . $decodedBody['error']['message']
            );
        }

        return $decodedBody;
    }

    /**
     * Return the main OpenAI prompt
     *
     * @return string
     */
    protected function prompt(): string
    {
        $prompt = $this->config->getPrompt();
        return !empty($prompt) ? $prompt : Constants::AATXT_OPENAI_DEFAULT_PROMPT;
    }

    /**
     * Clean response string from unwanted characters.
     *
     * @param string $text
     * @return string
     */
    protected function cleanString(string $text): string
    {
        $patterns = array(
            '/\"/',        // Double quotes
            '/\s\s+/',     // Double or more consecutive white spaces
            '/&quot;/'     // HTML sequence for double quotes
        );

        return trim(preg_replace($patterns, '', $text));
    }

    /**
     * Prepare the request body for OpenAI API.
     *
     * @param string $model
     * @param string $prompt
     * @param string $imageUrl
     * @return array
     */
    protected function prepareRequestBody(string $model, string $prompt, string $imageUrl): array
    {
        return [
            'model' => Constants::AATXT_OPENAI_VISION_MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => "text",
                            "text" => $prompt
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => $imageUrl
                            ]
                        ]
                    ]
                ],
            ],
            'max_tokens' => Constants::AATXT_OPENAI_MAX_TOKENS,
        ];
    }
}
