<?php

namespace AATXT\App\Admin\BulkActions;

/**
 * Value Object representing the result of a bulk action.
 *
 * Immutable object that holds the counts of total items processed
 * and successfully updated items.
 */
final class BulkActionResult
{
    /**
     * Total number of items that were processed
     *
     * @var int
     */
    private $total;

    /**
     * Number of items successfully updated
     *
     * @var int
     */
    private $updated;

    /**
     * Constructor
     *
     * @param int $total Total items processed
     * @param int $updated Items successfully updated
     */
    public function __construct(int $total, int $updated)
    {
        $this->total = $total;
        $this->updated = $updated;
    }

    /**
     * Get the total number of items processed.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Get the number of items successfully updated.
     *
     * @return int
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Check if all items were successfully updated.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->total === $this->updated;
    }

    /**
     * Check if no items were updated.
     *
     * @return bool
     */
    public function hasNoUpdates(): bool
    {
        return $this->updated === 0;
    }

    /**
     * Check if some but not all items were updated.
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return $this->updated > 0 && $this->updated < $this->total;
    }
}
