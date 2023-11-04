<?php

namespace ValerioMonti\AutoAltText\App\AIProviders\Azure;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\AIProviderInterface;
use ValerioMonti\AutoAltText\App\Exceptions\Azure\AzureComputerVisionException;
use ValerioMonti\AutoAltText\App\Exceptions\Azure\AzureTranslateInstanceException;
use ValerioMonti\AutoAltText\Config\Constants;

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
                    'url' => 'https://images.unsplash.com/photo-1668554245790-bfdc72f0bb3d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80',
                    //Dev example 'https://images.unsplash.com/photo-1668554245790-bfdc72f0bb3d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80',
                ]),
                'method' => 'POST',
            ]
        );

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);
        if (array_key_exists('error', $bodyResult)) {
            throw new AzureComputerVisionException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        $altText = $bodyResult['captionResult']['text'];
        $selectedLanguage = PluginOptions::languageAzureTranslateInstance();
        
        // If the default language (en) is selected it is not necessary a translation
        if ($selectedLanguage == Constants::AAT_AZURE_DEFAULT_LANGUAGE) {
            return $altText;
        }
        return (AzureTranslator::make())->translate($altText, $selectedLanguage);
    }
}