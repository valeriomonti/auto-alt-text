<?php

namespace AATXT\Tests\Unit\Domain\Exceptions;

use AATXT\App\Domain\Exceptions\EmptyResponseException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EmptyResponseException
 */
class EmptyResponseExceptionTest extends TestCase
{
    /**
     * Test exception with default provider name
     */
    public function testExceptionWithDefaultProvider(): void
    {
        $exception = new EmptyResponseException();

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals(
            'AI Provider returned an empty response. Unable to generate alt text.',
            $exception->getMessage()
        );
    }

    /**
     * Test exception with custom provider name
     */
    public function testExceptionWithCustomProvider(): void
    {
        $exception = new EmptyResponseException('OpenAI');

        $this->assertEquals(
            'OpenAI returned an empty response. Unable to generate alt text.',
            $exception->getMessage()
        );
    }

    /**
     * Test exception with Anthropic provider
     */
    public function testExceptionWithAnthropicProvider(): void
    {
        $exception = new EmptyResponseException('Anthropic Claude');

        $this->assertStringContainsString('Anthropic Claude', $exception->getMessage());
    }

    /**
     * Test exception is throwable
     */
    public function testExceptionIsThrowable(): void
    {
        $this->expectException(EmptyResponseException::class);
        $this->expectExceptionMessage('Azure returned an empty response');

        throw new EmptyResponseException('Azure');
    }
}
