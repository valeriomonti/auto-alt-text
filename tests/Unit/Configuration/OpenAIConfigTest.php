<?php

namespace AATXT\Tests\Unit\Configuration;

use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Configuration\OpenAIConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OpenAIConfig
 */
class OpenAIConfigTest extends TestCase
{
    /**
     * Test config implements AIProviderConfig interface
     */
    public function testImplementsAIProviderConfigInterface(): void
    {
        $config = new OpenAIConfig('api-key', 'prompt', 'gpt-4o');

        $this->assertInstanceOf(AIProviderConfig::class, $config);
    }

    /**
     * Test getApiKey returns correct value
     */
    public function testGetApiKeyReturnsCorrectValue(): void
    {
        $config = new OpenAIConfig('sk-test-api-key-123', 'prompt', 'model');

        $this->assertEquals('sk-test-api-key-123', $config->getApiKey());
    }

    /**
     * Test getPrompt returns correct value
     */
    public function testGetPromptReturnsCorrectValue(): void
    {
        $config = new OpenAIConfig('api-key', 'Generate alt text for this image', 'model');

        $this->assertEquals('Generate alt text for this image', $config->getPrompt());
    }

    /**
     * Test getModel returns correct value
     */
    public function testGetModelReturnsCorrectValue(): void
    {
        $config = new OpenAIConfig('api-key', 'prompt', 'gpt-4o-mini');

        $this->assertEquals('gpt-4o-mini', $config->getModel());
    }

    /**
     * Test config is immutable
     */
    public function testConfigIsImmutable(): void
    {
        $config = new OpenAIConfig('api-key', 'prompt', 'model');

        // There should be no setter methods
        $this->assertFalse(method_exists($config, 'setApiKey'));
        $this->assertFalse(method_exists($config, 'setPrompt'));
        $this->assertFalse(method_exists($config, 'setModel'));
    }

    /**
     * Test config with empty values
     */
    public function testConfigWithEmptyValues(): void
    {
        $config = new OpenAIConfig('', '', '');

        $this->assertEquals('', $config->getApiKey());
        $this->assertEquals('', $config->getPrompt());
        $this->assertEquals('', $config->getModel());
    }

    /**
     * Test config with different OpenAI models
     */
    public function testConfigWithDifferentModels(): void
    {
        $gpt4o = new OpenAIConfig('key', 'prompt', 'gpt-4o');
        $gpt4oMini = new OpenAIConfig('key', 'prompt', 'gpt-4o-mini');
        $o1Mini = new OpenAIConfig('key', 'prompt', 'o1-mini');

        $this->assertEquals('gpt-4o', $gpt4o->getModel());
        $this->assertEquals('gpt-4o-mini', $gpt4oMini->getModel());
        $this->assertEquals('o1-mini', $o1Mini->getModel());
    }
}
