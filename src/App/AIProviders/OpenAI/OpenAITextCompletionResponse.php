<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI\Client;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;

class OpenAITextCompletionResponse implements OpenAIResponseInterface
{
    public function response(Client $client, string $model, string $prompt): string
    {
        $result = $client->completions()->create([
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => 1
        ]);

        return trim($result['choices'][0]['text'], '"');
    }
}