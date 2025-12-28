<?php

namespace AATXT\Tests\Unit\Domain\Exceptions;

use AATXT\App\Domain\Exceptions\InvalidImageException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InvalidImageException
 */
class InvalidImageExceptionTest extends TestCase
{
    /**
     * Test exception with basic parameters
     */
    public function testExceptionWithBasicParameters(): void
    {
        $exception = new InvalidImageException(123, 'image/bmp');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertStringContainsString('Image ID 123', $exception->getMessage());
        $this->assertStringContainsString('image/bmp', $exception->getMessage());
        $this->assertStringContainsString('none specified', $exception->getMessage());
    }

    /**
     * Test exception with supported types list
     */
    public function testExceptionWithSupportedTypes(): void
    {
        $supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $exception = new InvalidImageException(456, 'image/gif', $supportedTypes);

        $this->assertStringContainsString('Image ID 456', $exception->getMessage());
        $this->assertStringContainsString('image/gif', $exception->getMessage());
        $this->assertStringContainsString('image/jpeg, image/png, image/webp', $exception->getMessage());
    }

    /**
     * Test exception message format
     */
    public function testExceptionMessageFormat(): void
    {
        $exception = new InvalidImageException(789, 'application/pdf', ['image/jpeg']);

        $expectedMessage = 'Image ID 789 has unsupported MIME type "application/pdf". Supported types: image/jpeg';
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * Test exception with empty supported types array
     */
    public function testExceptionWithEmptySupportedTypes(): void
    {
        $exception = new InvalidImageException(100, 'image/tiff', []);

        $this->assertStringContainsString('none specified', $exception->getMessage());
    }

    /**
     * Test exception is throwable
     */
    public function testExceptionIsThrowable(): void
    {
        $this->expectException(InvalidImageException::class);
        $this->expectExceptionMessage('Image ID 200');

        throw new InvalidImageException(200, 'video/mp4', ['image/jpeg', 'image/png']);
    }
}
