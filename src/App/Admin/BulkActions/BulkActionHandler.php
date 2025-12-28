<?php

namespace AATXT\App\Admin\BulkActions;

use AATXT\Config\Constants;

/**
 * Handler for managing and executing bulk actions in the media library.
 *
 * This class is responsible for:
 * - Registering bulk actions with WordPress
 * - Handling bulk action execution
 * - Displaying admin notices with results
 */
final class BulkActionHandler
{
    /**
     * Registered bulk actions
     *
     * @var array<string, BulkActionInterface>
     */
    private $actions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->actions = [];
    }

    /**
     * Register a bulk action.
     *
     * @param BulkActionInterface $action The bulk action to register
     * @return void
     */
    public function register(BulkActionInterface $action): void
    {
        $this->actions[$action->getName()] = $action;
    }

    /**
     * Get all registered action names and labels for WordPress bulk actions filter.
     *
     * @param array<string, string> $actions Existing WordPress bulk actions
     * @return array<string, string> Modified bulk actions array
     */
    public function getBulkActions(array $actions): array
    {
        foreach ($this->actions as $action) {
            $actions[$action->getName()] = $action->getLabel();
        }

        return $actions;
    }

    /**
     * Handle bulk action execution from media library.
     *
     * Checks for the current action, verifies permissions and nonce,
     * executes the action, and redirects with results.
     *
     * @return void
     */
    public function handleBulkAction(): void
    {
        if (!current_user_can('upload_files')) {
            return;
        }

        $wpListTable = _get_list_table('WP_Media_List_Table');
        $currentAction = $wpListTable->current_action();

        if (!isset($this->actions[$currentAction])) {
            return;
        }

        // Protect against CSRF attacks
        check_admin_referer('bulk-media');

        $mediaIds = isset($_REQUEST['media']) ? (array) $_REQUEST['media'] : [];

        if (empty($mediaIds)) {
            return;
        }

        $action = $this->actions[$currentAction];
        $result = $action->execute($mediaIds);

        // Redirect with result data
        $redirectArgs = [
            'bulk_action_name' => $currentAction,
            'mediaSelected' => $result->getTotal(),
            'mediaUpdated' => $result->getUpdated(),
        ];

        // Preserve Media Library pagination when redirecting after bulk action
        $sendback = wp_get_referer();
        if (empty($sendback)) {
            $sendback = admin_url('upload.php');
        }

        $sendback = remove_query_arg([
            'action',
            'action2',
            'media',
            '_wpnonce',
            '_wp_http_referer',
            'auto_alt_text',
            'mediaSelected',
            'mediaUpdated',
        ], $sendback);

        $sendback = add_query_arg($redirectArgs, $sendback);

        wp_safe_redirect($sendback);
        exit();
    }

    /**
     * Display admin notice after bulk action execution.
     *
     * Shows success, warning, or error notice based on results.
     *
     * @return void
     */
    public function displayAdminNotice(): void
    {
        if (!isset($_REQUEST['bulk_action_name'])) {
            return;
        }

        $actionName = sanitize_text_field($_REQUEST['bulk_action_name']);

        if (!isset($this->actions[$actionName])) {
            return;
        }

        $mediaSelected = isset($_REQUEST['mediaSelected']) ? intval($_REQUEST['mediaSelected']) : 0;
        $mediaUpdated = isset($_REQUEST['mediaUpdated']) ? intval($_REQUEST['mediaUpdated']) : 0;

        $result = new BulkActionResult($mediaSelected, $mediaUpdated);

        $this->renderNotice($result);
    }

    /**
     * Render the appropriate admin notice based on the result.
     *
     * @param BulkActionResult $result The bulk action result
     * @return void
     */
    private function renderNotice(BulkActionResult $result): void
    {
        $errorLogDisclaimer = __('Take a look at the', 'auto-alt-text')
            . ' <a href="' . esc_url(menu_page_url(Constants::AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG, false)) . '">'
            . __('error log', 'auto-alt-text') . '</a>.';

        if ($result->hasNoUpdates()) {
            printf(
                '<div id="message" class="notice notice-error is-dismissible"><p>%s %s</p></div>',
                esc_html__('No Alt Text has been set.', 'auto-alt-text'),
                wp_kses_post($errorLogDisclaimer)
            );
        } elseif ($result->isComplete()) {
            printf(
                '<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %s = number of images processed */
                    esc_html__('The Alt Text has been set for %s media.', 'auto-alt-text'),
                    number_format_i18n($result->getUpdated())
                )
            );
        } else {
            printf(
                '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: 1 = updated count, 2 = selected count, 3 = disclaimer HTML */
                    __('The Alt Text has been set for %1$s of %2$s media. %3$s', 'auto-alt-text'),
                    number_format_i18n($result->getUpdated()),
                    number_format_i18n($result->getTotal()),
                    wp_kses_post($errorLogDisclaimer)
                )
            );
        }
    }
}
