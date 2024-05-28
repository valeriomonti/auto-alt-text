<?php
namespace AATXT\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;

class Fallback extends OpenAIResponse
{
    public static function make(): Fallback
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
        $prompt = parent::prompt();
        $requestBody = parent::prepareRequestBody(Constants::AATXT_OPENAI_FALLBACK_MODEL, $prompt, $imageUrl);
        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT);
        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }
}