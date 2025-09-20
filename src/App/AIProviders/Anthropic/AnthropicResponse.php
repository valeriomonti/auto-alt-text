<?php

namespace AATXT\App\AIProviders\Anthropic;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Exceptions\Anthropic\AnthropicException;
use AATXT\Config\Constants;

class AnthropicResponse implements AIProviderInterface
{
    public static function make(): AnthropicResponse
    {
        return new self();
    }

    /**
     *  Make a request to OpenAI Chat APIs to retrieve a description for the image passed
     * @param string $imageUrl
     * @return string
     * @throws AnthropicException
     */
    public function response(string $imageUrl): string
    {
        $model = PluginOptions::anthropicModel();
        $apiKey = PluginOptions::apiKeyAnthropic();

        if(!$apiKey) {
            throw new AnthropicException('Anthropic API key is missing in plugin settings');
        }

        $payload = [
            "model"      => $model,
            "max_tokens" => 1024,
            "messages"   => [
                [
                    "role"    => "user",
                    "content" => [
                        [
                            "type"   => "image",
                            "source" => [
                                "type" => "url",
                                "url"  => $imageUrl,
                            ],
                        ],
                        [
                            "type" => "text",
                            "text" => PluginOptions::anthropicPrompt(),
                        ],
                    ],
                ],
            ],
        ];

        $args = [
            'headers'     => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'body'        => wp_json_encode($payload),
            'timeout'     => 30,
            'data_format' => 'body',
        ];

        $response = wp_remote_post(Constants::AATXT_ANTHROPIC_ENDPOINT, $args);
        if (is_wp_error($response)) {
            throw new AnthropicException('Request error: ' . $response->get_error_message());
        }
        $statusCode = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AnthropicException('HTTP Error ' . $statusCode . ':' . $body);
        }

        $data = json_decode($body, true);

        $answer = $data['content'][0]['text'] ?? null;

        if (!$answer) {
            throw new AnthropicException('Response format unattended : ' . $body);
        }

        return $answer;

    }
}