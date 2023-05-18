<?php
namespace ValerioMonti\AutoAltText\App;

use ValerioMonti\AutoAltText\App\AltTextGeneratorInterface;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    public function altText(int $imageId): string
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $apiKey = '';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    "role" => "user",
                    "content" => "Hello!"
                ],
                'max_tokens' => 3,
                'temperature' => 0.5,
            ))
        );
        $response = wp_remote_post( $url, $args );
        if ( is_wp_error( $response ) ) {
            // error
            $errorMessage = $response->get_error_message();
            echo "Something went wrong: $errorMessage";
        } else {
            // Response
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            print_r( $data );
        }
        return '';
    }
}