<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Anthropic;

use AATXT\App\AIProviders\Anthropic\AnthropicResponse;
use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\Anthropic\AnthropicException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnthropicResponse provider with mocked HTTP client.
 *
 * These tests verify the Anthropic Claude provider's behavior without making real API calls.
 *
 * @coversDefaultClass \AATXT\App\AIProviders\Anthropic\AnthropicResponse
 */
class AnthropicResponseTest extends TestCase
{
    private const TEST_API_KEY = 'sk-ant-test-api-key-12345';
    private const TEST_PROMPT = 'Describe this image for alt text';
    private const TEST_MODEL = 'claude-3-5-haiku-latest';
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
                'content' => [
                    [
                        'text' => $expectedAltText
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

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

        $provider = new AnthropicResponse($httpClient, $config);

        // Assert
        $this->expectException(AnthropicException::class);
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

        $provider = new AnthropicResponse($httpClient, $config);

        // Assert
        $this->expectException(AnthropicException::class);
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

        $provider = new AnthropicResponse($httpClient, $config);

        // Assert
        $this->expectException(AnthropicException::class);
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
                $this->equalTo(Constants::AATXT_ANTHROPIC_ENDPOINT),
                $this->callback(function ($headers) {
                    return isset($headers['x-api-key'])
                        && $headers['x-api-key'] === self::TEST_API_KEY
                        && isset($headers['anthropic-version'])
                        && $headers['anthropic-version'] === Constants::AATXT_API_VERSION
                        && isset($headers['Content-Type'])
                        && $headers['Content-Type'] === 'application/json';
                }),
                $this->anything()
            )
            ->willReturn([
                'content' => [
                    ['text' => 'Test alt text']
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

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
                    // Verify max_tokens
                    if ($body['max_tokens'] !== 1024) {
                        return false;
                    }
                    // Verify message structure
                    if (!isset($body['messages'][0]['role']) || $body['messages'][0]['role'] !== 'user') {
                        return false;
                    }
                    // Verify image content
                    $content = $body['messages'][0]['content'];
                    $hasImage = false;
                    $hasText = false;
                    foreach ($content as $item) {
                        if ($item['type'] === 'image' && $item['source']['url'] === self::TEST_IMAGE_URL) {
                            $hasImage = true;
                        }
                        if ($item['type'] === 'text' && $item['text'] === self::TEST_PROMPT) {
                            $hasText = true;
                        }
                    }
                    return $hasImage && $hasText;
                })
            )
            ->willReturn([
                'content' => [
                    ['text' => 'Test alt text']
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @covers ::response
     */
    public function testItHandlesEmptyTextInResponse(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'content' => [
                    ['text' => '']
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

        // Assert - empty string is falsy, so it should throw
        $this->expectException(AnthropicException::class);
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

        $provider = new AnthropicResponse($httpClient, $config);

        // Act
        $mimeTypes = $provider->getSupportedMimeTypes();

        // Assert
        $this->assertIsArray($mimeTypes);
        $this->assertNotEmpty($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('image/png', $mimeTypes);
        $this->assertContains('image/gif', $mimeTypes);
        $this->assertContains('image/webp', $mimeTypes);
    }

    /**
     * @covers ::supportsImage
     */
    public function testItCorrectlyValidatesSupportedMimeTypes(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
        $config = $this->createStub(AIProviderConfig::class);

        $provider = new AnthropicResponse($httpClient, $config);

        // Assert
        $this->assertTrue($provider->supportsImage('image/jpeg'));
        $this->assertTrue($provider->supportsImage('image/png'));
        $this->assertTrue($provider->supportsImage('image/gif'));
        $this->assertTrue($provider->supportsImage('image/webp'));
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

        $validProvider = new AnthropicResponse($httpClient, $validConfig);
        $shortProvider = new AnthropicResponse($httpClient, $shortConfig);
        $emptyProvider = new AnthropicResponse($httpClient, $emptyConfig);

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

        $providerWithKey = new AnthropicResponse($httpClient, $configWithKey);
        $providerWithoutKey = new AnthropicResponse($httpClient, $configWithoutKey);

        // Assert
        $this->assertTrue($providerWithKey->hasApiKey());
        $this->assertFalse($providerWithoutKey->hasApiKey());
    }

    /**
     * @covers ::response
     */
    public function testItReturnsTextWithoutModification(): void
    {
        // Arrange - Anthropic doesn't clean the response like OpenAI
        $expectedAltText = 'A beautiful sunset over the ocean';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'content' => [
                    [
                        'text' => $expectedAltText
                    ]
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @covers ::response
     */
    public function testItHandlesMultipleContentBlocks(): void
    {
        // Arrange - returns first text block
        $expectedAltText = 'First text block';

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'content' => [
                    ['text' => $expectedAltText],
                    ['text' => 'Second text block']
                ]
            ]);

        $config = $this->createStub(AIProviderConfig::class);
        $config->method('getApiKey')->willReturn(self::TEST_API_KEY);
        $config->method('getPrompt')->willReturn(self::TEST_PROMPT);
        $config->method('getModel')->willReturn(self::TEST_MODEL);

        $provider = new AnthropicResponse($httpClient, $config);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }
}
