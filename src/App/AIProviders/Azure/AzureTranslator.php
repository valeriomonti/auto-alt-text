<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\Azure;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\AITranslatorInterface;

class AzureTranslator implements AITranslatorInterface
{
    private function __construct()
    {

    }

    public static function make(): AzureTranslator
    {
        return new self();
    }
    public function translate(string $text, string $language): string
    {
        $route = "translate?api-version=3.0&from=en&to=" . $language;

        $response = wp_remote_post(
            PluginOptions::endpointAzureTranslateInstance() . $route,
            [
                'headers'   => [
                    'Content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key'     => PluginOptions::apiKeyAzureTranslateInstance(),
                    'Ocp-Apim-Subscription-Region' => PluginOptions::regionAzureTranslateInstance()
                ],
                'body' => json_encode([
                    [
                        'Text' => $text
                    ]
                ]),
                'method' => 'POST',
            ]
        );
        if (is_wp_error($response)) {
            // Gestisci l'errore di richiesta
        } else {
            $translatedText = json_decode(wp_remote_retrieve_body($response), true)[0]['translations'][0]['text'];
        }

        return $translatedText;
    }

    public function supportedLanguages(): array
    {
        $route = 'languages?api-version=3.0';

        $url = PluginOptions::endpointAzureTranslateInstance() . $route;

        $headers = array(
            'Content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => PluginOptions::apiKeyAzureTranslateInstance()
        );

        $response = wp_remote_get(
            $url,
            array(
                'headers' => $headers
            )
        );

        if (is_wp_error($response)) {
            // Gestisci l'errore di richiesta
        } else {
            $languages = json_decode(wp_remote_retrieve_body($response), true);
            // Elabora la risposta per ottenere la lista delle lingue
            return $languages['translation'];
        }

        return [];
    }
}