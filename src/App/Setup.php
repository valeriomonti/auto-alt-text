<?php

namespace AATXT\App;

use AATXT\App\Core\Container;
use AATXT\App\Core\PluginBootstrap;
use AATXT\App\Services\AltTextService;

/**
 * Legacy Setup class maintained for backward compatibility.
 *
 * @deprecated 2.6.0 Use PluginBootstrap::init() and dependency injection for new code.
 *             All methods in this class are deprecated and will be removed in v3.0.0.
 *             Use AltTextService via dependency injection instead of Setup::altText().
 */
class Setup
{
    /**
     * Register plugin functionalities.
     *
     * @deprecated 2.6.0 Use PluginBootstrap::init() instead.
     * @return void
     */
    public static function register(): void
    {
        _deprecated_function(__METHOD__, '2.6.0', 'PluginBootstrap::init()');
        PluginBootstrap::init(AATXT_FILE_ABSPATH);
    }

    /**
     * Generate alt text for an attachment.
     *
     * This method is kept for backward compatibility with external code
     * that may call Setup::altText() directly.
     *
     * @deprecated 2.6.0 Use AltTextService::generateForAttachment() via dependency injection instead.
     * @param int $postId The attachment post ID
     * @return string Generated alt text, or empty string if generation fails
     */
    public static function altText(int $postId): string
    {
        _deprecated_function(__METHOD__, '2.6.0', 'AltTextService::generateForAttachment()');

        $container = Container::make();
        $altTextService = $container->get(AltTextService::class);

        return $altTextService->generateForAttachment($postId);
    }
}
