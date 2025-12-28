<?php

namespace AATXT\Tests\Unit\Events;

use AATXT\App\Events\BulkActionCompletedEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BulkActionCompletedEvent
 */
class BulkActionCompletedEventTest extends TestCase
{
    /**
     * Test event stores action name correctly
     */
    public function testGetActionNameReturnsCorrectValue(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 8);

        $this->assertEquals('generate_alt_text', $event->getActionName());
    }

    /**
     * Test event stores total items correctly
     */
    public function testGetTotalItemsReturnsCorrectValue(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 100, 95);

        $this->assertEquals(100, $event->getTotalItems());
    }

    /**
     * Test event stores success count correctly
     */
    public function testGetSuccessCountReturnsCorrectValue(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 100, 95);

        $this->assertEquals(95, $event->getSuccessCount());
    }

    /**
     * Test failure count is calculated correctly
     */
    public function testGetFailureCountIsCalculatedCorrectly(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 100, 95);

        $this->assertEquals(5, $event->getFailureCount());
    }

    /**
     * Test event stores timestamp
     */
    public function testGetTimestampReturnsTimestamp(): void
    {
        $before = time();
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 8);
        $after = time();

        $timestamp = $event->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    /**
     * Test event stores success IDs
     */
    public function testGetSuccessIdsReturnsCorrectValue(): void
    {
        $successIds = [1, 2, 3, 4, 5];
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 5, $successIds);

        $this->assertEquals($successIds, $event->getSuccessIds());
    }

    /**
     * Test event stores failed IDs
     */
    public function testGetFailedIdsReturnsCorrectValue(): void
    {
        $failedIds = [6, 7, 8, 9, 10];
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 5, [], $failedIds);

        $this->assertEquals($failedIds, $event->getFailedIds());
    }

    /**
     * Test default IDs arrays are empty
     */
    public function testDefaultIdsArraysAreEmpty(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 8);

        $this->assertEquals([], $event->getSuccessIds());
        $this->assertEquals([], $event->getFailedIds());
    }

    /**
     * Test isFullySuccessful returns true when no failures
     */
    public function testIsFullySuccessfulReturnsTrueWhenNoFailures(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 10);

        $this->assertTrue($event->isFullySuccessful());
    }

    /**
     * Test isFullySuccessful returns false when failures exist
     */
    public function testIsFullySuccessfulReturnsFalseWhenFailuresExist(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 9);

        $this->assertFalse($event->isFullySuccessful());
    }

    /**
     * Test isFullyFailed returns true when no successes
     */
    public function testIsFullyFailedReturnsTrueWhenNoSuccesses(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 0);

        $this->assertTrue($event->isFullyFailed());
    }

    /**
     * Test isFullyFailed returns false when successes exist
     */
    public function testIsFullyFailedReturnsFalseWhenSuccessesExist(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 1);

        $this->assertFalse($event->isFullyFailed());
    }

    /**
     * Test getSuccessRate returns correct percentage
     */
    public function testGetSuccessRateReturnsCorrectPercentage(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 100, 75);

        $this->assertEquals(75.0, $event->getSuccessRate());
    }

    /**
     * Test getSuccessRate returns 100 for full success
     */
    public function testGetSuccessRateReturnsHundredForFullSuccess(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 50, 50);

        $this->assertEquals(100.0, $event->getSuccessRate());
    }

    /**
     * Test getSuccessRate returns 0 for full failure
     */
    public function testGetSuccessRateReturnsZeroForFullFailure(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 50, 0);

        $this->assertEquals(0.0, $event->getSuccessRate());
    }

    /**
     * Test getSuccessRate returns 0 when total is 0
     */
    public function testGetSuccessRateReturnsZeroWhenTotalIsZero(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 0, 0);

        $this->assertEquals(0.0, $event->getSuccessRate());
    }

    /**
     * Test getSummaryMessage for full success
     */
    public function testGetSummaryMessageForFullSuccess(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 10);

        $message = $event->getSummaryMessage();

        $this->assertStringContainsString('10', $message);
        $this->assertStringContainsString('success', strtolower($message));
    }

    /**
     * Test getSummaryMessage for full failure
     */
    public function testGetSummaryMessageForFullFailure(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 0);

        $message = $event->getSummaryMessage();

        $this->assertStringContainsString('10', $message);
        $this->assertStringContainsString('fail', strtolower($message));
    }

    /**
     * Test getSummaryMessage for partial success
     */
    public function testGetSummaryMessageForPartialSuccess(): void
    {
        $event = new BulkActionCompletedEvent('generate_alt_text', 10, 7);

        $message = $event->getSummaryMessage();

        $this->assertStringContainsString('7', $message);
        $this->assertStringContainsString('3', $message);
    }
}
