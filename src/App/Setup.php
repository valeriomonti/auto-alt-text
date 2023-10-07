<?php

namespace ValerioMonti\AutoAltText\App;

use OpenAI\Exceptions\ErrorException;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureTranslator;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAITextCompletionResponse;
use ValerioMonti\AutoAltText\App\AltTextGeneratorParentPostTitle;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAi;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAttachmentTitle;
use ValerioMonti\AutoAltText\App\Exceptions\AzureComputerVisionException;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
use ValerioMonti\AutoAltText\Config\Constants;


class Setup
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        PluginOptions::register();

        // When attachment is uploaded, create alt text
        add_action('add_attachment', [self::$instance, 'addAltTextOnUpload']);
        // When plugin is loaded, load text domain
        add_action('plugins_loaded', [self::$instance, 'loadTextDomain']);
    }

    /**
     * Load text domain
     */
    public static function loadTextDomain(): void
    {
        load_plugin_textdomain( 'auto-alt-text', false, AUTO_ALT_TEXT_LANGUAGES_RELATIVE_PATH );
    }

    /**
     * @param $postId
     * @return void
     */
    public static function addAltTextOnUpload($postId): void
    {
        if (!wp_attachment_is_image($postId)) {
            return;
        }

        $altText = '';
        switch (PluginOptions::typology()) {
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE:
                try {
                    $altText = (AltTextGeneratorAi::make(AzureComputerVisionCaptionsResponse::make()))->altText($postId);
                } catch (AzureComputerVisionException $e) {
                    (FileLogger::make())->writeImageLog($postId, "Azure - " . $e->getMessage());
                }
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI:
                $model = PluginOptions::model();
                try {
                    if (Constants::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION == Constants::AAT_OPENAI_MODELS[$model]) {
                        $altText = (AltTextGeneratorAi::make(OpenAITextCompletionResponse::make()))->altText($postId);
                    } else {
                        $altText = (AltTextGeneratorAi::make(OpenAIChatCompletionResponse::make()))->altText($postId);
                    }
                } catch(ErrorException $e) {
                    $errorMessage = "OpenAI - " . $e->getErrorType() . " - " . $e->getMessage();
                    (FileLogger::make())->writeImageLog($postId, $errorMessage);
                }
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE:
                $altText = (AltTextGeneratorParentPostTitle::make())->altText($postId);
                break;
            case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE:
                $altText = (AltTextGeneratorAttachmentTitle::make())->altText($postId);
                break;
        }

        update_post_meta($postId, '_wp_attachment_image_alt', $altText);
    }

}
