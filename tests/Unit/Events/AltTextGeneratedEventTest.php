<?php

namespace AATXT\Tests\Unit\Events;

use AATXT\App\Events\AltTextGeneratedEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AltTextGeneratedEvent
 */
class AltTextGeneratedEventTest extends TestCase
{
    /**
     * Test event stores image ID correctly
     */
    public function testGetImageIdReturnsCorrectValue(): void
    {
        $event = new AltTextGeneratedEvent(123, 'Alt text', 'OpenAI');

        $this->assertEquals(123, $event->getImageId());
    }

    /**
     * Test event stores alt text correctly
     */
    public function testGetAltTextReturnsCorrectValue(): void
    {
        $event = new AltTextGeneratedEvent(123, 'A beautiful sunset', 'OpenAI');

        $this->assertEquals('A beautiful sunset', $event->getAltText());
    }

    /**
     * Test event stores provider correctly
     */
    public function testGetProviderReturnsCorrectValue(): void
    {
        $event = new AltTextGeneratedEvent(123, 'Alt text', 'Anthropic Claude');

        $this->assertEquals('Anthropic Claude', $event->getProvider());
    }

    /**
     * Test event stores timestamp
     */
    public function testGetTimestampReturnsTimestamp(): void
    {
        $before = time();
        $event = new AltTextGeneratedEvent(123, 'Alt text', 'OpenAI');
        $after = time();

        $timestamp = $event->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    /**
     * Test getAltTextLength returns correct length
     */
    public function testGetAltTextLengthReturnsCorrectLength(): void
    {
        $altText = 'Hello World';
        $event = new AltTextGeneratedEvent(123, $altText, 'OpenAI');

        $this->assertEquals(11, $event->getAltTextLength());
    }

    /**
     * Test getAltTextLength with multibyte characters
     */
    public function testGetAltTextLengthWithMultibyteCharacters(): void
    {
        $altText = 'Ciao mondo!'; // 11 characters
        $event = new AltTextGeneratedEvent(123, $altText, 'OpenAI');

        $this->assertEquals(11, $event->getAltTextLength());
    }

    /**
     * Test getAltTextLength with empty string
     */
    public function testGetAltTextLengthWithEmptyString(): void
    {
        $event = new AltTextGeneratedEvent(123, '', 'OpenAI');

        $this->assertEquals(0, $event->getAltTextLength());
    }

    /**
     * Test event with different providers
     */
    public function testEventWithDifferentProviders(): void
    {
        $openAI = new AltTextGeneratedEvent(1, 'Alt 1', 'OpenAI Vision');
        $anthropic = new AltTextGeneratedEvent(2, 'Alt 2', 'Anthropic Claude');
        $azure = new AltTextGeneratedEvent(3, 'Alt 3', 'Azure Computer Vision');

        $this->assertEquals('OpenAI Vision', $openAI->getProvider());
        $this->assertEquals('Anthropic Claude', $anthropic->getProvider());
        $this->assertEquals('Azure Computer Vision', $azure->getProvider());
    }
}
