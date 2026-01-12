<?php

namespace AATXT\App\AIProviders\Anthropic;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Contracts\RequiresAuthentication;
use AATXT\App\AIProviders\Contracts\SupportsImageValidation;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\Anthropic\AnthropicException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;

/**
 * Anthropic Claude provider for generating alt text using Claude models.
 *
 * Implements SupportsImageValidation and RequiresAuthentication interfaces
 * following the Interface Segregation Principle.
 */
class AnthropicResponse implements AIProviderInterface, SupportsImageValidation, RequiresAuthentication
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var AIProviderConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AIProviderConfig $config Configuration with API key, prompt, and model
     */
    public function __construct(HttpClientInterface $httpClient, AIProviderConfig $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * Get the list of supported MIME types for Anthropic Claude.
     *
     * @return array<string> List of supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return Constants::AATXT_ANTHROPIC_ALLOWED_MIME_TYPES;
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
     * Make a request to Anthropic Claude API to retrieve a description for the image passed
     *
     * @param string $imageUrl
     * @return string
     * @throws AnthropicException
     */
    public function response(string $imageUrl): string
    {
        $apiKey = $this->config->getApiKey();

        if (empty($apiKey)) {
            throw new AnthropicException('Anthropic API key is missing in plugin settings');
        }

        $payload = [
            "model"      => $this->config->getModel(),
            "max_tokens" => 1024,
            "messages"   => [
                [
                    "role"    => "user",
                    "content" => [
                        [
                            "type"   => "image",
                            "source" => [
                                "type" => "url",
                                "url"  => $imageUrl,
                            ],
                        ],
                        [
                            "type" => "text",
                            "text" => $this->config->getPrompt(),
                        ],
                    ],
                ],
            ],
        ];

        $headers = [
            'Content-Type'      => 'application/json',
            'x-api-key'         => $apiKey,
            'anthropic-version' => Constants::AATXT_API_VERSION,
        ];

        try {
            $data = $this->httpClient->post(Constants::AATXT_ANTHROPIC_ENDPOINT, $headers, $payload);
        } catch (\Exception $e) {
            throw new AnthropicException('HTTP request failed: ' . $e->getMessage());
        }

        $answer = $data['content'][0]['text'] ?? null;

        if (!$answer) {
            $bodyJson = json_encode($data);
            throw new AnthropicException('Response format unexpected: ' . $bodyJson);
        }

        return $answer;
    }
}
