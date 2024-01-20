<?php
namespace AATXT\App;

interface AltTextGeneratorInterface
{
    public function altText(int $imageId): string;
}