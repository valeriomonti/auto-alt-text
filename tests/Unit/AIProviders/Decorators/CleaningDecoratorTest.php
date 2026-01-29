<?php

namespace AATXT\Tests\Unit\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Decorators\CleaningDecorator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CleaningDecorator
 */
class CleaningDecoratorTest extends TestCase
{
    /**
     * Test decorator passes through clean text unchanged
     */
    public function testPassesThroughCleanTextUnchanged(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('Clean text without issues');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('Clean text without issues', $result);
    }

    /**
     * Test decorator removes double quotes
     */
    public function testRemovesDoubleQuotes(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('"A person walking"');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('A person walking', $result);
    }

    /**
     * Test decorator removes HTML quote entities
     */
    public function testRemovesHtmlQuoteEntities(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('&quot;A beautiful sunset&quot;');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('A beautiful sunset', $result);
    }

    /**
     * Test decorator removes multiple consecutive whitespaces
     * Note: The decorator removes 2+ consecutive spaces, not all spaces
     */
    public function testRemovesMultipleWhitespaces(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('A   person  walking    on beach');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        // Multiple consecutive spaces are removed, single spaces preserved
        $this->assertEquals('Apersonwalkingon beach', $result);
    }

    /**
     * Test decorator trims leading and trailing whitespace
     */
    public function testTrimsLeadingAndTrailingWhitespace(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('   Trimmed text   ');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        // Trims leading/trailing but preserves single internal space
        $this->assertEquals('Trimmed text', $result);
    }

    /**
     * Test decorator handles empty string
     */
    public function testHandlesEmptyString(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('', $result);
    }

    /**
     * Test decorator handles string with only quotes
     */
    public function testHandlesStringWithOnlyQuotes(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('"""');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('', $result);
    }

    /**
     * Test decorator combines all cleaning operations
     */
    public function testCombinesAllCleaningOperations(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('"A   &quot;person&quot;  in park"');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        // Removes quotes, entities, and multiple spaces
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString('&quot;', $result);
    }

    /**
     * Test decorator calls wrapped provider with correct URL
     */
    public function testCallsProviderWithCorrectUrl(): void
    {
        $imageUrl = 'http://example.com/test-image.png';

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->expects($this->once())
            ->method('response')
            ->with($imageUrl)
            ->willReturn('Test response');

        $decorator = new CleaningDecorator($provider);

        $decorator->response($imageUrl);
    }

    /**
     * Test decorator preserves single spaces between words
     */
    public function testPreservesSingleSpaces(): void
    {
        $provider = $this->createStub(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('A person walking on the beach');

        $decorator = new CleaningDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('A person walking on the beach', $result);
    }
}
