<?php

namespace ValerioMonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AltTextGeneratorParentPostTitle;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAi;
use ValerioMonti\AutoAltText\App\AltTextGeneratorAttachmentTitle;


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

        $api_key = get_option('api_key');

        add_action('add_attachment', [self::$instance, 'addAltTextOnUpload']);

//        add_action('init', function(){
//            self::addAltTextOnUpload(4);
//        });
    }

    public static function addAltTextOnUpload($postId): void
    {
        if (wp_attachment_is_image($postId)) {
            $altText = '';
            switch (PluginOptions::typology()) {
                case 'gpt4':
                    $altText = (new AltTextGeneratorAi())->altText($postId);
                    break;
                case 'article-title':
                    $altText = (new AltTextGeneratorParentPostTitle())->altText($postId);
                    break;
                case 'file-name':
                    $altText = (new AltTextGeneratorAttachmentTitle())->altText($postId);
                    break;
            }

            update_post_meta($postId, '_wp_attachment_image_alt', $altText);
        }
    }

}

//
//function aggiungi_alt_text_all_imagini($post_id) {
//    if (wp_attachment_is_image($post_id)) {
//        $immagine = get_post($post_id);
//        $alt_text = 'ciao';
//        update_post_meta( $post_id, '_wp_attachment_image_alt', $alt_text);
//    }
//}
//add_action('add_attachment', 'aggiungi_alt_text_all_imagini');
