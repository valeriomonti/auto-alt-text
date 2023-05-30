<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use OpenAI\Client;

class ChatCompletionAIResponse implements AIResponseInterface
{
    public function response(Client $client, string $model, string $prompt): string
    {
        $result = $client->chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
        ]);

        return trim($result->toArray()['choices'][0]['message']['content']);
    }
}