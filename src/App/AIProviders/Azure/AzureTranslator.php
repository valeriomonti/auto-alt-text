<?php

namespace ValerioMonti\AutoAltText\App\AIProviders\Azure;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\AITranslatorInterface;
use ValerioMonti\AutoAltText\App\Exceptions\Azure\AzureTranslateInstanceException;

class AzureTranslator implements AITranslatorInterface
{
    private function __construct()
    {
    }

    public static function make(): AzureTranslator
    {
        return new self();
    }

    /**
     * Translate a string sending a request to the Azure translation Api
     * @param string $text
     * @param string $language
     * @return string
     * @throws AzureTranslateInstanceException
     */
    public function translate(string $text, string $language): string
    {
        $route = "translate?api-version=3.0&from=en&to=" . $language;

        $response = wp_remote_post(
            PluginOptions::endpointAzureTranslateInstance() . $route,
            [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => PluginOptions::apiKeyAzureTranslateInstance(),
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

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult[0]['translations'][0]['text'];
    }

    /**
     * Get the list of supported languages from Azure Api
     * @return array
     * @throws AzureTranslateInstanceException
     */
    public function supportedLanguages(): array
    {
        $apiKey = PluginOptions::apiKeyAzureTranslateInstance();
        if (empty($apiKey)) {
            return [];
        }
        $endpoint = PluginOptions::endpointAzureTranslateInstance();
        if (empty($endpoint)) {
            return [];
        }

        $route = 'languages?api-version=3.0';

        $url = $endpoint . $route;

        $headers = array(
            'Content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $apiKey
        );

        $response = wp_remote_get(
            $url,
            array(
                'headers' => $headers
            )
        );

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($bodyResult)) {
            throw new AzureTranslateInstanceException(__('No language retrieved: maybe the translation endpoint is wrong. Please check it out and try again.', 'auto-alt-text'));
        }

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult['translation'];
    }
}