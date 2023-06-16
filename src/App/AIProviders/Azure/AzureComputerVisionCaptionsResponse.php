<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\Azure;

use ValerioMonti\AutoAltText\App\Admin\PluginOptions;
use ValerioMonti\AutoAltText\App\AIProviders\AIProviderInterface;
use ValerioMonti\AutoAltText\App\Exceptions\AzureComputerVisionException;

class AzureComputerVisionCaptionsResponse implements AIProviderInterface
{
    public function response(string $imageUrl): string
    {
        $response = wp_remote_post(
            PluginOptions::endpointAzure() . 'computervision/imageanalysis:analyze?api-version=2023-02-01-preview&features=caption&language=en&gender-neutral-caption=False',
            [
                'headers'   => [
                    'content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key'     => PluginOptions::apiKeyAzure(),
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

        return $bodyResult['captionResult']['text'];
    }
}