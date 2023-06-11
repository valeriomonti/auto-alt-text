<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAITextCompletionResponse;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
use ValerioMonti\AutoAltText\Config\Constants;
use OpenAI\Exceptions\ErrorException;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $altText = '';
        $model = PluginOptions::model();
        //$endpoint = PluginOptions::endpoint();
        $apiKey = PluginOptions::apiKey();

        $imageUrl = wp_get_attachment_url($imageId);
        $prompt = $this->prompt($imageUrl);
        $client = OpenAI::client($apiKey);

        try {
            if (Constants::AAT_ENDPOINT_TEXT_COMPLETION == Constants::AAT_OPENAI_MODELS[$model]) {
                $altText = (new OpenAITextCompletionResponse())->response($client, $model, $prompt);
            } else {
                $altText = (new OpenAIChatCompletionResponse())->response($client, $model, $prompt);
            }
        } catch(ErrorException $e) {
            (new FileLogger())->writeImageLog($imageId, $e);
        }

        return $altText;
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