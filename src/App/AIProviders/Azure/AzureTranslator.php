<?php

namespace AATXT\App\AIProviders\Azure;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\AITranslatorInterface;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;

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
        $apiKey = PluginOptions::apiKeyAzureTranslateInstance();
        $region = PluginOptions::regionAzureTranslateInstance();
        $endpoint = PluginOptions::endpointAzureTranslateInstance();

        if (empty($apiKey) || empty($region) || empty($endpoint)) {
            return $text;
        }

        $route = "translate?api-version=3.0&from=en&to=" . $language;

        $response = wp_remote_post(
            $endpoint . $route,
            [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => $apiKey,
                    'Ocp-Apim-Subscription-Region' => $region,
                ],
                'body' => json_encode([
                    [
                        'Text' => $text
                    ]
                ]),
                'method' => 'POST',
            ]
        );


        //$bodyResult = json_decode(wp_remote_retrieve_body($response), true);

        $responseBody = wp_remote_retrieve_body($response);
        if (empty($responseBody)) {
            if (is_object($response) && property_exists($response, 'errors') && array_key_exists('http_request_failed', $response->errors)) {
                throw new AzureTranslateInstanceException("Error: " . $response->errors['http_request_failed'][0]);
            }
            if (is_array($response) && array_key_exists('response', $response)) {
                throw new AzureTranslateInstanceException("Code: " . $response['response']['code'] . " - " . $response['response']['message']);
            }
            throw new AzureTranslateInstanceException("Error: please check if the Azure endpoint in plugin options is right");
        }

        $bodyResult = json_decode($responseBody, true);

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
            throw new AzureTranslateInstanceException(esc_html__('No language retrieved: maybe the translation endpoint is wrong. Please check it out and try again.', 'auto-alt-text'));
        }

        if (array_key_exists('error', $bodyResult)) {
            throw new AzureTranslateInstanceException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        return $bodyResult['translation'];
    }
}