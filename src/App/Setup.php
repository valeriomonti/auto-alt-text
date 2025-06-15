<?php

namespace AATXT\App;

use AATXT\App\Admin\MediaLibrary;
use AATXT\App\Logging\DBLogger;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use AATXT\App\AIProviders\OpenAI\Fallback;
use AATXT\App\AIProviders\OpenAI\OpenAIVision;
use AATXT\App\Exceptions\Azure\AzureException;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;
use WpOrg\Requests\Exception;


class Setup
{
    private static ?self $instance = null;

    private function __construct()
    {
        //
    }

    /**
     * Register plugin functionalities
     * @return void
     */
    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        //Register plugin options pages
        PluginOptions::register();
        //Register medial library hooks
        MediaLibrary::register();

        register_activation_hook(AATXT_FILE_ABSPATH, [self::$instance, 'activatePlugin']);
        register_deactivation_hook(AATXT_FILE_ABSPATH, [self::$instance, 'deactivatePlugin']);

        // When attachment is uploaded, create alt text
        add_action('add_attachment', [self::$instance, 'addAltText']);
        // When plugin is loaded, load text domain
        add_action('plugins_loaded', [self::$instance, 'loadTextDomain']);
        // Add settings link to the plugin in the plugins listing
        add_filter('plugin_action_links_auto-alt-text/auto-alt-text.php', [self::$instance, 'settingsLink']);
        // Register bulk action for media library
        add_filter('bulk_actions-upload', [self::$instance, 'registerBulkAction']);
        // Handle alt text generation bulk action for media library
        add_action('load-upload.php', [self::$instance, 'handleAltTextBulkAction']);
        // Display a notice after alt text generation bulk action
        add_action('admin_notices', [self::$instance, 'altTextBulkActionAdminNotice']);
    }

    /**
     * Register bulk action for media library
     */
    public static function registerBulkAction(array $actions): array
    {
        $actions['auto_alt_text'] = esc_attr__('Generate Alt Text', 'auto-alt-text');
        return $actions;
    }

    /**
     * Handle alt text generation bulk action for media library
     */
    public static function handleAltTextBulkAction()
    {
        $mediaUpdated = 0;
        $wpListTable = _get_list_table('WP_Media_List_Table');
        $action = $wpListTable->current_action();

        if ($action === 'auto_alt_text') {
            // Recupera l'elenco degli ID dei media selezionati
            $mediaIds = isset($_REQUEST['media']) ? $_REQUEST['media'] : array();

            // Imposta l'alt text per ogni media selezionato
            foreach ($mediaIds as $mediaId) {
                $altText = self::altText($mediaId);
                if (!empty($altText)) {
                    update_post_meta($mediaId, '_wp_attachment_image_alt', $altText);
                    $mediaUpdated++;
                }
            }

            $callBackData = [
                'mediaSelected' => count($mediaIds),
                'mediaUpdated' => $mediaUpdated,
                'auto_alt_text' => '1',
            ];

            // Redirect alla pagina della media library con un messaggio di successo
            $sendback = add_query_arg(
                $callBackData,
                admin_url('upload.php')
            );
            wp_redirect($sendback);
            exit();
        }
    }

    /**
     * Display a notice after alt text generation bulk action
     */
    public static function altTextBulkActionAdminNotice()
    {
        if (isset($_REQUEST['auto_alt_text'])) {
            $mediaSelected = intval($_REQUEST['mediaSelected']);
            $mediaUpdated = intval($_REQUEST['mediaUpdated']);

            $errorLogDisclaimer = __('Take a look at the', 'auto-alt-text') . ' <a href="' . esc_url(menu_page_url(Constants::AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG, false)) . '">' . __('error log', 'auto-alt-text') . '</a>.';

            if ($mediaUpdated === 0) {
                printf('<div id="message" class="notice notice-error is-dismissible"><p>' . esc_attr__('No Alt Text has been set.', 'auto-alt-text') . ' %s</p></div>', $errorLogDisclaimer);
            } elseif ($mediaSelected === $mediaUpdated) {
                /**
                 * translators:
                 * %s = number of images processed
                 */
                printf('<div id="message" class="updated notice is-dismissible"><p>' . esc_attr__('The Alt Text has been set for %s media.', 'auto-alt-text') . '</p></div>', $mediaUpdated);
            } else {
                /**
                 * translators:
                 *   %1$s = number of media items successfully updated
                 *   %2$s = total number of media items selected
                 *   %3$s = HTML disclaimer/link to the error log
                 */
                printf(
                    '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    sprintf(
                    /* translators: 1 = updated count, 2 = selected count, 3 = disclaimer HTML */
                        __( 'The Alt Text has been set for %1$s of %2$s media. %3$s', 'auto-alt-text' ),
                        number_format_i18n( $mediaUpdated ),
                        number_format_i18n( $mediaSelected ),
                        wp_kses_post( $errorLogDisclaimer )
                    )
                );
            }
        }
    }

    /**
     * Create a Logs table on plugin activation
     */
    public static function activatePlugin(): void
    {
        DBLogger::make()->createLogTable();
    }

    /**
     * Drop Logs table on plugin deactivation
     */
    public static function deactivatePlugin(): void
    {
        DBLogger::make()->dropLogTable();
    }

    /**
     * Add link to the options page of the plugin in the plugins listing
     */
    public static function settingsLink(array $links): array
    {
        $url = esc_url(add_query_arg(
            'page',
            'auto-alt-text-options',
            get_admin_url() . 'admin.php'
        ));
        $settingsLink = "<a href='$url'>" . esc_html__('Settings', 'auto-alt-text') . '</a>';
        $links[] = $settingsLink;

        return $links;
    }

    /**
     * Load text domain
     * @return void
     */
    public static function loadTextDomain(): void
    {
        load_plugin_textdomain('auto-alt-text', false, AATXT_LANGUAGES_RELATIVE_PATH);
    }

    /**
     * @param array<string> $allowedMimeTypes
     */
    private static function allowedMimeTypesList(array $allowedMimeTypes): string
    {
        return str_replace('image/', '' , implode(', ', $allowedMimeTypes));
    }

    /**
     * @param int $postId
     * @return string
     */
    public static function altText(int $postId): string
    {
        if (!wp_attachment_is_image($postId)) {
            return '';
        }

        if (PluginOptions::preserveExistingAltText()) {
            $altText = get_post_meta($postId, '_wp_attachment_image_alt', TRUE);

            if (!empty($altText)) {
                return $altText;
            }
        }

        $mimeType = get_post_mime_type($postId);

        switch (PluginOptions::typology()) {
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE:
                if (!in_array($mimeType, Constants::AATXT_AZURE_ALLOWED_MIME_TYPES, true)) {
                    $formats = self::allowedMimeTypesList(Constants::AATXT_AZURE_ALLOWED_MIME_TYPES);
                    (DBLogger::make())->writeImageLog($postId, "You uploaded an unsupported image. Please make sure your image has of one the following formats: $formats");
                    return '';
                }
                try {
                    $altText = (AltTextGeneratorAi::make(AzureComputerVisionCaptionsResponse::make()))->altText($postId);
                } catch (AzureException $e) {
                    (DBLogger::make())->writeImageLog($postId, "Azure - " . $e->getMessage());
                }
                break;
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI:
                if (!in_array($mimeType, Constants::AATXT_OPENAI_ALLOWED_MIME_TYPES, true)) {
                    $formats = self::allowedMimeTypesList(Constants::AATXT_OPENAI_ALLOWED_MIME_TYPES);
                    (DBLogger::make())->writeImageLog($postId, "You uploaded an unsupported image. Please make sure your image has of one the following formats: $formats");
                    return '';
                }
                try {
                    $altText = (AltTextGeneratorAi::make(OpenAIVision::make()))->altText($postId);
                } catch (OpenAIException $e) {
                    //If vision model fails, try with a fallback model
                    $errorMessage = "OpenAI - " . Constants::AATXT_OPENAI_VISION_MODEL . ' - ' . $e->getMessage();
                    (DBLogger::make())->writeImageLog($postId, $errorMessage);
                    try {
                        $altText = (AltTextGeneratorAi::make(Fallback::make()))->altText($postId);
                    } catch (OpenAIException $e) {
                        $errorMessage = "OpenAI - " . $e->getMessage();
                        (DBLogger::make())->writeImageLog($postId, $errorMessage);
                    }
                }
                break;
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE:
                // If Article title is selected as alt text generating typology
                $parentId = wp_get_post_parent_id($postId);
                if ($parentId) {
                    $altText = (AltTextGeneratorParentPostTitle::make())->altText($postId);
                } else {
                    //If media has not a parent use the Attachment Title method as fallback
                    $altText = (AltTextGeneratorAttachmentTitle::make())->altText($postId);
                }
                break;
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE:
                // If Attachment title is selected as alt text generating typology
                $altText = (AltTextGeneratorAttachmentTitle::make())->altText($postId);
                break;
            default:
                return '';
        }

        return $altText ?? '';
    }

    /**
     * @param int $postId
     * @return void
     */
    public static function addAltText(int $postId): void
    {
        $altText = self::altText($postId);
        if (empty($altText)) {
            return;
        }

        update_post_meta($postId, '_wp_attachment_image_alt', $altText);
    }
}
