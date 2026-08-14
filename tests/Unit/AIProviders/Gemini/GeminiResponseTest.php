<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Gemini;

use AATXT\App\AIProviders\Gemini\GeminiModelsRegistry;
use AATXT\App\AIProviders\Gemini\GeminiResponse;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\Gemini\GeminiException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\App\Infrastructure\Http\ImageFetcherInterface;
use AATXT\Config\Constants;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeminiResponse provider with mocked HTTP client.
 *
 * These tests verify the Google Gemini provider's behavior without making real API calls.
 *
 * @coversDefaultClass \AATXT\App\AIProviders\Gemini\GeminiResponse
 */
class GeminiResponseTest extends TestCase
{
    private const TEST_API_KEY = 'AIza-test-api-key-12345';
    private const TEST_PROMPT = 'Describe this image for alt text';
    private const TEST_MODEL = 'gemini-3.5-flash';
    private const TEST_IMAGE_URL = 'https://example.com/image.jpg';
    private const TEST_IMAGE_BASE64 = 'aW1hZ2UtYnl0ZXM=';

    /**
     * Build an image fetcher returning fixed base64 contents.
     *
     * The Interactions API only accepts inline image data, so every request
     * carries the encoded bytes of the image.
     */
    private function imageFetcher(string $base64 = self::TEST_IMAGE_BASE64): ImageFetcherInterface
    {
        $fetcher = $this->createStub(ImageFetcherInterface::class);
        $fetcher->method('fetchAsBase64')->willReturn($base64);

        return $fetcher;
    }

