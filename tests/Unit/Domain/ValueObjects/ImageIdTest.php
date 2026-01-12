<?php

namespace AATXT\Tests\Unit\Domain\ValueObjects;

use AATXT\App\Domain\ValueObjects\ImageId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ImageId Value Object
 */
class ImageIdTest extends TestCase
{
    /**
     * Test creating ImageId from valid integer
     */
    public function testFromIntCreatesImageId(): void
    {
        $imageId = ImageId::fromInt(123);

        $this->assertInstanceOf(ImageId::class, $imageId);
        $this->assertEquals(123, $imageId->toInt());
    }

    /**
     * Test creating ImageId with value 1 (minimum valid)
     */
    public function testFromIntWithMinimumValue(): void
    {
        $imageId = ImageId::fromInt(1);

        $this->assertEquals(1, $imageId->toInt());
    }

    /**
     * Test creating ImageId with large value
     */
    public function testFromIntWithLargeValue(): void
    {
        $largeId = 999999999;
        $imageId = ImageId::fromInt($largeId);

        $this->assertEquals($largeId, $imageId->toInt());
    }

    /**
     * Test fromInt throws exception for zero
     */
    public function testFromIntThrowsExceptionForZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image ID must be a positive integer, got: 0');

        ImageId::fromInt(0);
    }

    /**
     * Test fromInt throws exception for negative value
     */
    public function testFromIntThrowsExceptionForNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image ID must be a positive integer, got: -5');

        ImageId::fromInt(-5);
    }

    /**
     * Test toInt returns correct value
     */
    public function testToIntReturnsValue(): void
    {
        $value = 456;
        $imageId = ImageId::fromInt($value);

        $this->assertEquals($value, $imageId->toInt());
    }

    /**
     * Test equals returns true for equal values
     */
    public function testEqualsReturnsTrueForEqualValues(): void
    {
        $imageId1 = ImageId::fromInt(100);
        $imageId2 = ImageId::fromInt(100);

        $this->assertTrue($imageId1->equals($imageId2));
    }

    /**
     * Test equals returns false for different values
     */
    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $imageId1 = ImageId::fromInt(100);
        $imageId2 = ImageId::fromInt(200);

        $this->assertFalse($imageId1->equals($imageId2));
    }

    /**
     * Test __toString magic method
     */
    public function testMagicToString(): void
    {
        $imageId = ImageId::fromInt(789);

        $this->assertEquals('789', (string) $imageId);
    }

    /**
     * Test immutability - creating new instances from same value
     */
    public function testImmutability(): void
    {
        $imageId1 = ImageId::fromInt(50);
        $imageId2 = ImageId::fromInt(50);

        // Same value but different instances
        $this->assertTrue($imageId1->equals($imageId2));
        $this->assertNotSame($imageId1, $imageId2);
    }
}
