<?php
namespace AATXT\App\AIProviders\OpenAI;
use OpenAI\Client;

interface OpenAIResponseInterface
{
    public function response(Client $client, string $model, string $prompt): string;
}