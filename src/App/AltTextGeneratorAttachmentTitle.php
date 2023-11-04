<?php
namespace Valeriomonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorAttachmentTitle implements AltTextGeneratorInterface
{

    private function __construct()
    {
    }

    /**
     * @return AltTextGeneratorAttachmentTitle
     */
    public static function make(): AltTextGeneratorAttachmentTitle
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
        return get_the_title($imageId);
    }
}