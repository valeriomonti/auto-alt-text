<?php

namespace AATXT\Tests\Unit\Configuration;

use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Configuration\GeminiConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeminiConfig
 */
class GeminiConfigTest extends TestCase
{
    /**
     * Test config implements AIProviderConfig interface
     */
    public function testImplementsAIProviderConfigInterface(): void
    {
        $config = new GeminiConfig('api-key', 'prompt', 'gemini-3.5-flash');

        $this->assertInstanceOf(AIProviderConfig::class, $config);
    }

    /**
     * Test getApiKey returns correct value
     */
    public function testGetApiKeyReturnsCorrectValue(): void
    {
        $config = new GeminiConfig('AIza-test-key', 'prompt', 'model');

        $this->assertEquals('AIza-test-key', $config->getApiKey());
    }

    /**
     * Test getPrompt returns correct value
     */
    public function testGetPromptReturnsCorrectValue(): void
    {
        $config = new GeminiConfig('api-key', 'Describe this image', 'model');

        $this->assertEquals('Describe this image', $config->getPrompt());
    }

    /**
     * Test getModel returns correct value
     */
    public function testGetModelReturnsCorrectValue(): void
    {
        $config = new GeminiConfig('api-key', 'prompt', 'gemini-3.5-flash');

        $this->assertEquals('gemini-3.5-flash', $config->getModel());
    }

    /**
     * Test config is immutable
     */
    public function testConfigIsImmutable(): void
    {
        $config = new GeminiConfig('api-key', 'prompt', 'model');

        $this->assertFalse(method_exists($config, 'setApiKey'));
        $this->assertFalse(method_exists($config, 'setPrompt'));
        $this->assertFalse(method_exists($config, 'setModel'));
    }

    /**
     * Test config with empty values
     */
    public function testConfigWithEmptyValues(): void
    {
        $config = new GeminiConfig('', '', '');

        $this->assertEquals('', $config->getApiKey());
        $this->assertEquals('', $config->getPrompt());
        $this->assertEquals('', $config->getModel());
    }

    /**
     * Test config with different Gemini models
     */
    public function testConfigWithDifferentModels(): void
    {
        $flash = new GeminiConfig('key', 'prompt', 'gemini-3.5-flash');
        $flash25 = new GeminiConfig('key', 'prompt', 'gemini-2.5-flash');

        $this->assertEquals('gemini-3.5-flash', $flash->getModel());
        $this->assertEquals('gemini-2.5-flash', $flash25->getModel());
    }
}
