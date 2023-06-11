<?php

namespace ValerioMonti\AutoAltText\App\AIProviders;

interface AIProviderInterface
{
    public function response(string $imageUrl): string;
}