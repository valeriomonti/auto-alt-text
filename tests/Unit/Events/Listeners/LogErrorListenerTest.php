<?php

namespace AATXT\Tests\Unit\Events\Listeners;

use AATXT\App\Domain\Entities\ErrorLog;
use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Events\Listeners\LogErrorListener;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LogErrorListener
 */
class LogErrorListenerTest extends TestCase
{
    /**
     * Test listener logs error to repository
     */
    public function testHandleLogsErrorToRepository(): void
    {
        $repository = $this->createMock(ErrorLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ErrorLog $log) {
                return $log->getImageId() === 123
                    && strpos($log->getErrorMessage(), 'OpenAI') !== false
                    && strpos($log->getErrorMessage(), 'API Error') !== false;
            }));

        $listener = new LogErrorListener($repository);
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('API Error'));

        $listener->handle($event);
    }

    /**
     * Test listener marks event as handled
     */
    public function testHandleMarksEventAsHandled(): void
    {
        $repository = $this->createMock(ErrorLogRepositoryInterface::class);

        $listener = new LogErrorListener($repository);
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $this->assertFalse($event->isHandled());

        $listener->handle($event);

        $this->assertTrue($event->isHandled());
    }

    /**
     * Test listener skips already handled events
     */
    public function testHandleSkipsAlreadyHandledEvents(): void
    {
        $repository = $this->createMock(ErrorLogRepositoryInterface::class);
        $repository->expects($this->never())
            ->method('save');

        $listener = new LogErrorListener($repository);
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));
        $event->markAsHandled();

        $listener->handle($event);
    }

    /**
     * Test __invoke delegates to handle
     */
    public function testInvokeDelegatesToHandle(): void
    {
        $repository = $this->createMock(ErrorLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save');

        $listener = new LogErrorListener($repository);
        $event = new AltTextGenerationFailedEvent(123, 'OpenAI', new Exception('Error'));

        $listener($event);

        $this->assertTrue($event->isHandled());
    }

    /**
     * Test getSubscribedEvents returns correct events
     */
    public function testGetSubscribedEventsReturnsCorrectEvents(): void
    {
        $events = LogErrorListener::getSubscribedEvents();

        $this->assertContains(AltTextGenerationFailedEvent::class, $events);
    }

    /**
     * Test error message format in log
     */
    public function testErrorMessageFormatInLog(): void
    {
        $repository = $this->createMock(ErrorLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ErrorLog $log) {
                // Should use formatted error message: "Provider - error message"
                $message = $log->getErrorMessage();
                return $message === 'Anthropic Claude - Rate limit exceeded';
            }));

        $listener = new LogErrorListener($repository);
        $event = new AltTextGenerationFailedEvent(456, 'Anthropic Claude', new Exception('Rate limit exceeded'));

        $listener->handle($event);
    }
}
