<?php

namespace AATXT\Tests\Unit\Domain\Exceptions;

use AATXT\App\Domain\Exceptions\UnsupportedGeneratorException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UnsupportedGeneratorException
 */
class UnsupportedGeneratorExceptionTest extends TestCase
{
    /**
     * Test exception with generator type
     */
    public function testExceptionWithGeneratorType(): void
    {
        $exception = new UnsupportedGeneratorException('unknown_generator');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertStringContainsString('unknown_generator', $exception->getMessage());
    }

    /**
     * Test exception message format
     */
    public function testExceptionMessageFormat(): void
    {
        $exception = new UnsupportedGeneratorException('my_custom_type');

        $expectedMessage = 'Unsupported generator type: "my_custom_type". Please register this generator type in the factory.';
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * Test exception with OpenAI type
     */
    public function testExceptionWithOpenAIType(): void
    {
        $exception = new UnsupportedGeneratorException('openai_vision_v2');

        $this->assertStringContainsString('openai_vision_v2', $exception->getMessage());
        $this->assertStringContainsString('register this generator type', $exception->getMessage());
    }

    /**
     * Test exception is throwable
     */
    public function testExceptionIsThrowable(): void
    {
        $this->expectException(UnsupportedGeneratorException::class);
        $this->expectExceptionMessage('invalid_provider');

        throw new UnsupportedGeneratorException('invalid_provider');
    }

    /**
     * Test exception with empty type
     */
    public function testExceptionWithEmptyType(): void
    {
        $exception = new UnsupportedGeneratorException('');

        $this->assertStringContainsString('Unsupported generator type: ""', $exception->getMessage());
    }
}
