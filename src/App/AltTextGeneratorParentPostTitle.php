<?php
namespace Valeriomonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorParentPostTitle implements AltTextGeneratorInterface
{
    private function __construct()
    {
    }

    /**
     * @return AltTextGeneratorParentPostTitle
     */
    public static function make(): AltTextGeneratorParentPostTitle
    {
        return new self();
    }

    /**
     * Get the alt text of the image
     * @param int $imageId
     * @return string
     */
    public function altText(int $imageId): string
    {
        $parentPost = get_post_parent($imageId);

        if (empty($parentPost)) {
            return '';
        }

        return get_the_title($parentPost);
    }
}