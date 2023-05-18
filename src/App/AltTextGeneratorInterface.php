<?php
namespace ValerioMonti\AutoAltText\App;

interface AltTextGeneratorInterface
{
    public function altText(int $imageId): string;
}