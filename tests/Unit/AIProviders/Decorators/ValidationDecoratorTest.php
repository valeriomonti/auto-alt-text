<?php

namespace AATXT\Tests\Unit\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Decorators\ValidationDecorator;
use AATXT\App\Domain\Exceptions\EmptyResponseException;
use AATXT\App\Domain\ValueObjects\AltText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValidationDecorator
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

        $decorator = new ValidationDecorator($provider, null, false);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals('', $result);
    }

    /**
     * Test decorator truncates long text
     */
    public function testTruncatesLongText(): void
    {
        $longText = str_repeat('a', 200); // 200 characters

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn($longText);

        $decorator = new ValidationDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertLessThanOrEqual(AltText::MAX_LENGTH, mb_strlen($result));
    }

    /**
     * Test decorator uses custom max length
     */
    public function testUsesCustomMaxLength(): void
    {
        $longText = str_repeat('a', 100);

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn($longText);

        $decorator = new ValidationDecorator($provider, 50);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertLessThanOrEqual(50, mb_strlen($result));
    }

    /**
     * Test decorator does not truncate text under max length
     */
    public function testDoesNotTruncateTextUnderMaxLength(): void
    {
        $shortText = 'Short text';

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn($shortText);

        $decorator = new ValidationDecorator($provider);

        $result = $decorator->response('http://example.com/image.jpg');

        $this->assertEquals($shortText, $result);
    }

    /**
     * Test decorator truncates at word boundary when possible
     */
    public function testTruncatesAtWordBoundary(): void
    {
        // Create text that needs truncation
        $text = 'This is a sentence with many words that will need to be truncated at some point during validation testing process';
        $customMaxLength = 60;

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn($text);

        $decorator = new ValidationDecorator($provider, $customMaxLength);

        $result = $decorator->response('http://example.com/image.jpg');

        // Should be shorter than or equal to max length
        $this->assertLessThanOrEqual($customMaxLength, mb_strlen($result));
    }

    /**
     * Test getMaxLength returns configured value
     */
    public function testGetMaxLengthReturnsConfiguredValue(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $decorator = new ValidationDecorator($provider, 100);

        $this->assertEquals(100, $decorator->getMaxLength());
    }

    /**
     * Test getMaxLength returns default when null passed
     */
    public function testGetMaxLengthReturnsDefaultWhenNullPassed(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $decorator = new ValidationDecorator($provider, null);

        $this->assertEquals(AltText::MAX_LENGTH, $decorator->getMaxLength());
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

        $decorator = new ValidationDecorator($provider, null, false);

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
