<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use OpenAI;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $imageUrl = wp_get_attachment_url($imageId);
        $apiKey = PluginOptions::apiKey();

        $client = OpenAI::client($apiKey);

        $result = $client->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => "Agisci come un esperto SEO e scrivi un alt text in italiano lungo al massimo 15 parole per questa immagine: " . $imageUrl . ". Limitati semplicemente a ritornare il testo senza virgolette.",
            'temperature' => 1
        ]);

        return trim($result['choices'][0]['text']);
    }
}