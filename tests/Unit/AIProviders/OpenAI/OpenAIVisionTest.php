<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\OpenAI;

use AATXT\App\AIProviders\OpenAI\OpenAIVision;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OpenAIVision provider with mocked HTTP client.
 *
 * These tests verify the provider's behavior without making real API calls.
 *
 * @coversDefaultClass \AATXT\App\AIProviders\OpenAI\OpenAIVision
 */
class OpenAIVisionTest extends TestCase
{
    private const TEST_API_KEY = 'sk-test-api-key-12345';
    private const TEST_PROMPT = 'Describe this image for alt text';
    private const TEST_MODEL = 'gpt-4o';
    private const TEST_IMAGE_URL = 'https://example.com/image.jpg';

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
            ->willReturn([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            ['text' => $expectedAltText]
                        ]
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItCleansDoubleQuotesFromResponse(): void
    {
        // Arrange
        $rawResponse = '"A beautiful sunset over the ocean"';
        $expectedAltText = 'A beautiful sunset over the ocean';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            ['text' => $rawResponse]
                        ]
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItCleansMultipleWhitespacesFromResponse(): void
    {
        // Arrange - The cleanString method removes double+ whitespaces entirely
        $rawResponse = 'A dog   running  in   the park';
        $expectedAltText = 'A dogrunninginthe park';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            ['text' => $rawResponse]
                        ]
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItThrowsExceptionOnApiErrorResponse(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'error' => [
                    'type' => 'invalid_request_error',
                    'code' => 'invalid_api_key',
                    'message' => 'Invalid API key provided'
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Assert
        $this->expectException(OpenAIException::class);
        $this->expectExceptionMessageMatches('/invalid_api_key/');

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

        $provider = new OpenAIVision($httpClient, $config);

        // Assert
        $this->expectException(OpenAIException::class);
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
                $this->anything(),
                $this->callback(function ($headers) {
                    return isset($headers['Authorization'])
                        && $headers['Authorization'] === 'Bearer ' . self::TEST_API_KEY
                        && isset($headers['Content-Type'])
                        && strpos($headers['Content-Type'], 'application/json') !== false;
                }),
                $this->anything()
            )
            ->willReturn([
                'output' => [
                    ['type' => 'message', 'content' => [['text' => 'Test alt text']]]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItSendsImageUrlInRequestBody(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    return isset($body['input'][0]['content'][1]['image_url'])
                        && $body['input'][0]['content'][1]['image_url'] === self::TEST_IMAGE_URL;
                })
            )
            ->willReturn([
                'output' => [
                    ['type' => 'message', 'content' => [['text' => 'Test alt text']]]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItUsesDefaultPromptWhenConfigPromptIsEmpty(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    // Should contain some prompt text even if config is empty
                    return isset($body['input'][0]['content'][0]['text'])
                        && !empty($body['input'][0]['content'][0]['text']);
                })
            )
            ->willReturn([
                'output' => [
                    ['type' => 'message', 'content' => [['text' => 'Test alt text']]]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(''); // Empty prompt
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

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

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $mimeTypes = $provider->getSupportedMimeTypes();

        // Assert
        $this->assertIsArray($mimeTypes);
        $this->assertNotEmpty($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('image/png', $mimeTypes);
    }

    /**
     * @covers ::supportsImage
     */
    public function testItCorrectlyValidatesSupportedMimeTypes(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $config = $this->createStub(AIProviderConfig::class);

        $provider = new OpenAIVision($httpClient, $config);

        // Assert
        $this->assertTrue($provider->supportsImage('image/jpeg'));
        $this->assertTrue($provider->supportsImage('image/png'));
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

        $invalidConfig = $this->createStub(AIProviderConfig::class);
        $invalidConfig->method('getApiKey')->willReturn('short');

        $emptyConfig = $this->createStub(AIProviderConfig::class);
        $emptyConfig->method('getApiKey')->willReturn('');

        $validProvider = new OpenAIVision($httpClient, $validConfig);
        $invalidProvider = new OpenAIVision($httpClient, $invalidConfig);
        $emptyProvider = new OpenAIVision($httpClient, $emptyConfig);

        // Assert
        $this->assertTrue($validProvider->validateCredentials());
        $this->assertFalse($invalidProvider->validateCredentials());
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

        $providerWithKey = new OpenAIVision($httpClient, $configWithKey);
        $providerWithoutKey = new OpenAIVision($httpClient, $configWithoutKey);

        // Assert
        $this->assertTrue($providerWithKey->hasApiKey());
        $this->assertFalse($providerWithoutKey->hasApiKey());
    }

    /**
     * @covers ::response
     */
    public function testItTrimsWhitespaceFromResponse(): void
    {
        // Arrange
        $rawResponse = '  A mountain landscape with snow  ';
        $expectedAltText = 'A mountain landscape with snow';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            ['text' => $rawResponse]
                        ]
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItHandlesHtmlEntitiesInResponse(): void
    {
        // Arrange
        $rawResponse = 'A &quot;beautiful&quot; garden';
        $expectedAltText = 'A beautiful garden';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            ['text' => $rawResponse]
                        ]
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }
}
