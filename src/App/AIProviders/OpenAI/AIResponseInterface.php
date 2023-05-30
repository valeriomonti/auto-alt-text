<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;
use OpenAI\Client;

interface AIResponseInterface
{
    public function response(Client $client, string $model, string $prompt): string;
}