    /**
     * Build a successful Interaction API response containing the given text.
     *
     * @return array<string, mixed>
     */
    private function interactionResponse(string $text): array
    {
        return [
            'id' => 'v1_test-interaction-id',
            'model' => self::TEST_MODEL,
            'status' => 'completed',
            'steps' => [
                [
                    'type' => 'model_output',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }

    /**
     * @covers ::response
     */
    public function testItReturnsAltTextFromSuccessfulApiResponse(): void
    {
        // Arrange
        $expectedAltText = 'A cat sitting on a windowsill looking outside';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->willReturn($this->interactionResponse($expectedAltText));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItThrowsExceptionWhenApiKeyIsMissing(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn('');
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Assert
        $this->expectException(GeminiException::class);
        $this->expectExceptionMessageMatches('/API key is missing/');

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItThrowsExceptionOnUnexpectedResponseFormat(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'unexpected' => 'format'
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Assert
        $this->expectException(GeminiException::class);
        $this->expectExceptionMessageMatches('/Response format unexpected/');

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItThrowsExceptionOnHttpFailure(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willThrowException(new \Exception('Connection timeout'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Assert
        $this->expectException(GeminiException::class);
        $this->expectExceptionMessageMatches('/HTTP request failed/');

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItSendsCorrectHeadersWithApiKey(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo(Constants::AATXT_GEMINI_INTERACTIONS_ENDPOINT),
                $this->callback(function ($headers) {
                    return isset($headers['x-goog-api-key'])
                        && $headers['x-goog-api-key'] === self::TEST_API_KEY
                        && isset($headers['Content-Type'])
                        && $headers['Content-Type'] === 'application/json';
                }),
                $this->anything()
            )
            ->willReturn($this->interactionResponse('Test alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItSendsCorrectRequestBodyStructure(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    // Verify model
                    if ($body['model'] !== self::TEST_MODEL) {
                        return false;
                    }
                    // Verify input content
                    $input = $body['input'];
                    $hasImage = false;
                    $hasText = false;
                    foreach ($input as $item) {
                        if ($item['type'] === 'image'
                            && $item['data'] === self::TEST_IMAGE_BASE64
                            && $item['mime_type'] === 'image/jpeg') {
                            $hasImage = true;
                        }
                        if ($item['type'] === 'text' && $item['text'] === self::TEST_PROMPT) {
                            $hasText = true;
                        }
                    }
                    return $hasImage && $hasText;
                })
            )
            ->willReturn($this->interactionResponse('Test alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItInfersMimeTypeFromImageUrlExtension(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    foreach ($body['input'] as $item) {
                        if ($item['type'] === 'image') {
                            return $item['mime_type'] === 'image/png';
                        }
                    }
                    return false;
                })
            )
            ->willReturn($this->interactionResponse('Test alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Act
        $provider->response('https://example.com/image.png?w=300');
    }

    /**
     * @covers ::response
     */
    public function testItHandlesEmptyTextInResponse(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn($this->interactionResponse(''));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Assert - empty string is falsy, so it should throw
        $this->expectException(GeminiException::class);
        $this->expectExceptionMessageMatches('/Response format unexpected/');

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testItReturnsSupportedMimeTypes(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $config = $this->createStub(AIProviderConfig::class);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Act
        $mimeTypes = $provider->getSupportedMimeTypes();

        // Assert
        $this->assertIsArray($mimeTypes);
        $this->assertNotEmpty($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('image/png', $mimeTypes);
        $this->assertContains('image/webp', $mimeTypes);
        // Gemini does not support GIF images
        $this->assertNotContains('image/gif', $mimeTypes);
    }

    /**
     * @covers ::supportsImage
     */
    public function testItCorrectlyValidatesSupportedMimeTypes(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $config = $this->createStub(AIProviderConfig::class);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        // Assert
        $this->assertTrue($provider->supportsImage('image/jpeg'));
        $this->assertTrue($provider->supportsImage('image/png'));
        $this->assertTrue($provider->supportsImage('image/webp'));
        $this->assertTrue($provider->supportsImage('image/heic'));
        $this->assertTrue($provider->supportsImage('image/heif'));
        $this->assertFalse($provider->supportsImage('image/gif'));
        $this->assertFalse($provider->supportsImage('image/svg+xml'));
        $this->assertFalse($provider->supportsImage('application/pdf'));
    }

    /**
     * @covers ::validateCredentials
     */
    public function testItValidatesCredentialsCorrectly(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);

        $validConfig = $this->createStub(AIProviderConfig::class);
        $validConfig->method('getApiKey')->willReturn(self::TEST_API_KEY);

        $shortConfig = $this->createStub(AIProviderConfig::class);
        $shortConfig->method('getApiKey')->willReturn('short');

        $emptyConfig = $this->createStub(AIProviderConfig::class);
        $emptyConfig->method('getApiKey')->willReturn('');

        $validProvider = new GeminiResponse($httpClient, $validConfig, $this->imageFetcher());
        $shortProvider = new GeminiResponse($httpClient, $shortConfig, $this->imageFetcher());
        $emptyProvider = new GeminiResponse($httpClient, $emptyConfig, $this->imageFetcher());

        // Assert
        $this->assertTrue($validProvider->validateCredentials());
        $this->assertFalse($shortProvider->validateCredentials());
        $this->assertFalse($emptyProvider->validateCredentials());
    }

    /**
     * @covers ::hasApiKey
     */
    public function testItChecksIfApiKeyExists(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);

        $configWithKey = $this->createStub(AIProviderConfig::class);
        $configWithKey->method('getApiKey')->willReturn(self::TEST_API_KEY);

        $configWithoutKey = $this->createStub(AIProviderConfig::class);
        $configWithoutKey->method('getApiKey')->willReturn('');

        $providerWithKey = new GeminiResponse($httpClient, $configWithKey, $this->imageFetcher());
        $providerWithoutKey = new GeminiResponse($httpClient, $configWithoutKey, $this->imageFetcher());

        // Assert
        $this->assertTrue($providerWithKey->hasApiKey());
        $this->assertFalse($providerWithoutKey->hasApiKey());
    }

    /**
     * @covers ::response
     *
     * Runtime fallback: when the registry says the configured model is no longer
     * available, GeminiResponse must transparently send the registry's
     * default model to the API so generation keeps working
     * until the admin picks a new one from the settings page.
     */
    public function testItFallsBackToRegistryDefaultWhenConfiguredModelIsRetired(): void
    {
        $retiredModel = 'gemini-2.0-flash';
        $replacementModel = 'gemini-3.5-flash';

        $registry = $this->createMock(GeminiModelsRegistry::class);
        $registry->expects($this->once())
            ->method('isAvailable')
            ->with($retiredModel)
            ->willReturn(false);
        $registry->expects($this->once())
            ->method('getDefaultModel')
            ->willReturn($replacementModel);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) use ($replacementModel) {
                    return ($body['model'] ?? null) === $replacementModel;
                })
            )
            ->willReturn($this->interactionResponse('Alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn($retiredModel);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher(), $registry);

        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItKeepsConfiguredModelWhenRegistryConfirmsItIsAvailable(): void
    {
        $currentModel = 'gemini-3.5-flash';

        $registry = $this->createMock(GeminiModelsRegistry::class);
        $registry->expects($this->once())
            ->method('isAvailable')
            ->with($currentModel)
            ->willReturn(true);
        $registry->expects($this->never())->method('getDefaultModel');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) use ($currentModel) {
                    return ($body['model'] ?? null) === $currentModel;
                })
            )
            ->willReturn($this->interactionResponse('Alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn($currentModel);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher(), $registry);

        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     *
     * The answer must come from the model output: intermediate steps
     * (e.g. thoughts or tool calls) without text content are skipped and the
     * last text emitted by the model wins.
     */
    public function testItExtractsTextFromTheLastModelOutputStep(): void
    {
        $expectedAltText = 'Final alt text';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'status' => 'completed',
                'steps' => [
                    ['type' => 'user_input'],
                    [
                        'type' => 'model_output',
                        'content' => [
                            ['type' => 'text', 'text' => 'Intermediate text'],
                        ],
                    ],
                    [
                        'type' => 'model_output',
                        'content' => [
                            ['type' => 'thought_summary', 'text' => 'Reasoning...'],
                            ['type' => 'text', 'text' => $expectedAltText],
                        ],
                    ],
                ],
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        $this->assertEquals($expectedAltText, $provider->response(self::TEST_IMAGE_URL));
    }

    /**
     * @covers ::response
     *
     * The Interactions API does not download image URLs on our behalf: sending
     * "uri" gets the request rejected. Guards against reintroducing it.
     */
    public function testItSendsTheImageInlineAndNeverAsUri(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    foreach ($body['input'] as $item) {
                        if ($item['type'] === 'image') {
                            return !isset($item['uri'])
                                && ($item['data'] ?? null) === self::TEST_IMAGE_BASE64;
                        }
                    }
                    return false;
                })
            )
            ->willReturn($this->interactionResponse('Alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     *
     * Recent flash models think by default and burn tokens even on trivial
     * prompts, which is pure waste for a 125 character alt text.
     */
    public function testItRequestsMinimalThinking(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    return ($body['generation_config']['thinking_level'] ?? null) === 'minimal';
                })
            )
            ->willReturn($this->interactionResponse('Alt text'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $this->imageFetcher());

        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     *
     * An unreadable image must surface as a GeminiException so the event system
     * logs it and the upload proceeds without alt text, instead of bubbling up
     * a raw exception.
     */
    public function testItThrowsExceptionWhenTheImageCannotBeRead(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('post');

        $fetcher = $this->createStub(ImageFetcherInterface::class);
        $fetcher->method('fetchAsBase64')
            ->willThrowException(new \Exception('Unable to download the image: HTTP status 404'));

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new GeminiResponse($httpClient, $config, $fetcher);

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessageMatches('/Unable to read the image/');

        $provider->response(self::TEST_IMAGE_URL);
    }
}
