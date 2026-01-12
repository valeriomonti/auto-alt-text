<?php

namespace AATXT\Tests\Unit\Configuration;

use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Configuration\AzureConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AzureConfig
 */
class AzureConfigTest extends TestCase
{
    /**
     * Test config implements AIProviderConfig interface
     */
    public function testImplementsAIProviderConfigInterface(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com');

        $this->assertInstanceOf(AIProviderConfig::class, $config);
    }

    /**
     * Test getApiKey returns correct value
     */
    public function testGetApiKeyReturnsCorrectValue(): void
    {
        $config = new AzureConfig('azure-api-key-123', 'https://endpoint.azure.com');

        $this->assertEquals('azure-api-key-123', $config->getApiKey());
    }

    /**
     * Test getEndpoint returns correct value
     */
    public function testGetEndpointReturnsCorrectValue(): void
    {
        $config = new AzureConfig('api-key', 'https://my-vision.cognitiveservices.azure.com/');

        $this->assertEquals('https://my-vision.cognitiveservices.azure.com/', $config->getEndpoint());
    }

    /**
     * Test getModel returns API version
     */
    public function testGetModelReturnsApiVersion(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com', '2023-10-01');

        $this->assertEquals('2023-10-01', $config->getModel());
    }

    /**
     * Test getPrompt returns empty string by default
     */
    public function testGetPromptReturnsEmptyStringByDefault(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com');

        $this->assertEquals('', $config->getPrompt());
    }

    /**
     * Test translation configuration
     */
    public function testTranslationConfiguration(): void
    {
        $config = new AzureConfig(
            'api-key',
            'https://endpoint.azure.com',
            '',
            '',
            'translation-key',
            'https://translator.azure.com',
            'westeurope',
            'it'
        );

        $this->assertEquals('translation-key', $config->getTranslationApiKey());
        $this->assertEquals('https://translator.azure.com', $config->getTranslationEndpoint());
        $this->assertEquals('westeurope', $config->getRegion());
        $this->assertEquals('it', $config->getTranslationLanguage());
    }

    /**
     * Test shouldTranslate returns true when configured for non-English
     */
    public function testShouldTranslateReturnsTrueWhenConfigured(): void
    {
        $config = new AzureConfig(
            'api-key',
            'https://endpoint.azure.com',
            '',
            '',
            'translation-key',
            'https://translator.azure.com',
            'westeurope',
            'fr'
        );

        $this->assertTrue($config->shouldTranslate());
    }

    /**
     * Test shouldTranslate returns false when language is English
     */
    public function testShouldTranslateReturnsFalseForEnglish(): void
    {
        $config = new AzureConfig(
            'api-key',
            'https://endpoint.azure.com',
            '',
            '',
            'translation-key',
            'https://translator.azure.com',
            'westeurope',
            'en'
        );

        $this->assertFalse($config->shouldTranslate());
    }

    /**
     * Test shouldTranslate returns false when API key is empty
     */
    public function testShouldTranslateReturnsFalseWhenApiKeyEmpty(): void
    {
        $config = new AzureConfig(
            'api-key',
            'https://endpoint.azure.com',
            '',
            '',
            '',
            'https://translator.azure.com',
            'westeurope',
            'it'
        );

        $this->assertFalse($config->shouldTranslate());
    }

    /**
     * Test shouldTranslate returns false when endpoint is empty
     */
    public function testShouldTranslateReturnsFalseWhenEndpointEmpty(): void
    {
        $config = new AzureConfig(
            'api-key',
            'https://endpoint.azure.com',
            '',
            '',
            'translation-key',
            '',
            'westeurope',
            'it'
        );

        $this->assertFalse($config->shouldTranslate());
    }

    /**
     * Test default translation language is English
     */
    public function testDefaultTranslationLanguageIsEnglish(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com');

        $this->assertEquals('en', $config->getTranslationLanguage());
    }

    /**
     * Test config with all optional parameters empty
     */
    public function testConfigWithAllOptionalParametersEmpty(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com');

        $this->assertEquals('', $config->getModel());
        $this->assertEquals('', $config->getPrompt());
        $this->assertEquals('', $config->getTranslationApiKey());
        $this->assertEquals('', $config->getTranslationEndpoint());
        $this->assertEquals('', $config->getRegion());
        $this->assertEquals('en', $config->getTranslationLanguage());
        $this->assertFalse($config->shouldTranslate());
    }

    /**
     * Test config is immutable
     */
    public function testConfigIsImmutable(): void
    {
        $config = new AzureConfig('api-key', 'https://endpoint.azure.com');

        $this->assertFalse(method_exists($config, 'setApiKey'));
        $this->assertFalse(method_exists($config, 'setEndpoint'));
        $this->assertFalse(method_exists($config, 'setTranslationLanguage'));
    }
}
