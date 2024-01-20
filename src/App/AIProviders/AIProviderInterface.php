<?php

namespace AATXT\App\AIProviders;

interface AIProviderInterface
{
    public function response(string $imageUrl): string;
}