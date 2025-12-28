<?php

namespace AATXT\App\Admin\BulkActions;

use AATXT\App\Services\AltTextService;

/**
 * Bulk action for generating alt text for multiple media items.
 *
 * This class encapsulates the logic for processing multiple images
 * and generating alt text for each one using the AltTextService.
 */
final class GenerateAltTextBulkAction implements BulkActionInterface
{
    /**
     * Action identifier
     */
    private const ACTION_NAME = 'auto_alt_text';

    /**
     * Alt text generation service
     *
     * @var AltTextService
     */
    private $altTextService;

    /**
     * Constructor
     *
     * @param AltTextService $altTextService Service for generating alt text
     */
    public function __construct(AltTextService $altTextService)
    {
        $this->altTextService = $altTextService;
    }

    /**
     * Execute the bulk action on the given media items.
     *
     * Generates alt text for each media item and updates the post meta.
     *
     * @param array<int> $itemIds Array of media item IDs to process
     * @return BulkActionResult Result containing processing statistics
     */
    public function execute(array $itemIds): BulkActionResult
    {
        $updated = 0;

        foreach ($itemIds as $mediaId) {
            $mediaId = (int) $mediaId;
            $altText = $this->altTextService->generateForAttachment($mediaId);

            if (!empty($altText)) {
                update_post_meta($mediaId, '_wp_attachment_image_alt', $altText);
                $updated++;
            }
        }

        return new BulkActionResult(count($itemIds), $updated);
    }

    /**
     * Get the unique name/identifier of this bulk action.
     *
     * @return string The action name
     */
    public function getName(): string
    {
        return self::ACTION_NAME;
    }

    /**
     * Get the display label for this bulk action.
     *
     * @return string The translated label
     */
    public function getLabel(): string
    {
        return esc_attr__('Generate Alt Text', 'auto-alt-text');
    }
}
