<?php

namespace ValerioMonti\AutoAltText\App;

use Composer\Installers\Plugin;
use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;
use ValerioMonti\AutoAltText\App\Admin\PluginOptions;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $altText = '';
        $imageUrl = wp_get_attachment_url($imageId);
        $url = 'https://api.openai.com/v1/chat/completions';
        $apiKey = PluginOptions::apiKey();

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        "role" => "user",
                        "content" => "Agisci come un esperto SEO e scrivi un alt text in italiano lungo al massimo 15 parole per questa immagine: " . $imageUrl,
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 1,
            ))
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $errorMessage = $response->get_error_message();
            echo "Something went wrong: $errorMessage";
        } else {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $altText = $data['choices'][0]['message']['content'] ?? '';
        }

        return $altText;
    }
}