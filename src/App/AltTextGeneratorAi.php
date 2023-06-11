<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\AIProviderInterface;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
use ValerioMonti\AutoAltText\App\AIProviders\OpenAI\OpenAITextCompletionResponse;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
use ValerioMonti\AutoAltText\Config\Constants;
use OpenAI\Exceptions\ErrorException;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    private AIProviderInterface $AIProvider;

    public function __construct(AIProviderInterface $AIProvider)
    {
        $this->AIProvider = $AIProvider;
    }

    public function altText(int $imageId): string
    {
        $altText = '';
        $imageUrl = wp_get_attachment_url($imageId);

        $altText = $this->AIProvider->response($imageUrl);

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