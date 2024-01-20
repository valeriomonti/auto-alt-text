<?php
namespace AATXT\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;

class OpenAITextCompletionResponse extends OpenAIResponse
{
    private function __construct() {

    }

    public static function make(): OpenAITextCompletionResponse
    {
        return new self();
    }

    /**
     *  Make a request to OpenAI Text Completion APIs to retrieve a description for the image passed
     * @param string $imageUrl
     * @return string
     * @throws OpenAIException
     */
    public function response(string $imageUrl): string
    {
        $prompt = parent::prompt();
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