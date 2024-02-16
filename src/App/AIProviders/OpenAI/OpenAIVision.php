<?php

namespace AATXT\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;

class OpenAIVision extends OpenAIResponse
{
    public static function make(): OpenAIVision
    {
        return new self();
    }

    /**
     *  Make a request to OpenAI Chat APIs to retrieve a description for the image passed
     * @param string $imageUrl
     * @return string
     * @throws OpenAIException
     */
    public function response(string $imageUrl): string
    {
        $prompt = parent::prompt();

        $requestBody = [
            'model' => Constants::AATXT_OPENAI_VISION_MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => "text",
                            "text" => $prompt
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => $imageUrl
                            ]
                        ]
                    ]
                ],
            ],
            'max_tokens' => Constants::AATXT_OPENAI_MAX_TOKENS,
        ];

        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT);

        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }
}