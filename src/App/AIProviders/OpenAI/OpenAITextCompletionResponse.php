<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\Exceptions\OpenAIException;
use ValerioMonti\AutoAltText\Config\Constants;

class OpenAITextCompletionResponse extends OpenAIResponse
{
    private function __construct() {

    }

    public static function make(): OpenAITextCompletionResponse
    {
        return new self();
    }

    /**
     * @throws OpenAIException
     */
    public function response(string $imageUrl): string
    {
        $prompt = parent::prompt($imageUrl);
        $model = PluginOptions::model();

        $requestBody = [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens' => Constants::AAT_OPENAI_MAX_TOKENS,
            'temperature' => Constants::AAT_OPENAI_TEXT_COMPLETION_TEMPERATURE,
        ];

        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AAT_OPENAI_TEXT_COMPLETION_ENDPOINT);

        return $this->cleanString($decodedBody['choices'][0]['text']);
    }
}