<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\Setup;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;

class OpenAIChatCompletionResponse extends OpenAIResponse
{
    public static function make(): OpenAIChatCompletionResponse
    {
        return new self();
    }
    public function response(string $imageUrl): string
    {
        $model = PluginOptions::model();
        $apiKey = PluginOptions::apiKeyOpenAI();
        $prompt = parent::prompt($imageUrl);
        $client = OpenAI::client($apiKey);

        $result = $client->chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
        ]);
        return trim(preg_replace('/\s\s+\"/', '', $result->toArray()['choices'][0]['message']['content']));
    }
}