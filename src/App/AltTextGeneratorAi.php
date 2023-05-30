<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\ChatCompletionAIResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\TextCompletionAIResponse;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\Config\Constants;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $model = PluginOptions::model();
        $endpoint = PluginOptions::endpoint();
        $apiKey = PluginOptions::apiKey();

        $imageUrl = wp_get_attachment_url($imageId);
        $prompt = $this->prompt($imageUrl);

        $client = OpenAI::client($apiKey);

        if (Constants::AAT_OPTION_ENDPOINT_CHOICE_TEXT_COMPLETION == $endpoint) {
            return (new TextCompletionAIResponse())->response($client, $model, $prompt);
        }

        return (new ChatCompletionAIResponse())->response($client, $model, $prompt);
    }

    /**
     * @param string $imageUrl
     * @return string
     */
    private function prompt(string $imageUrl): string
    {
        $prompt = PluginOptions::prompt() ?: Constants::AAT_DEFAULT_PROMPT;
        return str_replace(Constants::AAT_IMAGE_URL_TAG, $imageUrl, $prompt);
    }
}