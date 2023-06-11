<?php
namespace ValerioMonti\AutoAltText\App\AIProviders\Azure;

use ValerioMonti\AutoAltText\App\AIProviders\AIProviderInterface;

class AzureComputerVisionCaptionsResponse implements AIProviderInterface
{
    public function response(string $imageUrl): string
    {
        $response = wp_remote_post(
            'https://computer-vision-france-central.cognitiveservices.azure.com/computervision/imageanalysis:analyze?api-version=2023-02-01-preview&features=caption&language=en&gender-neutral-caption=False',
            [
                'headers'   => [
                    'content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key'     => '',
                ],
                'body' => json_encode([
                    'url' => 'https://images.unsplash.com/photo-1668554245790-bfdc72f0bb3d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80',
                ]),
                'method' => 'POST',
            ]
        );

        $bodyResult = json_decode(wp_remote_retrieve_body($response), true);
        return $bodyResult['captionResult']['text'];
    }
}