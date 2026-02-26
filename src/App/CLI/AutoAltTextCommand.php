<?php

declare(strict_types=1);

namespace AATXT\App\CLI;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\Services\AltTextService;
use AATXT\Config\Constants;

final class AutoAltTextCommand extends \WP_CLI_Command
{
    private AltTextService $altTextService;

    public function __construct(AltTextService $altTextService)
    {
        $this->altTextService = $altTextService;
    }

    /**
     * Generate and save image alt text for attachments.
     *
     * ## OPTIONS
     *
     * [--ids=<ids>]
     * : Comma-separated list of attachment IDs.
     *
     * [--all]
     * : Process attachments using a query (images only). Use with --limit/--offset to batch.
     *
     * [--limit=<n>]
     * : Max number of attachments to process when using --all.
     *
     * [--offset=<n>]
     * : Offset when using --all.
     *
     * [--dry-run]
     * : Do not update metadata; only show what would happen.
     *
     * [--force]
     * : Overwrite existing alt text even if the plugin setting "Keep existing alt text" is enabled.
     *
     * ## EXAMPLES
     *
     *     wp auto-alt-text generate --ids=123,456
     *     wp auto-alt-text generate --all --limit=200 --offset=0
     *     wp auto-alt-text generate --all --limit=200 --offset=200
     *
     * @subcommand generate
     */
    public function generate(array $args, array $assocArgs): void
    {
        $idsArg = isset($assocArgs['ids']) ? (string) $assocArgs['ids'] : '';
        $all = isset($assocArgs['all']);
        $dryRun = isset($assocArgs['dry-run']);
        $force = isset($assocArgs['force']);

        $limit = isset($assocArgs['limit']) ? (int) $assocArgs['limit'] : 100;
        $offset = isset($assocArgs['offset']) ? (int) $assocArgs['offset'] : 0;

        if ($limit < 1) {
            \WP_CLI::error('Invalid --limit. Must be >= 1.');
        }

        if ($offset < 0) {
            \WP_CLI::error('Invalid --offset. Must be >= 0.');
        }

        if ($idsArg === '' && ! $all) {
            \WP_CLI::error('Specify either --ids=<csv> or --all');
        }

        $ids = [];
        if ($idsArg !== '') {
            $ids = array_values(array_filter(array_map('absint', preg_split('/\s*,\s*/', $idsArg))));
            $ids = array_values(array_unique($ids));
        }

        if ($idsArg === '' && $all) {
            $query = new \WP_Query([
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'fields' => 'ids',
                'posts_per_page' => $limit,
                'offset' => $offset,
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);

            $ids = array_map('intval', $query->posts);
        }

        if ($ids === []) {
            \WP_CLI::success('No attachments found.');
            return;
        }

        $preserveOptionKey = Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT;
        $originalPreserve = get_option($preserveOptionKey);

        if ($force) {
            update_option($preserveOptionKey, 0);
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $progress = \WP_CLI\Utils\make_progress_bar('Generating alt text', count($ids));

        try {
            foreach ($ids as $attachmentId) {
                $attachmentId = (int) $attachmentId;

                if ($attachmentId < 1) {
                    $failed++;
                    $processed++;
                    $progress->tick();
                    continue;
                }

                if (! wp_attachment_is_image($attachmentId)) {
                    $skipped++;
                    $processed++;
                    $progress->tick();
                    continue;
                }

                if (! $force && PluginOptions::preserveExistingAltText()) {
                    $existingAlt = (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
                    if ($existingAlt !== '') {
                        $skipped++;
                        $processed++;
                        $progress->tick();
                        continue;
                    }
                }

                $altText = $this->altTextService->generateForAttachment($attachmentId);

                if ($altText === '') {
                    $failed++;
                    $processed++;
                    $progress->tick();
                    continue;
                }

                if (! $dryRun) {
                    update_post_meta($attachmentId, '_wp_attachment_image_alt', $altText);
                }

                $updated++;
                $processed++;
                $progress->tick();
            }
        } finally {
            $progress->finish();
            if ($force) {
                update_option($preserveOptionKey, $originalPreserve);
            }
        }

        \WP_CLI::log('Processed: ' . $processed);
        \WP_CLI::log('Updated:   ' . $updated . ($dryRun ? ' (dry-run)' : ''));
        \WP_CLI::log('Skipped:   ' . $skipped);
        \WP_CLI::log('Failed:    ' . $failed);

        \WP_CLI::success('Done.');
    }
}
