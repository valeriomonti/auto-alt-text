<?php
namespace Valeriomonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorAttachmentTitle implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        return get_the_title($imageId);
    }
}