<?php

namespace AATXT\App;

use AATXT\App\Logging\DBLogger;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use AATXT\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
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

        register_activation_hook(AATXT_FILE_ABSPATH, [self::$instance, 'activatePlugin']);
        register_deactivation_hook(AATXT_FILE_ABSPATH, [self::$instance, 'deactivatePlugin']);

        // When attachment is uploaded, create alt text
        add_action('add_attachment', [self::$instance, 'addAltTextOnUpload']);
        // When plugin is loaded, load text domain
        add_action('plugins_loaded', [self::$instance, 'loadTextDomain']);

        add_filter('plugin_action_links_auto-alt-text/auto-alt-text.php', [self::$instance, 'settingsLink']);
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
     *
     * @param int $postId
     * @return void
     */
    public static function addAltTextOnUpload(int $postId): void
    {
        if (!wp_attachment_is_image($postId)) {
            return;
        }

        $altText = '';

        switch (PluginOptions::typology()) {
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE:
                // If Azure is selected as alt text generating typology
                try {
                    $altText = (AltTextGeneratorAi::make(AzureComputerVisionCaptionsResponse::make()))->altText($postId);
                } catch (AzureException $e) {
                    (DBLogger::make())->writeImageLog($postId, "Azure - " . $e->getMessage());
                }
                break;
            case Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI:
                // If OpenAI is selected as alt text generating typology
                try {
                    $altText = (AltTextGeneratorAi::make(OpenAIVision::make()))->altText($postId);
                } catch (OpenAIException $e) {
                    //If vision model fails, try with a fallback model
                    $errorMessage = "OpenAI - " . Constants::AATXT_OPENAI_VISION_MODEL . ' - ' . $e->getMessage();
                    (DBLogger::make())->writeImageLog($postId, $errorMessage);
                    try {
                        $altText = (AltTextGeneratorAi::make(OpenAIChatCompletionResponse::make()))->altText($postId);
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
                return;
        }

        update_post_meta($postId, '_wp_attachment_image_alt', $altText);
    }
}
