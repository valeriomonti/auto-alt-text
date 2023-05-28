<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use OpenAI;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\Config\Constants;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $imageUrl = wp_get_attachment_url($imageId);
        $apiKey = PluginOptions::apiKey();
        $client = OpenAI::client($apiKey);

        $result = $client->completions()->create([
            'model' => PluginOptions::model(),
            'prompt' => $this->prompt($imageUrl),
            'temperature' => 1
        ]);

        return trim($result['choices'][0]['text']);
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