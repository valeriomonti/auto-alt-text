<?php

namespace AATXT\App\AIProviders;

interface AITranslatorInterface
{
    public function translate(string $text, string $language): string;
}