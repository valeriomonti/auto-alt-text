<?php

namespace AATXT\App\Events\Listeners;

use AATXT\App\Domain\Entities\ErrorLog;
use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;

/**
 * Listener that logs alt text generation errors to the database.
 *
 * This listener responds to AltTextGenerationFailedEvent by saving
 * the error details to the error log repository for later review.
 *
 * @package AATXT\App\Events\Listeners
 */
final class LogErrorListener
{
    /**
     * Error log repository
     *
     * @var ErrorLogRepositoryInterface
     */
    private $errorLogRepository;

    /**
     * Constructor
     *
     * @param ErrorLogRepositoryInterface $errorLogRepository Repository for persisting error logs
     */
    public function __construct(ErrorLogRepositoryInterface $errorLogRepository)
    {
        $this->errorLogRepository = $errorLogRepository;
    }

    /**
     * Handle the alt text generation failed event.
     *
     * Logs the error to the database and marks the event as handled.
     *
     * @param AltTextGenerationFailedEvent $event The event to handle
     * @return void
     */
    public function __invoke(AltTextGenerationFailedEvent $event): void
    {
        $this->handle($event);
    }

    /**
     * Handle the alt text generation failed event.
     *
     * @param AltTextGenerationFailedEvent $event The event to handle
     * @return void
     */
    public function handle(AltTextGenerationFailedEvent $event): void
    {
        // Don't log if already handled by another listener
        if ($event->isHandled()) {
            return;
        }

        $errorLog = new ErrorLog(
            $event->getImageId(),
            $event->getFormattedErrorMessage()
        );

        $this->errorLogRepository->save($errorLog);

        $event->markAsHandled();
    }

    /**
     * Get the events this listener responds to.
     *
     * @return array<string> Array of event class names
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AltTextGenerationFailedEvent::class,
        ];
    }
}
