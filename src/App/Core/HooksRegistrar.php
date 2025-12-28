<?php

namespace AATXT\App\Core;

use AATXT\App\Admin\BulkActions\BulkActionHandler;
use AATXT\App\Admin\MediaLibrary;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\Services\AltTextService;

/**
 * Registers all WordPress hooks for the plugin.
 *
 * This class follows the Single Responsibility Principle by handling
 * only the registration of WordPress actions and filters.
 */
final class HooksRegistrar
{
    /**
     * Alt text generation service
     *
     * @var AltTextService
     */
    private $altTextService;

    /**
     * Bulk action handler
     *
     * @var BulkActionHandler
     */
    private $bulkActionHandler;

    /**
     * Plugin lifecycle handler
     *
     * @var PluginLifecycle
     */
    private $lifecycle;

    /**
     * Media library handler
     *
     * @var MediaLibrary
     */
    private $mediaLibrary;

    /**
     * Path to the main plugin file
     *
     * @var string
     */
    private $pluginFile;

    /**
     * Constructor
     *
     * @param AltTextService $altTextService Service for generating alt text
     * @param BulkActionHandler $bulkActionHandler Handler for bulk actions
     * @param PluginLifecycle $lifecycle Plugin lifecycle handler
     * @param MediaLibrary $mediaLibrary Media library handler
     * @param string $pluginFile Path to the main plugin file
     */
    public function __construct(
        AltTextService $altTextService,
        BulkActionHandler $bulkActionHandler,
        PluginLifecycle $lifecycle,
        MediaLibrary $mediaLibrary,
        string $pluginFile
    ) {
        $this->altTextService = $altTextService;
        $this->bulkActionHandler = $bulkActionHandler;
        $this->lifecycle = $lifecycle;
        $this->mediaLibrary = $mediaLibrary;
        $this->pluginFile = $pluginFile;
    }

    /**
     * Register all WordPress hooks.
     *
     * @return void
     */
    public function register(): void
    {
        // Register admin pages
        PluginOptions::register();
        $this->mediaLibrary->register();

        // Plugin lifecycle hooks
        register_activation_hook($this->pluginFile, [$this->lifecycle, 'activate']);
        register_deactivation_hook($this->pluginFile, [$this->lifecycle, 'deactivate']);

        // Auto-generate alt text on image upload
        add_action('add_attachment', [$this, 'addAltText']);

        // Load translations
        add_action('plugins_loaded', [$this, 'loadTextDomain']);

        // Add settings link in plugins page
        add_filter(
            'plugin_action_links_auto-alt-text/auto-alt-text.php',
            [$this, 'addSettingsLink']
        );

        // Bulk actions
        add_filter('bulk_actions-upload', [$this->bulkActionHandler, 'getBulkActions']);
        add_action('load-upload.php', [$this->bulkActionHandler, 'handleBulkAction']);
        add_action('admin_notices', [$this->bulkActionHandler, 'displayAdminNotice']);
    }

    /**
     * Generate and save alt text for a newly uploaded attachment.
     *
     * @param int $attachmentId The attachment post ID
     * @return void
     */
    public function addAltText(int $attachmentId): void
    {
        $altText = $this->altTextService->generateForAttachment($attachmentId);

        if (!empty($altText)) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', $altText);
        }
    }

    /**
     * Load plugin text domain for translations.
     *
     * @return void
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain('auto-alt-text', false, AATXT_LANGUAGES_RELATIVE_PATH);
    }

    /**
     * Add settings link to the plugin actions in the plugins list.
     *
     * @param array<string> $links Existing plugin action links
     * @return array<string> Modified plugin action links
     */
    public function addSettingsLink(array $links): array
    {
        $url = esc_url(add_query_arg(
            'page',
            'auto-alt-text-options',
            get_admin_url() . 'admin.php'
        ));

        $settingsLink = '<a href="' . $url . '">' . esc_html__('Settings', 'auto-alt-text') . '</a>';
        $links[] = $settingsLink;

        return $links;
    }
}
