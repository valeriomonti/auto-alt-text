<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\OpenAI;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\AIProviderInterface;
use ValerioMonti\AutoAltText\Config\Constants;

abstract class OpenAIResponse implements AIProviderInterface
{
    abstract public function response(string $imageUrl): string;

    /**
     * @param string $imageUrl
     * @return string
     */
    protected function prompt(string $imageUrl): string
    {
        $prompt = PluginOptions::prompt() ?: Constants::AAT_OPENAI_DEFAULT_PROMPT;
        return str_replace(Constants::AAT_IMAGE_URL_TAG, $imageUrl, $prompt);
    }
}