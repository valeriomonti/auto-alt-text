<?php

namespace AATXT\Tests\Unit\Domain\ValueObjects;

use AATXT\App\Domain\ValueObjects\AltText;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the AltText Value Object
 */
class AltTextTest extends TestCase
{
    /**
     * Test creating AltText from string
     */
    public function testFromStringCreatesAltText(): void
    {
        $altText = AltText::fromString('A beautiful sunset over the ocean');

        $this->assertInstanceOf(AltText::class, $altText);
        $this->assertEquals('A beautiful sunset over the ocean', $altText->toString());
    }

    /**
     * Test creating AltText trims whitespace
     */
    public function testFromStringTrimsWhitespace(): void
    {
        $altText = AltText::fromString('  Some text with spaces  ');

        $this->assertEquals('Some text with spaces', $altText->toString());
    }

    /**
     * Test creating empty AltText
     */
    public function testEmptyCreatesEmptyAltText(): void
    {
        $altText = AltText::empty();

        $this->assertInstanceOf(AltText::class, $altText);
        $this->assertEquals('', $altText->toString());
        $this->assertTrue($altText->isEmpty());
    }

    /**
     * Test toString returns correct value
     */
    public function testToStringReturnsValue(): void
    {
        $value = 'Test alt text';
        $altText = AltText::fromString($value);

        $this->assertEquals($value, $altText->toString());
    }

    /**
     * Test isEmpty returns true for empty string
     */
    public function testIsEmptyReturnsTrueForEmptyString(): void
    {
        $altText = AltText::fromString('');

        $this->assertTrue($altText->isEmpty());
    }

    /**
     * Test isEmpty returns true for whitespace only
     */
    public function testIsEmptyReturnsTrueForWhitespaceOnly(): void
    {
        $altText = AltText::fromString('   ');

        $this->assertTrue($altText->isEmpty());
    }

    /**
     * Test isEmpty returns false for non-empty string
     */
    public function testIsEmptyReturnsFalseForNonEmptyString(): void
    {
        $altText = AltText::fromString('Some text');

        $this->assertFalse($altText->isEmpty());
    }

    /**
     * Test length returns correct character count
     */
    public function testLengthReturnsCorrectCount(): void
    {
        $altText = AltText::fromString('Hello');

        $this->assertEquals(5, $altText->length());
    }

    /**
     * Test length with multibyte characters
     */
    public function testLengthWithMultibyteCharacters(): void
    {
        $altText = AltText::fromString('Ciao mondo!');

        $this->assertEquals(11, $altText->length());
    }

    /**
     * Test exceedsMaxLength returns false for short text
     */
    public function testExceedsMaxLengthReturnsFalseForShortText(): void
    {
        $altText = AltText::fromString('Short text');

        $this->assertFalse($altText->exceedsMaxLength());
    }

    /**
     * Test exceedsMaxLength returns false for exactly MAX_LENGTH
     */
    public function testExceedsMaxLengthReturnsFalseForExactLength(): void
    {
        $text = str_repeat('a', AltText::MAX_LENGTH);
        $altText = AltText::fromString($text);

        $this->assertFalse($altText->exceedsMaxLength());
    }

    /**
     * Test exceedsMaxLength returns true for long text
     */
    public function testExceedsMaxLengthReturnsTrueForLongText(): void
    {
        $text = str_repeat('a', AltText::MAX_LENGTH + 1);
        $altText = AltText::fromString($text);

        $this->assertTrue($altText->exceedsMaxLength());
    }

    /**
     * Test truncate returns same instance if already short enough
     */
    public function testTruncateReturnsSameInstanceIfShortEnough(): void
    {
        $altText = AltText::fromString('Short text');

        $truncated = $altText->truncate(50);

        $this->assertSame($altText, $truncated);
    }

    /**
     * Test truncate shortens long text
     */
    public function testTruncateShortenLongText(): void
    {
        $altText = AltText::fromString('This is a very long text that should be truncated');

        $truncated = $altText->truncate(20);

        $this->assertLessThanOrEqual(20, $truncated->length());
        $this->assertNotEquals($altText->toString(), $truncated->toString());
    }

    /**
     * Test truncate uses default MAX_LENGTH
     */
    public function testTruncateUsesDefaultMaxLength(): void
    {
        $longText = str_repeat('word ', 50); // About 250 characters
        $altText = AltText::fromString($longText);

        $truncated = $altText->truncate();

        $this->assertLessThanOrEqual(AltText::MAX_LENGTH, $truncated->length());
    }

    /**
     * Test truncate throws exception for non-positive length
     */
    public function testTruncateThrowsExceptionForNonPositiveLength(): void
    {
        $altText = AltText::fromString('Some text');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Truncate length must be positive');

        $altText->truncate(0);
    }

    /**
     * Test truncate throws exception for negative length
     */
    public function testTruncateThrowsExceptionForNegativeLength(): void
    {
        $altText = AltText::fromString('Some text');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Truncate length must be positive');

        $altText->truncate(-5);
    }

    /**
     * Test equals returns true for equal values
     */
    public function testEqualsReturnsTrueForEqualValues(): void
    {
        $altText1 = AltText::fromString('Same text');
        $altText2 = AltText::fromString('Same text');

        $this->assertTrue($altText1->equals($altText2));
    }

    /**
     * Test equals returns false for different values
     */
    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $altText1 = AltText::fromString('Text one');
        $altText2 = AltText::fromString('Text two');

        $this->assertFalse($altText1->equals($altText2));
    }

    /**
     * Test __toString magic method
     */
    public function testMagicToString(): void
    {
        $value = 'Magic string test';
        $altText = AltText::fromString($value);

        $this->assertEquals($value, (string) $altText);
    }

    /**
     * Test MAX_LENGTH constant value
     */
    public function testMaxLengthConstant(): void
    {
        $this->assertEquals(125, AltText::MAX_LENGTH);
    }

    /**
     * Test truncate preserves word boundaries when possible
     */
    public function testTruncatePreservesWordBoundaries(): void
    {
        $altText = AltText::fromString('This is a sentence with multiple words that is long');

        $truncated = $altText->truncate(30);

        // Should end at a word boundary, not cutting a word
        $this->assertStringNotContainsString(' tha', $truncated->toString());
    }
}
