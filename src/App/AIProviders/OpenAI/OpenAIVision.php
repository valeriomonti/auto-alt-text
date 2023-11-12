<?php

namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\Exceptions\OpenAI\OpenAIException;
use ValerioMonti\AutoAltText\Config\Constants;

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
        $prompt = parent::prompt($imageUrl);

        $requestBody = [
            'model' => Constants::AAT_OPENAI_VISION_MODEL,
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
                                "url" => "https://upload.wikimedia.org/wikipedia/commons/thumb/d/dd/Gfp-wisconsin-madison-the-nature-boardwalk.jpg/2560px-Gfp-wisconsin-madison-the-nature-boardwalk.jpg"
                            ]
                        ]
                    ]
                ],
            ],
            'max_tokens' => Constants::AAT_OPENAI_MAX_TOKENS,
        ];

        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AAT_OPENAI_CHAT_COMPLETION_ENDPOINT);

        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }
}