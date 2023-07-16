<?php

namespace ValerioMonti\AutoAltText\App\AIProviders;

interface AITranslatorInterface
{
    public function translate(string $text, string $language): string;
}