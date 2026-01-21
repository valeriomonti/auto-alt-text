<?php

namespace AATXT\Tests\Unit\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Decorators\ValidationDecorator;
use AATXT\App\Domain\Exceptions\EmptyResponseException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValidationDecorator
 *
 * Note: This decorator validates that responses are not empty but does NOT
 * truncate responses. Alt text length should be controlled by the user's
 * prompt to the AI provider.
 */
class ValidationDecoratorTest extends TestCase
{
    /**
     * Test decorator passes through valid text unchanged
     */
    public function testPassesThroughValidTextUnchanged(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('Valid alt text');

        $decorator = new ValidationDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('Valid alt text', $result);
    }

    /**
     * Test decorator trims whitespace
     */
    public function testTrimsWhitespace(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('   Trimmed text   ');

        $decorator = new ValidationDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('Trimmed text', $result);
    }

    /**
     * Test decorator throws exception for empty response
     */
    public function testThrowsExceptionForEmptyResponse(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('');

        $decorator = new ValidationDecorator($provider);

        $this->expectException(EmptyResponseException::class);

        $decorator->response('http://example.com/image.jpg');
    }

    /**
     * Test decorator throws exception for whitespace-only response
     */
    public function testThrowsExceptionForWhitespaceOnlyResponse(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('   ');

        $decorator = new ValidationDecorator($provider);

        $this->expectException(EmptyResponseException::class);

        $decorator->response('http://example.com/image.jpg');
    }

    /**
     * Test decorator does not throw when throwOnEmpty is false
     */
    public function testDoesNotThrowWhenThrowOnEmptyIsFalse(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('');

        $decorator = new ValidationDecorator($provider, false);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('', $result);
    }

    /**
     * Test decorator does NOT truncate long text
     *
     * Alt text length should be controlled by the user's prompt to the AI provider,
     * not by the decorator.
     */
    public function testDoesNotTruncateLongText(): void
    {
        $longText = str_repeat('a', 500); // 500 characters

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn($longText);

        $decorator = new ValidationDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        // Text should be returned unchanged (no truncation)
        $this->assertEquals(500, mb_strlen($result));
        $this->assertEquals($longText, $result);
    }

    /**
     * Test throwsOnEmpty returns true by default
     */
    public function testThrowsOnEmptyReturnsTrueByDefault(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $decorator = new ValidationDecorator($provider);

        $this->assertTrue($decorator->throwsOnEmpty());
    }

    /**
     * Test throwsOnEmpty returns configured value
     */
    public function testThrowsOnEmptyReturnsConfiguredValue(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $decorator = new ValidationDecorator($provider, false);

        $this->assertFalse($decorator->throwsOnEmpty());
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

        $decorator = new ValidationDecorator($provider);

        $decorator->response($imageUrl);
    }

    /**
     * Test exception message contains image URL
     */
    public function testExceptionMessageContainsImageUrl(): void
    {
        $imageUrl = 'http://example.com/test.jpg';

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('');

        $decorator = new ValidationDecorator($provider);

        $this->expectException(EmptyResponseException::class);
        $this->expectExceptionMessage($imageUrl);

        $decorator->response($imageUrl);
    }
}
