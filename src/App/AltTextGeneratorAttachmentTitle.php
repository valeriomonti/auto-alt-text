<?php
namespace Valeriomonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorAttachmentTitle implements AltTextGeneratorInterface
{
    private function __construct()
    {

    }

    public static function make(): AltTextGeneratorAttachmentTitle
    {
        return new self();
    }
    public function altText(int $imageId): string
    {
        return get_the_title($imageId);
    }
}