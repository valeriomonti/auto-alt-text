<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;

class OpenAITextCompletionResponse extends OpenAIResponse
{
    public function response(string $imageUrl): string
    {

        $model = PluginOptions::model();
        $apiKey = PluginOptions::apiKey();
        $prompt = parent::prompt($imageUrl);
        $client = OpenAI::client($apiKey);

        $result = $client->completions()->create([
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => 1
        ]);

        return trim($result['choices'][0]['text'], '"');
    }
}