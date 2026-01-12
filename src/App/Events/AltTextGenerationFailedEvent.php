<?php

namespace AATXT\App\Events;

use Throwable;

/**
 * Event dispatched when alt text generation fails.
 *
 * This event is fired when an AI provider fails to generate alt text
 * for an image. Listeners can use this for error logging, notifications,
 * or triggering fallback mechanisms.
 *
 * @package AATXT\App\Events
 */
final class AltTextGenerationFailedEvent
{
    /**
     * WordPress attachment ID
     *
     * @var int
     */
    private $imageId;

    /**
     * Provider name that failed
     *
     * @var string
     */
    private $provider;

    /**
     * The exception that caused the failure
     *
     * @var Throwable
     */
    private $exception;

    /**
     * Timestamp when the event occurred
     *
     * @var int
     */
    private $timestamp;

    /**
     * Whether the error has been handled/logged
     *
     * @var bool
     */
    private $handled = false;

    /**
     * Constructor
     *
     * @param int $imageId WordPress attachment ID
     * @param string $provider Provider name (e.g., 'OpenAI Vision', 'Anthropic')
     * @param Throwable $exception The exception that caused the failure
     */
    public function __construct(int $imageId, string $provider, Throwable $exception)
    {
        $this->imageId = $imageId;
        $this->provider = $provider;
        $this->exception = $exception;
        $this->timestamp = time();
    }

    /**
     * Get the image ID.
     *
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the exception that caused the failure.
     *
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Get the error message.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Get the timestamp when the event occurred.
     *
     * @return int Unix timestamp
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Mark the error as handled.
     *
     * This can be used by listeners to indicate they have
     * processed/logged the error to prevent duplicate handling.
     *
     * @return void
     */
    public function markAsHandled(): void
    {
        $this->handled = true;
    }

    /**
     * Check if the error has been handled.
     *
     * @return bool
     */
    public function isHandled(): bool
    {
        return $this->handled;
    }

    /**
     * Get a formatted error message for logging.
     *
     * @return string
     */
    public function getFormattedErrorMessage(): string
    {
        return sprintf(
            '%s - %s',
            $this->provider,
            $this->exception->getMessage()
        );
    }
}
