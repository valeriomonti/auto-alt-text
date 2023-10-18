<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\Config\Constants;

class OpenAITextCompletionResponse extends OpenAIResponse
{
    private function __construct() {

    }

    public static function make(): OpenAITextCompletionResponse
    {
        return new self();
    }
    public function response(string $imageUrl): string
    {
        $model = PluginOptions::model();
        $apiKey = PluginOptions::apiKeyOpenAI();
        $prompt = parent::prompt($imageUrl);
        $client = OpenAI::client($apiKey);

        $result = $client->completions()->create([
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens' => Constants::AAT_OPENAI_MAX_TOKENS,
            'temperature' => 1
        ]);

        return $this->cleanString($result['choices'][0]['text']);
    }
}