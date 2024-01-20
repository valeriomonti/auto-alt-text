<?php
namespace AATXT\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;

class OpenAIChatCompletionResponse extends OpenAIResponse
{
    public static function make(): OpenAIChatCompletionResponse
    {
        return new self();
    }

    /**
     *  Make a request to OpenAI Chat APIs to retrieve a description for the image file name passed
     * @param string $imageUrl
     * @return string
     * @throws OpenAIException
     */
    public function response(string $imageUrl): string
    {
        $model = PluginOptions::model();
        $prompt = parent::fallbackPrompt($imageUrl);

        $requestBody = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'max_tokens' => Constants::AAT_OPENAI_MAX_TOKENS,
        ];

        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AAT_OPENAI_CHAT_COMPLETION_ENDPOINT);

        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }
}