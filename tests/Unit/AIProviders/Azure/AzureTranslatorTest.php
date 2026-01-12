<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Azure;

use AATXT\App\AIProviders\Azure\AzureTranslator;
use AATXT\App\Configuration\AzureConfig;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AzureTranslator with mocked HTTP client.
 *
 * These tests verify the Azure Translator's behavior without making real API calls.
 * Uses concrete AzureConfig instances since the class is final and cannot be mocked.
 *
 * @coversDefaultClass \AATXT\App\AIProviders\Azure\AzureTranslator
 */
class AzureTranslatorTest extends TestCase
{
    private const TEST_API_KEY = 'azure-translation-api-key-12345';
    private const TEST_ENDPOINT = 'https://api.cognitive.microsofttranslator.com/';
    private const TEST_REGION = 'westeurope';
    private const TEST_VISION_ENDPOINT = 'https://test-region.api.cognitive.microsoft.com/';

    /**
     * @test
     * @covers ::translate
     */
    public function it_translates_text_successfully(): void
    {
        // Arrange
        $originalText = 'Hello world';
        $translatedText = 'Ciao mondo';
        $targetLanguage = 'it';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->willReturn([
                [
                    'translations' => [
                        [
                            'text' => $translatedText
                        ]
                    ]
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->translate($originalText, $targetLanguage);

        // Assert
        $this->assertEquals($translatedText, $result);
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_returns_original_text_when_api_key_is_missing(): void
    {
        // Arrange
        $originalText = 'Hello world';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('post');

        $config = new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            '', // Empty translation API key
            self::TEST_ENDPOINT,
            self::TEST_REGION,
            'it'
        );

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->translate($originalText, 'it');

        // Assert
        $this->assertEquals($originalText, $result);
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_returns_original_text_when_region_is_missing(): void
    {
        // Arrange
        $originalText = 'Hello world';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('post');

        $config = new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            self::TEST_API_KEY,
            self::TEST_ENDPOINT,
            '', // Empty region
            'it'
        );

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->translate($originalText, 'it');

        // Assert
        $this->assertEquals($originalText, $result);
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_returns_original_text_when_endpoint_is_missing(): void
    {
        // Arrange
        $originalText = 'Hello world';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('post');

        $config = new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            self::TEST_API_KEY,
            '', // Empty endpoint
            self::TEST_REGION,
            'it'
        );

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->translate($originalText, 'it');

        // Assert
        $this->assertEquals($originalText, $result);
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_throws_exception_on_api_error(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willReturn([
                'error' => [
                    'code' => '401000',
                    'message' => 'Invalid subscription key'
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Assert
        $this->expectException(AzureTranslateInstanceException::class);
        $this->expectExceptionMessageMatches('/401000/');

        // Act
        $translator->translate('Hello', 'it');
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_throws_exception_on_http_failure(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('post')
            ->willThrowException(new \Exception('Network error'));

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Assert
        $this->expectException(AzureTranslateInstanceException::class);
        $this->expectExceptionMessageMatches('/HTTP request failed/');

        // Act
        $translator->translate('Hello', 'it');
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_sends_correct_headers(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->callback(function ($headers) {
                    return isset($headers['Ocp-Apim-Subscription-Key'])
                        && $headers['Ocp-Apim-Subscription-Key'] === self::TEST_API_KEY
                        && isset($headers['Ocp-Apim-Subscription-Region'])
                        && $headers['Ocp-Apim-Subscription-Region'] === self::TEST_REGION
                        && isset($headers['Content-type'])
                        && $headers['Content-type'] === 'application/json';
                }),
                $this->anything()
            )
            ->willReturn([
                [
                    'translations' => [
                        ['text' => 'Translated']
                    ]
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $translator->translate('Hello', 'it');
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_sends_correct_url_with_language_parameter(): void
    {
        // Arrange
        $targetLanguage = 'fr';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function ($url) use ($targetLanguage) {
                    return strpos($url, self::TEST_ENDPOINT) === 0
                        && strpos($url, 'translate?api-version=3.0') !== false
                        && strpos($url, 'from=en') !== false
                        && strpos($url, 'to=' . $targetLanguage) !== false;
                }),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([
                [
                    'translations' => [
                        ['text' => 'Bonjour']
                    ]
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $translator->translate('Hello', $targetLanguage);
    }

    /**
     * @test
     * @covers ::translate
     */
    public function it_sends_text_in_correct_payload_format(): void
    {
        // Arrange
        $textToTranslate = 'Hello world';

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($payload) use ($textToTranslate) {
                    return isset($payload[0]['Text'])
                        && $payload[0]['Text'] === $textToTranslate;
                })
            )
            ->willReturn([
                [
                    'translations' => [
                        ['text' => 'Ciao mondo']
                    ]
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $translator->translate($textToTranslate, 'it');
    }

    /**
     * @test
     * @covers ::supportedLanguages
     */
    public function it_returns_supported_languages(): void
    {
        // Arrange
        $expectedLanguages = [
            'it' => ['name' => 'Italian', 'nativeName' => 'Italiano'],
            'fr' => ['name' => 'French', 'nativeName' => 'Français'],
        ];

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->willReturn([
                'translation' => $expectedLanguages
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->supportedLanguages();

        // Assert
        $this->assertEquals($expectedLanguages, $result);
    }

    /**
     * @test
     * @covers ::supportedLanguages
     */
    public function it_returns_empty_array_when_api_key_missing_for_languages(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $config = new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            '', // Empty translation API key
            self::TEST_ENDPOINT,
            self::TEST_REGION,
            'it'
        );

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->supportedLanguages();

        // Assert
        $this->assertEquals([], $result);
    }

    /**
     * @test
     * @covers ::supportedLanguages
     */
    public function it_returns_empty_array_when_endpoint_missing_for_languages(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $config = new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            self::TEST_API_KEY,
            '', // Empty translation endpoint
            self::TEST_REGION,
            'it'
        );

        $translator = new AzureTranslator($httpClient, $config);

        // Act
        $result = $translator->supportedLanguages();

        // Assert
        $this->assertEquals([], $result);
    }

    /**
     * @test
     * @covers ::supportedLanguages
     */
    public function it_throws_exception_on_api_error_for_languages(): void
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('get')
            ->willReturn([
                'error' => [
                    'code' => '401000',
                    'message' => 'Invalid subscription key'
                ]
            ]);

        $config = $this->createValidConfig();

        $translator = new AzureTranslator($httpClient, $config);

        // Assert
        $this->expectException(AzureTranslateInstanceException::class);
        $this->expectExceptionMessageMatches('/401000/');

        // Act
        $translator->supportedLanguages();
    }

    /**
     * Helper method to create a valid AzureConfig for translation
     *
     * @return AzureConfig
     */
    private function createValidConfig(): AzureConfig
    {
        return new AzureConfig(
            'vision-api-key',
            self::TEST_VISION_ENDPOINT,
            '',
            '',
            self::TEST_API_KEY,
            self::TEST_ENDPOINT,
            self::TEST_REGION,
            'it'
        );
    }
}
