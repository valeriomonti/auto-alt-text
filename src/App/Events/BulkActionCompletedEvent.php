<?php

namespace AATXT\App\Events;

/**
 * Event dispatched when a bulk action completes.
 *
 * This event is fired after a bulk operation on multiple images
 * has completed. Listeners can use this for logging, showing
 * admin notices, or triggering cleanup actions.
 *
 * @package AATXT\App\Events
 */
final class BulkActionCompletedEvent
{
    /**
     * Name of the bulk action
     *
     * @var string
     */
    private $actionName;

    /**
     * Total number of items processed
     *
     * @var int
     */
    private $totalItems;

    /**
     * Number of successfully processed items
     *
     * @var int
     */
    private $successCount;

    /**
     * Number of failed items
     *
     * @var int
     */
    private $failureCount;

    /**
     * Timestamp when the event occurred
     *
     * @var int
     */
    private $timestamp;

    /**
     * IDs of successfully processed items
     *
     * @var array<int>
     */
    private $successIds;

    /**
     * IDs of failed items
     *
     * @var array<int>
     */
    private $failedIds;

    /**
     * Constructor
     *
     * @param string $actionName Name of the bulk action
     * @param int $totalItems Total number of items processed
     * @param int $successCount Number of successfully processed items
     * @param array<int> $successIds IDs of successfully processed items
     * @param array<int> $failedIds IDs of failed items
     */
    public function __construct(
        string $actionName,
        int $totalItems,
        int $successCount,
        array $successIds = [],
        array $failedIds = []
    ) {
        $this->actionName = $actionName;
        $this->totalItems = $totalItems;
        $this->successCount = $successCount;
        $this->failureCount = $totalItems - $successCount;
        $this->successIds = $successIds;
        $this->failedIds = $failedIds;
        $this->timestamp = time();
    }

    /**
     * Get the action name.
     *
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * Get the total number of items processed.
     *
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Get the number of successfully processed items.
     *
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get the number of failed items.
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
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
     * Get the IDs of successfully processed items.
     *
     * @return array<int>
     */
    public function getSuccessIds(): array
    {
        return $this->successIds;
    }

    /**
     * Get the IDs of failed items.
     *
     * @return array<int>
     */
    public function getFailedIds(): array
    {
        return $this->failedIds;
    }

    /**
     * Check if all items were processed successfully.
     *
     * @return bool
     */
    public function isFullySuccessful(): bool
    {
        return $this->failureCount === 0;
    }

    /**
     * Check if all items failed.
     *
     * @return bool
     */
    public function isFullyFailed(): bool
    {
        return $this->successCount === 0;
    }

    /**
     * Get the success rate as a percentage.
     *
     * @return float Percentage between 0 and 100
     */
    public function getSuccessRate(): float
    {
        if ($this->totalItems === 0) {
            return 0.0;
        }

        return ($this->successCount / $this->totalItems) * 100;
    }

    /**
     * Get a summary message for display.
     *
     * @return string
     */
    public function getSummaryMessage(): string
    {
        if ($this->isFullySuccessful()) {
            return sprintf(
                'Successfully processed all %d items.',
                $this->totalItems
            );
        }

        if ($this->isFullyFailed()) {
            return sprintf(
                'Failed to process all %d items.',
                $this->totalItems
            );
        }

        return sprintf(
            'Processed %d of %d items successfully (%d failed).',
            $this->successCount,
            $this->totalItems,
            $this->failureCount
        );
    }
}
