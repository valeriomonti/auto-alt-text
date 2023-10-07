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

    private function __construct(AIProviderInterface $AIProvider)
    {
        $this->AIProvider = $AIProvider;
    }

    public static function make(AIProviderInterface $aiProvider): AltTextGeneratorAi
    {
        return new self($aiProvider);
    }

    public function altText(int $imageId): string
    {
        $imageUrl = wp_get_attachment_url($imageId);
        return $this->AIProvider->response($imageUrl);
    }
}