<?php

namespace AATXT\App\Admin\BulkActions;

/**
 * Interface for bulk actions in the media library.
 *
 * Defines the contract for implementing bulk actions that can be
 * executed on multiple media items at once.
 */
interface BulkActionInterface
{
    /**
     * Execute the bulk action on the given items.
     *
     * @param array<int> $itemIds Array of media item IDs to process
     * @return BulkActionResult Result containing total and updated counts
     */
    public function execute(array $itemIds): BulkActionResult;

    /**
     * Get the unique name/identifier of this bulk action.
     *
     * @return string The action name (e.g., 'auto_alt_text')
     */
    public function getName(): string;

    /**
     * Get the display label for this bulk action.
     *
     * @return string The translated label shown in the UI
     */
    public function getLabel(): string;
}
