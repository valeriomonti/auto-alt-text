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
     * @covers ::translate
     */
    public function testItTranslatesTextSuccessfully(): void
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
     * @covers ::translate
     */
    public function testItReturnsOriginalTextWhenApiKeyIsMissing(): void
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
     * @covers ::translate
     */
    public function testItReturnsOriginalTextWhenRegionIsMissing(): void
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
     * @covers ::translate
     */
    public function testItReturnsOriginalTextWhenEndpointIsMissing(): void
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
     * @covers ::translate
     */
    public function testItThrowsExceptionOnApiError(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
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
     * @covers ::translate
     */
    public function testItThrowsExceptionOnHttpFailure(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
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
     * @covers ::translate
     */
    public function testItSendsCorrectHeaders(): void
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
     * @covers ::translate
     */
    public function testItSendsCorrectUrlWithLanguageParameter(): void
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
     * @covers ::translate
     */
    public function testItSendsTextInCorrectPayloadFormat(): void
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
     * @covers ::supportedLanguages
     */
    public function testItReturnsSupportedLanguages(): void
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
     * @covers ::supportedLanguages
     */
    public function testItReturnsEmptyArrayWhenApiKeyMissingForLanguages(): void
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
     * @covers ::supportedLanguages
     */
    public function testItReturnsEmptyArrayWhenEndpointMissingForLanguages(): void
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
     * @covers ::supportedLanguages
     */
    public function testItThrowsExceptionOnApiErrorForLanguages(): void
    {
        // Arrange
        $httpClient = $this->createStub(HttpClientInterface::class);
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
