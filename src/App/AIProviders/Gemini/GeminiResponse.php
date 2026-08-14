<?php

namespace AATXT\App\AIProviders\Gemini;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Contracts\RequiresAuthentication;
use AATXT\App\AIProviders\Contracts\SupportsImageValidation;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\Gemini\GeminiException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\App\Infrastructure\Http\ImageFetcherInterface;
use AATXT\Config\Constants;

/**
 * Google Gemini provider for generating alt text using Gemini models.
 *
 * Uses the Interactions API (https://ai.google.dev/gemini-api/docs/image-understanding),
 * sending the image inline as base64 data. The API does not fetch image URLs on
 * our behalf, so the bytes must travel with the request - which also means
 * generation works on sites Google cannot reach (local, staging, HTTP auth).
 *
 * Implements SupportsImageValidation and RequiresAuthentication interfaces
 * following the Interface Segregation Principle.
 */
class GeminiResponse implements AIProviderInterface, SupportsImageValidation, RequiresAuthentication
{
    /**
     * Reasoning effort requested from the model.
     *
     * Recent flash models think by default, spending hundreds of tokens even on
     * trivial prompts. Describing an image in 125 characters does not need it,
     * so the lowest level keeps cost and latency down.
     */
    private const THINKING_LEVEL = 'minimal';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var AIProviderConfig
     */
    private $config;

    /**
     * @var ImageFetcherInterface
     */
    private $imageFetcher;

    /**
     * @var GeminiModelsRegistry|null
     */
    private $modelsRegistry;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AIProviderConfig $config Configuration with API key, prompt, and model
     * @param ImageFetcherInterface $imageFetcher Reads the image bytes to send inline
     * @param GeminiModelsRegistry|null $modelsRegistry Optional registry used to fall back
     *        to a currently-available model when the configured one has been retired
     */
    public function __construct(
        HttpClientInterface $httpClient,
        AIProviderConfig $config,
        ImageFetcherInterface $imageFetcher,
        ?GeminiModelsRegistry $modelsRegistry = null
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->imageFetcher = $imageFetcher;
        $this->modelsRegistry = $modelsRegistry;
    }

    /**
     * Get the list of supported MIME types for Google Gemini.
     *
     * @return array<string> List of supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return Constants::AATXT_GEMINI_ALLOWED_MIME_TYPES;
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
     * Make a request to the Gemini Interactions API to retrieve a description for the image passed
     *
     * @param string $imageUrl
     * @return string
     * @throws GeminiException
     */
    public function response(string $imageUrl): string
    {
        $apiKey = $this->config->getApiKey();

        if (empty($apiKey)) {
            throw new GeminiException('Gemini API key is missing in plugin settings');
        }

        try {
            $imageData = $this->imageFetcher->fetchAsBase64($imageUrl);
        } catch (\Exception $e) {
            throw new GeminiException('Unable to read the image: ' . $e->getMessage());
        }

        $payload = [
            "model" => $this->resolveModel(),
            "input" => [
                [
                    "type" => "text",
                    "text" => $this->config->getPrompt(),
                ],
                [
                    "type"      => "image",
                    "mime_type" => $this->mimeTypeFromUrl($imageUrl),
                    "data"      => $imageData,
                ],
            ],
            "generation_config" => [
                "thinking_level" => self::THINKING_LEVEL,
            ],
        ];

        $headers = [
            'Content-Type'   => 'application/json',
            'x-goog-api-key' => $apiKey,
        ];

        try {
            $data = $this->httpClient->post(Constants::AATXT_GEMINI_INTERACTIONS_ENDPOINT, $headers, $payload);
        } catch (\Exception $e) {
            throw new GeminiException('HTTP request failed: ' . $e->getMessage());
        }

        $answer = $this->extractText($data);

        if (!$answer) {
            $bodyJson = json_encode($data);
            throw new GeminiException('Response format unexpected: ' . $bodyJson);
        }

        return $answer;
    }

    /**
     * Extract the generated text from the Interaction object returned by the API.
     * The answer lives in the content of the last "model_output" step.
     *
     * @param array<string, mixed> $data Decoded API response
     * @return string|null The generated text, or null when the structure is unexpected
     */
    private function extractText(array $data): ?string
    {
        $steps = isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : [];

        $answer = null;
        foreach ($steps as $step) {
            if (!is_array($step) || !isset($step['content']) || !is_array($step['content'])) {
                continue;
            }
            if (isset($step['type']) && $step['type'] !== 'model_output') {
                continue;
            }
            foreach ($step['content'] as $item) {
                if (!is_array($item) || !isset($item['text']) || !is_string($item['text']) || $item['text'] === '') {
                    continue;
                }
                if (isset($item['type']) && $item['type'] !== 'text') {
                    continue;
                }
                $answer = $item['text'];
            }
        }

        return $answer;
    }

    /**
     * Infer the image MIME type from the URL file extension, since the
     * provider interface only receives the image URL.
     */
    private function mimeTypeFromUrl(string $imageUrl): string
    {
        $path = (string) parse_url($imageUrl, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'png':
                return 'image/png';
            case 'webp':
                return 'image/webp';
            case 'heic':
                return 'image/heic';
            case 'heif':
                return 'image/heif';
            case 'jpg':
            case 'jpeg':
            default:
                return 'image/jpeg';
        }
    }

    /**
     * Return the model id to send to the API, transparently falling back to the
     * default available model when the configured one is no longer listed
     * by the registry.
     */
    private function resolveModel(): string
    {
        $configured = $this->config->getModel();

        if ($this->modelsRegistry === null || $configured === '') {
            return $configured;
        }

        if ($this->modelsRegistry->isAvailable($configured)) {
            return $configured;
        }

        return $this->modelsRegistry->getDefaultModel();
    }
}
