<?php

namespace ValerioMonti\AutoAltText\App;

use OpenAI\Exceptions\ErrorException;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAITextCompletionResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAIVision;
use ValerioMonti\AutoAltText\App\Exceptions\Azure\AzureException;
use ValerioMonti\AutoAltText\App\Exceptions\OpenAI\OpenAIException;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
use ValerioMonti\AutoAltText\App\Logging\LogCleaner;
use ValerioMonti\AutoAltText\App\Utilities\Encryption;
use ValerioMonti\AutoAltText\Config\Constants;
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
        //Enable automatic clean for the plugin error log
        LogCleaner::register();

        // When attachment is uploaded, create alt text
        add_action('add_attachment', [self::$instance, 'addAltTextOnUpload']);
        // When plugin is loaded, load text domain
        add_action('plugins_loaded', [self::$instance, 'loadTextDomain']);

        add_filter('plugin_action_links_auto-alt-text/auto-alt-text.php', [self::$instance, 'settingsLink']);
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
        $settingsLink = "<a href='$url'>" . __('Settings', 'auto-alt-text') . '</a>';
        $links[] = $settingsLink;

        return $links;
    }

    /**
     * Load text domain
     * @return void
     */
    public static function loadTextDomain(): void
    {
        load_plugin_textdomain('auto-alt-text', false, AUTO_ALT_TEXT_LANGUAGES_RELATIVE_PATH);
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
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE:
                // If Azure is selected as alt text generating typology
                try {
                    $altText = (AltTextGeneratorAi::make(AzureComputerVisionCaptionsResponse::make()))->altText($postId);
                } catch (AzureException $e) {
                    (FileLogger::make(Encryption::make()))->writeImageLog($postId, "Azure - " . $e->getMessage());
                }
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI:
                // If OpenAI is selected as alt text generating typology
                try {
                    $altText = (AltTextGeneratorAi::make(OpenAIVision::make()))->altText($postId);
                } catch (OpenAIException $e) {
                    //If vision model fails, try with a fallback model
                    $errorMessage = "OpenAI - " . Constants::AAT_OPENAI_VISION_MODEL . ' - ' . $e->getMessage();
                    (FileLogger::make(Encryption::make()))->writeImageLog($postId, $errorMessage);
                    try {
                        $altText = (AltTextGeneratorAi::make(OpenAIChatCompletionResponse::make()))->altText($postId);
                    } catch (OpenAIException $e) {
                        $errorMessage = "OpenAI - " . $e->getMessage();
                        (FileLogger::make(Encryption::make()))->writeImageLog($postId, $errorMessage);
                    }
                }
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE:
                // If Article title is selected as alt text generating typology
                $parentId = wp_get_post_parent_id($postId);
                if ($parentId) {
                    $altText = (AltTextGeneratorParentPostTitle::make())->altText($postId);
                } else {
                    //If media has not a parent use the Attachment Title method as fallback
                    $altText = (AltTextGeneratorAttachmentTitle::make())->altText($postId);
                }
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE:
                // If Attachment title is selected as alt text generating typology
                $altText = (AltTextGeneratorAttachmentTitle::make())->altText($postId);
                break;
            default:
                return;
        }

        update_post_meta($postId, '_wp_attachment_image_alt', $altText);
    }
}
