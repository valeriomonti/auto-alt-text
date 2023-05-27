<?php

namespace ValerioMonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AltTextGeneratorParentPostTitle;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAi;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAttachmentTitle;
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

        add_action('add_attachment', [self::$instance, 'addAltTextOnUpload']);

    }

    public static function addAltTextOnUpload($postId): void
    {
        if (wp_attachment_is_image($postId)) {
            $altText = '';

            switch (PluginOptions::typology()) {
                case Constants::AAT_OPTION_TYPOLOGY_CHOICE_AI:
                    $altText = (new AltTextGeneratorAi())->altText($postId);
                    break;
                case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE:
                    $altText = (new AltTextGeneratorParentPostTitle())->altText($postId);
                    break;
                case Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE:
                    $altText = (new AltTextGeneratorAttachmentTitle())->altText($postId);
                    break;
            }

            update_post_meta($postId, '_wp_attachment_image_alt', $altText);
        }
    }

}
