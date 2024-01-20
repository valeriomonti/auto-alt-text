<?php

namespace AATXT\App\AIProviders\Azure;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Exceptions\Azure\AzureComputerVisionException;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;
use AATXT\Config\Constants;

class AzureComputerVisionCaptionsResponse implements AIProviderInterface
{
    private function __construct()
    {
    }

    public static function make(): AzureComputerVisionCaptionsResponse
    {
        return new self();
    }

    /**
     * Make a request to Azure Computer Vision APIs to retrieve the contents of the uploaded image
     * If necessary, translate the description into the requested language
     * @param string $imageUrl
     * @return string
     * @throws AzureComputerVisionException
     * @throws AzureTranslateInstanceException
     */
    public function response(string $imageUrl): string
    {
        $response = wp_remote_post(
            PluginOptions::endpointAzureComputerVision() . 'computervision/imageanalysis:analyze?api-version=2023-02-01-preview&features=caption&language=en&gender-neutral-caption=False',
            [
                'headers' => [
                    'content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => PluginOptions::apiKeyAzureComputerVision(),
                ],
                'body' => json_encode([
                    'url' => $imageUrl,
                ]),
                'method' => 'POST',
            ]
        );

        $responseBody = wp_remote_retrieve_body($response);
        if(empty($responseBody)) {
            if (is_object($response) && property_exists($response, 'errors') && array_key_exists('http_request_failed', $response->errors)) {
                throw new AzureTranslateInstanceException("Error: " . $response->errors['http_request_failed'][0]);
            }
            if (is_array($response) && array_key_exists('response', $response)) {
                throw new AzureTranslateInstanceException("Code: " . $response['response']['code'] . " - " . $response['response']['message']);
            }
            throw new AzureComputerVisionException("Error: please check if the Azure endpoint in plugin options is right");
        }

        $bodyResult = json_decode($responseBody, true);
        if (array_key_exists('error', $bodyResult)) {
            throw new AzureComputerVisionException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        $altText = $bodyResult['captionResult']['text'];
        $selectedLanguage = PluginOptions::languageAzureTranslateInstance();
        
        // If the default language (en) is selected it is not necessary a translation
        if ($selectedLanguage == Constants::AATXT_AZURE_DEFAULT_LANGUAGE) {
            return $altText;
        }

        return (AzureTranslator::make())->translate($altText, $selectedLanguage);
    }
}