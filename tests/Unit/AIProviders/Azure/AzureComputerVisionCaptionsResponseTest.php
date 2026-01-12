<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Azure;

use AATXT\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use AATXT\App\AIProviders\Azure\AzureTranslator;
use AATXT\App\Configuration\AzureConfig;
use AATXT\App\Exceptions\Azure\AzureComputerVisionException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AzureComputerVisionCaptionsResponse provider with mocked dependencies.
 *
 * These tests verify the Azure Computer Vision provider's behavior without making real API calls.
 * Uses concrete AzureConfig instances since the class is final and cannot be mocked.
 *
 * @coversDefaultClass \AATXT\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse
 */
class AzureComputerVisionCaptionsResponseTest extends TestCase
{
    private const TEST_API_KEY = 'azure-test-api-key-12345';
    private const TEST_ENDPOINT = 'https://test-region.api.cognitive.microsoft.com/';
    private const TEST_IMAGE_URL = 'https://example.com/image.jpg';

    /**
     * @test
     * @covers ::response
     */
    public function it_returns_caption_from_successful_api_response(): void
    {
        // Arrange
        $expectedCaption = 'A cat sitting on a windowsill';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->willReturn([
                'captionResult' => [
                    'text' => $expectedCaption
                ]
            ]);

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedCaption, $result);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_translates_caption_when_translation_is_enabled(): void
    {
        // Arrange
        $englishCaption = 'A cat sitting on a windowsill';
        $translatedCaption = 'Un gatto seduto sul davanzale';
        $targetLanguage = 'it';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'captionResult' => [
                    'text' => $englishCaption
                ]
            ]);

        $config = $this->createAzureConfig(true, $targetLanguage);

        $translator = $this->createMock(AzureTranslator::class);
        $translator->expects($this->once())
            ->method('translate')
            ->with($englishCaption, $targetLanguage)
            ->willReturn($translatedCaption);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($translatedCaption, $result);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_does_not_translate_when_language_is_default(): void
    {
        // Arrange
        $expectedCaption = 'A cat sitting on a windowsill';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'captionResult' => [
                    'text' => $expectedCaption
                ]
            ]);

        // Using default language (en) should skip translation
        $config = $this->createAzureConfig(false);

        $translator = $this->createMock(AzureTranslator::class);
        $translator->expects($this->never())
            ->method('translate');

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Act
        $result = $provider->response(self::TEST_IMAGE_URL);

        // Assert
        $this->assertEquals($expectedCaption, $result);
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
                    'code' => 'InvalidImageUrl',
                    'message' => 'The URL is not accessible'
                ]
            ]);

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Assert
        $this->expectException(AzureComputerVisionException::class);
        $this->expectExceptionMessageMatches('/InvalidImageUrl/');

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

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Assert
        $this->expectException(AzureComputerVisionException::class);
        $this->expectExceptionMessageMatches('/HTTP request failed/');

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_sends_correct_headers_with_subscription_key(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->stringContains(self::TEST_ENDPOINT),
                $this->callback(function ($headers) {
                    return isset($headers['Ocp-Apim-Subscription-Key'])
                        && $headers['Ocp-Apim-Subscription-Key'] === self::TEST_API_KEY
                        && isset($headers['content-type'])
                        && $headers['content-type'] === 'application/json';
                }),
                $this->anything()
            )
            ->willReturn([
                'captionResult' => ['text' => 'Test caption']
            ]);

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Act
        $provider->response(self::TEST_IMAGE_URL);
    }

    /**
     * @test
     * @covers ::response
     */
    public function it_sends_correct_url_with_api_version_and_features(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function ($url) {
                    return strpos($url, self::TEST_ENDPOINT) === 0
                        && strpos($url, 'computervision/imageanalysis:analyze') !== false
                        && strpos($url, 'api-version=' . Constants::AATXT_AZURE_COMPUTER_VISION_API_VERSION) !== false
                        && strpos($url, 'features=caption') !== false;
                }),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([
                'captionResult' => ['text' => 'Test caption']
            ]);

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

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
                    return isset($body['url']) && $body['url'] === self::TEST_IMAGE_URL;
                })
            )
            ->willReturn([
                'captionResult' => ['text' => 'Test caption']
            ]);

        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

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
        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

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
        $config = $this->createAzureConfig(false);
        $translator = $this->createMock(AzureTranslator::class);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

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
        $translator = $this->createMock(AzureTranslator::class);

        $validConfig = $this->createAzureConfig(false);
        $invalidConfig = new AzureConfig('', self::TEST_ENDPOINT);

        $validProvider = new AzureComputerVisionCaptionsResponse($httpClient, $validConfig, $translator);
        $invalidProvider = new AzureComputerVisionCaptionsResponse($httpClient, $invalidConfig, $translator);

        // Assert
        $this->assertTrue($validProvider->validateCredentials());
        $this->assertFalse($invalidProvider->validateCredentials());
    }

    /**
     * @test
     * @covers ::hasApiKey
     */
    public function it_checks_if_api_key_exists(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $translator = $this->createMock(AzureTranslator::class);

        $configWithKey = $this->createAzureConfig(false);
        $configWithoutKey = new AzureConfig('', self::TEST_ENDPOINT);

        $providerWithKey = new AzureComputerVisionCaptionsResponse($httpClient, $configWithKey, $translator);
        $providerWithoutKey = new AzureComputerVisionCaptionsResponse($httpClient, $configWithoutKey, $translator);

        // Assert
        $this->assertTrue($providerWithKey->hasApiKey());
        $this->assertFalse($providerWithoutKey->hasApiKey());
    }

    /**
     * @test
     * @covers ::isTranslationEnabled
     */
    public function it_correctly_reports_translation_status(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $translator = $this->createMock(AzureTranslator::class);

        $configWithTranslation = $this->createAzureConfig(true, 'it');
        $configWithoutTranslation = $this->createAzureConfig(false);

        $providerWithTranslation = new AzureComputerVisionCaptionsResponse(
            $httpClient,
            $configWithTranslation,
            $translator
        );
        $providerWithoutTranslation = new AzureComputerVisionCaptionsResponse(
            $httpClient,
            $configWithoutTranslation,
            $translator
        );

        // Assert
        $this->assertTrue($providerWithTranslation->isTranslationEnabled());
        $this->assertFalse($providerWithoutTranslation->isTranslationEnabled());
    }

    /**
     * @test
     * @covers ::getTargetLanguage
     */
    public function it_returns_target_language(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $translator = $this->createMock(AzureTranslator::class);
        $targetLanguage = 'fr';

        $config = $this->createAzureConfig(true, $targetLanguage);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Assert
        $this->assertEquals($targetLanguage, $provider->getTargetLanguage());
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_delegates_translation_to_translator(): void
    {
        // Arrange
        $text = 'Hello world';
        $translatedText = 'Ciao mondo';
        $targetLanguage = 'it';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $config = $this->createAzureConfig(true, $targetLanguage);

        $translator = $this->createMock(AzureTranslator::class);
        $translator->expects($this->once())
            ->method('translate')
            ->with($text, $targetLanguage)
            ->willReturn($translatedText);

        $provider = new AzureComputerVisionCaptionsResponse($httpClient, $config, $translator);

        // Act
        $result = $provider->translate($text, $targetLanguage);

        // Assert
        $this->assertEquals($translatedText, $result);
    }

    /**
     * Helper method to create a concrete AzureConfig instance
     *
     * @param bool $translationEnabled
     * @param string $translationLanguage
     * @return AzureConfig
     */
    private function createAzureConfig(
        bool $translationEnabled,
        string $translationLanguage = 'en'
    ): AzureConfig {
        if ($translationEnabled) {
            return new AzureConfig(
                self::TEST_API_KEY,
                self::TEST_ENDPOINT,
                '',
                '',
                'translation-api-key',
                'https://api.cognitive.microsofttranslator.com/',
                'westeurope',
                $translationLanguage
            );
        }

        return new AzureConfig(
            self::TEST_API_KEY,
            self::TEST_ENDPOINT,
            '',
            '',
            '',
            '',
            '',
            'en' // Default language (no translation)
        );
    }
}
