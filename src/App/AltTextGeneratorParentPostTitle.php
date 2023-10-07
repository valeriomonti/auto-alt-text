<?php
namespace Valeriomonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorParentPostTitle implements AltTextGeneratorInterface
{
    private function __construct()
    {

    }

    public static function make(): AltTextGeneratorParentPostTitle
    {
        return new self();
    }

    public function altText(int $imageId): string
    {
        $parentPost = get_post_parent($imageId);

        if (empty($parentPost)) {
            return '';
        }

        return get_the_title($parentPost);
    }
}