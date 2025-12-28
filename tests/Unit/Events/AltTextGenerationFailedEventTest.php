<?php

namespace AATXT\Tests\Unit\Events;

use AATXT\App\Events\AltTextGenerationFailedEvent;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for AltTextGenerationFailedEvent
 */
class AltTextGenerationFailedEventTest extends TestCase
{
    /**
     * Test event stores image ID correctly
     */
    public function testGetImageIdReturnsCorrectValue(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $this->assertEquals(123, $event->getImageId());
    }

    /**
     * Test event stores provider correctly
     */
    public function testGetProviderReturnsCorrectValue(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'Anthropic', new Exception('Error'));

        $this->assertEquals('Anthropic', $event->getProvider());
    }

    /**
     * Test event stores exception correctly
     */
    public function testGetExceptionReturnsException(): void
    {
        $exception = new RuntimeException('API Error');
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', $exception);

        $this->assertSame($exception, $event->getException());
    }

    /**
     * Test getErrorMessage returns exception message
     */
    public function testGetErrorMessageReturnsExceptionMessage(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('API connection failed'));

        $this->assertEquals('API connection failed', $event->getErrorMessage());
    }

    /**
     * Test event stores timestamp
     */
    public function testGetTimestampReturnsTimestamp(): void
    {
        $before = time();
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));
        $after = time();

        $timestamp = $event->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    /**
     * Test isHandled returns false by default
     */
    public function testIsHandledReturnsFalseByDefault(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $this->assertFalse($event->isHandled());
    }

    /**
     * Test markAsHandled sets handled to true
     */
    public function testMarkAsHandledSetsHandledToTrue(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $event->markAsHandled();

        $this->assertTrue($event->isHandled());
    }

    /**
     * Test getFormattedErrorMessage returns correct format
     */
    public function testGetFormattedErrorMessageReturnsCorrectFormat(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI Vision', new Exception('Rate limit exceeded'));

        $formatted = $event->getFormattedErrorMessage();

        $this->assertEquals('OpenAI Vision - Rate limit exceeded', $formatted);
    }

    /**
     * Test handled flag prevents duplicate handling
     */
    public function testHandledFlagPreventsDuplicateHandling(): void
    {
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $this->assertFalse($event->isHandled());
        $event->markAsHandled();
        $this->assertTrue($event->isHandled());
        // Calling again doesn't change anything
        $event->markAsHandled();
        $this->assertTrue($event->isHandled());
    }

    /**
     * Test with different exception types
     */
    public function testWithDifferentExceptionTypes(): void
    {
        $runtimeException = new RuntimeException('Runtime error');
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', $runtimeException);

        $this->assertInstanceOf(RuntimeException::class, $event->getException());
        $this->assertEquals('Runtime error', $event->getErrorMessage());
    }
}
