<?php

namespace AATXT\App;

use Composer\Installers\Plugin;
use OpenAI;
use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\OpenAI\OpenAIChatCompletionResponse;
use AATXT\App\AIProviders\OpenAI\OpenAITextCompletionResponse;
use AATXT\App\AltTextGeneratorInterface;
use AATXT\App\Admin\PluginOptions;
use AATXT\Config\Constants;
use OpenAI\Exceptions\ErrorException;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    private AIProviderInterface $AIProvider;

    /**
     * @param AIProviderInterface $AIProvider
     */
    private function __construct(AIProviderInterface $AIProvider)
    {
        $this->AIProvider = $AIProvider;
    }

    /**
     * @param AIProviderInterface $aiProvider
     * @return AltTextGeneratorAi
     */
    public static function make(AIProviderInterface $aiProvider): AltTextGeneratorAi
    {
        return new self($aiProvider);
    }

    /**
     * Get the alt text of the image
     * @param int $imageId
     * @return string
     */
    public function altText(int $imageId): string
    {
        $imageUrl = wp_get_attachment_url($imageId);
        return $this->AIProvider->response($imageUrl);
    }
}