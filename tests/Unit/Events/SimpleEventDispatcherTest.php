<?php

namespace AATXT\Tests\Unit\Events;

use AATXT\App\Events\SimpleEventDispatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for SimpleEventDispatcher
 */
class SimpleEventDispatcherTest extends TestCase
{
    /**
     * @var SimpleEventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new SimpleEventDispatcher();
    }

    /**
     * Test listen registers a listener
     */
    public function testListenRegistersListener(): void
    {
        $listener = function () {};

        $this->dispatcher->listen(stdClass::class, $listener);

        $this->assertTrue($this->dispatcher->hasListeners(stdClass::class));
    }

    /**
     * Test hasListeners returns false when no listeners
     */
    public function testHasListenersReturnsFalseWhenNoListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners(stdClass::class));
    }

    /**
     * Test getListeners returns empty array when no listeners
     */
    public function testGetListenersReturnsEmptyArrayWhenNoListeners(): void
    {
        $listeners = $this->dispatcher->getListeners(stdClass::class);

        $this->assertEquals([], $listeners);
    }

    /**
     * Test getListeners returns registered listeners
     */
    public function testGetListenersReturnsRegisteredListeners(): void
    {
        $listener1 = function () {};
        $listener2 = function () {};

        $this->dispatcher->listen(stdClass::class, $listener1);
        $this->dispatcher->listen(stdClass::class, $listener2);

        $listeners = $this->dispatcher->getListeners(stdClass::class);

        $this->assertCount(2, $listeners);
        $this->assertSame($listener1, $listeners[0]);
        $this->assertSame($listener2, $listeners[1]);
    }

    /**
     * Test dispatch calls all registered listeners
     */
    public function testDispatchCallsAllListeners(): void
    {
        $callCount = 0;

        $this->dispatcher->listen(stdClass::class, function ($event) use (&$callCount) {
            $callCount++;
        });

        $this->dispatcher->listen(stdClass::class, function ($event) use (&$callCount) {
            $callCount++;
        });

        $this->dispatcher->dispatch(new stdClass());

        $this->assertEquals(2, $callCount);
    }

    /**
     * Test dispatch passes event to listeners
     */
    public function testDispatchPassesEventToListeners(): void
    {
        $receivedEvent = null;
        $event = new stdClass();
        $event->data = 'test';

        $this->dispatcher->listen(stdClass::class, function ($e) use (&$receivedEvent) {
            $receivedEvent = $e;
        });

        $this->dispatcher->dispatch($event);

        $this->assertSame($event, $receivedEvent);
    }

    /**
     * Test dispatch returns the event
     */
    public function testDispatchReturnsEvent(): void
    {
        $event = new stdClass();

        $result = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatch calls listeners in registration order
     */
    public function testDispatchCallsListenersInOrder(): void
    {
        $order = [];

        $this->dispatcher->listen(stdClass::class, function () use (&$order) {
            $order[] = 'first';
        });

        $this->dispatcher->listen(stdClass::class, function () use (&$order) {
            $order[] = 'second';
        });

        $this->dispatcher->listen(stdClass::class, function () use (&$order) {
            $order[] = 'third';
        });

        $this->dispatcher->dispatch(new stdClass());

        $this->assertEquals(['first', 'second', 'third'], $order);
    }

    /**
     * Test removeListener removes listener
     */
    public function testRemoveListenerRemovesListener(): void
    {
        $listener = function () {};

        $this->dispatcher->listen(stdClass::class, $listener);
        $this->assertTrue($this->dispatcher->hasListeners(stdClass::class));

        $result = $this->dispatcher->removeListener(stdClass::class, $listener);

        $this->assertTrue($result);
        $this->assertFalse($this->dispatcher->hasListeners(stdClass::class));
    }

    /**
     * Test removeListener returns false when listener not found
     */
    public function testRemoveListenerReturnsFalseWhenNotFound(): void
    {
        $listener = function () {};

        $result = $this->dispatcher->removeListener(stdClass::class, $listener);

        $this->assertFalse($result);
    }

    /**
     * Test getListenerCount returns correct count
     */
    public function testGetListenerCountReturnsCorrectCount(): void
    {
        $this->assertEquals(0, $this->dispatcher->getListenerCount(stdClass::class));

        $this->dispatcher->listen(stdClass::class, function () {});
        $this->assertEquals(1, $this->dispatcher->getListenerCount(stdClass::class));

        $this->dispatcher->listen(stdClass::class, function () {});
        $this->assertEquals(2, $this->dispatcher->getListenerCount(stdClass::class));
    }

    /**
     * Test clearListeners removes all listeners for event
     */
    public function testClearListenersRemovesAllForEvent(): void
    {
        $this->dispatcher->listen(stdClass::class, function () {});
        $this->dispatcher->listen(stdClass::class, function () {});

        $removedCount = $this->dispatcher->clearListeners(stdClass::class);

        $this->assertEquals(2, $removedCount);
        $this->assertFalse($this->dispatcher->hasListeners(stdClass::class));
    }

    /**
     * Test clearListeners returns 0 when no listeners
     */
    public function testClearListenersReturnsZeroWhenNoListeners(): void
    {
        $removedCount = $this->dispatcher->clearListeners(stdClass::class);

        $this->assertEquals(0, $removedCount);
    }

    /**
     * Test clearAllListeners removes all listeners
     */
    public function testClearAllListenersRemovesAll(): void
    {
        $this->dispatcher->listen(stdClass::class, function () {});
        $this->dispatcher->listen('SomeOtherClass', function () {});

        $this->dispatcher->clearAllListeners();

        $this->assertFalse($this->dispatcher->hasListeners(stdClass::class));
        $this->assertFalse($this->dispatcher->hasListeners('SomeOtherClass'));
    }

    /**
     * Test dispatch with no listeners does not fail
     */
    public function testDispatchWithNoListenersDoesNotFail(): void
    {
        $event = new stdClass();

        $result = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }
}
