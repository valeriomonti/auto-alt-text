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
     * @test
     * @covers ::response
     */
    public function it_returns_alt_text_from_successful_api_response(): void
    {
        // Arrange
        $expectedAltText = 'A cat sitting on a windowsill looking outside';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->willReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => $expectedAltText
                        ]
                    ]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_cleans_double_quotes_from_response(): void
    {
        // Arrange
        $rawResponse = '"A beautiful sunset over the ocean"';
        $expectedAltText = 'A beautiful sunset over the ocean';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => $rawResponse
                        ]
                    ]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_cleans_multiple_whitespaces_from_response(): void
    {
        // Arrange - The cleanString method removes double+ whitespaces entirely
        $rawResponse = 'A dog   running  in   the park';
        $expectedAltText = 'A dogrunninginthe park';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => $rawResponse
                        ]
                    ]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_throws_exception_on_api_error_response(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'error' => [
                    'type' => 'invalid_request_error',
                    'code' => 'invalid_api_key',
                    'message' => 'Invalid API key provided'
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_throws_exception_on_http_failure(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willThrowException(new \Exception('Connection timeout'));

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_sends_correct_headers_with_api_key(): void
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
                'choices' => [
                    ['message' => ['content' => 'Test alt text']]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_sends_image_url_in_request_body(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($body) {
                    return isset($body['messages'][0]['content'][1]['image_url']['url'])
                        && $body['messages'][0]['content'][1]['image_url']['url'] === self::TEST_IMAGE_URL;
                })
            )
            ->willReturn([
                'choices' => [
                    ['message' => ['content' => 'Test alt text']]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_uses_default_prompt_when_config_prompt_is_empty(): void
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
                    return isset($body['messages'][0]['content'][0]['text'])
                        && !empty($body['messages'][0]['content'][0]['text']);
                })
            )
            ->willReturn([
                'choices' => [
                    ['message' => ['content' => 'Test alt text']]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(''); // Empty prompt
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new OpenAIVision($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @test
     * @covers ::getSupportedMimeTypes
     */
    public function it_returns_supported_mime_types(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $config = $this->createMock(AIProviderConfig::class);

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
     * @test
     * @covers ::supportsImage
     */
    public function it_correctly_validates_supported_mime_types(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $config = $this->createMock(AIProviderConfig::class);

        $provider = new OpenAIVision($httpClient, $config);

        // Assert
        $this->assertTrue($provider->supportsImage('image/jpeg'));
        $this->assertTrue($provider->supportsImage('image/png'));
        $this->assertFalse($provider->supportsImage('image/svg+xml'));
        $this->assertFalse($provider->supportsImage('application/pdf'));
    }

    /**
     * @test
     * @covers ::validateCredentials
     */
    public function it_validates_credentials_correctly(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);

        $validConfig = $this->createMock(AIProviderConfig::class);
        $validConfig->method('getApiKey')->willReturn(self::TEST_API_KEY);

        $invalidConfig = $this->createMock(AIProviderConfig::class);
        $invalidConfig->method('getApiKey')->willReturn('short');

        $emptyConfig = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::hasApiKey
     */
    public function it_checks_if_api_key_exists(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);

        $configWithKey = $this->createMock(AIProviderConfig::class);
        $configWithKey->method('getApiKey')->willReturn(self::TEST_API_KEY);

        $configWithoutKey = $this->createMock(AIProviderConfig::class);
        $configWithoutKey->method('getApiKey')->willReturn('');

        $providerWithKey = new OpenAIVision($httpClient, $configWithKey);
        $providerWithoutKey = new OpenAIVision($httpClient, $configWithoutKey);

        // Assert
        $this->assertTrue($providerWithKey->hasApiKey());
        $this->assertFalse($providerWithoutKey->hasApiKey());
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_trims_whitespace_from_response(): void
    {
        // Arrange
        $rawResponse = '  A mountain landscape with snow  ';
        $expectedAltText = 'A mountain landscape with snow';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => $rawResponse
                        ]
                    ]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
     * @test
     * @covers ::response
     */
    public function it_handles_html_entities_in_response(): void
    {
        // Arrange
        $rawResponse = 'A &quot;beautiful&quot; garden';
        $expectedAltText = 'A beautiful garden';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => $rawResponse
                        ]
                    ]
                ]
            ]);

        $config = $this->createMock(AIProviderConfig::class);
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
