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

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult[0]['translations'][0]['text'];
    }

    /**
     * @return array
     * @throws AzureTranslateInstanceException
     */
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

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult['translation'];
    }
}