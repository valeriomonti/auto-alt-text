<?php

namespace AATXT\Tests\Unit\Configuration;

use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Configuration\AnthropicConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnthropicConfig
 */
class AnthropicConfigTest extends TestCase
{
    /**
     * Test config implements AIProviderConfig interface
     */
    public function testImplementsAIProviderConfigInterface(): void
    {
        $config = new AnthropicConfig('api-key', 'prompt', 'claude-3-5-haiku');

        $this->assertInstanceOf(AIProviderConfig::class, $config);
    }

    /**
     * Test getApiKey returns correct value
     */
    public function testGetApiKeyReturnsCorrectValue(): void
    {
        $config = new AnthropicConfig('sk-ant-test-key', 'prompt', 'model');

        $this->assertEquals('sk-ant-test-key', $config->getApiKey());
    }

    /**
     * Test getPrompt returns correct value
     */
    public function testGetPromptReturnsCorrectValue(): void
    {
        $config = new AnthropicConfig('api-key', 'Describe this image', 'model');

        $this->assertEquals('Describe this image', $config->getPrompt());
    }

    /**
     * Test getModel returns correct value
     */
    public function testGetModelReturnsCorrectValue(): void
    {
        $config = new AnthropicConfig('api-key', 'prompt', 'claude-sonnet-4-20250514');

        $this->assertEquals('claude-sonnet-4-20250514', $config->getModel());
    }

    /**
     * Test config is immutable
     */
    public function testConfigIsImmutable(): void
    {
        $config = new AnthropicConfig('api-key', 'prompt', 'model');

        $this->assertFalse(method_exists($config, 'setApiKey'));
        $this->assertFalse(method_exists($config, 'setPrompt'));
        $this->assertFalse(method_exists($config, 'setModel'));
    }

    /**
     * Test config with empty values
     */
    public function testConfigWithEmptyValues(): void
    {
        $config = new AnthropicConfig('', '', '');

        $this->assertEquals('', $config->getApiKey());
        $this->assertEquals('', $config->getPrompt());
        $this->assertEquals('', $config->getModel());
    }

    /**
     * Test config with different Anthropic models
     */
    public function testConfigWithDifferentModels(): void
    {
        $haiku = new AnthropicConfig('key', 'prompt', 'claude-3-5-haiku-20241022');
        $sonnet = new AnthropicConfig('key', 'prompt', 'claude-sonnet-4-20250514');

        $this->assertEquals('claude-3-5-haiku-20241022', $haiku->getModel());
        $this->assertEquals('claude-sonnet-4-20250514', $sonnet->getModel());
    }
}
