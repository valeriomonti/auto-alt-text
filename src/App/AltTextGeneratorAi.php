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
        $imageUrl = wp_get_attachment_url($imageId);
        return $this->AIProvider->response($imageUrl);
    }
}