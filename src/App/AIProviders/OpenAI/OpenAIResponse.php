<?php
namespace AATXT\App\AIProviders\OpenAI;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\Config\Constants;

abstract class OpenAIResponse implements AIProviderInterface
{
    abstract public function response(string $imageUrl): string;

    /**
     * Send the request to the OpenAI APIs and return the decoded response
     * @param array $requestBody
     * @param string $endpoint
     * @return array
     * @throws OpenAIException
     */
    protected function decodedResponseBody(array $requestBody, string $endpoint): array
    {

        $apiKey = PluginOptions::apiKeyOpenAI();

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json; charset=utf-8',
        ];

        $args = [
            'headers' => $headers,
            'body'    => json_encode($requestBody),
            'method'  => 'POST',
            'data_format' => 'body',
        ];

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new OpenAIException("Something went wrong: $error_message");
        }

        $responseBody = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($responseBody, true);

        if (isset($decodedBody['error'])) {
            throw new OpenAIException('Error type: ' . $decodedBody['error']['type'] . ' - Error code: ' . $decodedBody['error']['code'] . ' - ' . $decodedBody['error']['message']);
        }

        return $decodedBody;

    }

    /**
     * Return the main OpenAI prompt
     * @return string
     */
    protected function prompt(): string
    {
        return PluginOptions::prompt() ?: Constants::AATXT_OPENAI_DEFAULT_PROMPT;
    }

    /**
     * Compute the fallback prompt based on the template saved in the options and the imageUrl passed
     * @param string $imageUrl
     * @return string
     */
    protected function fallbackPrompt(string $imageUrl): string
    {
        $prompt = PluginOptions::fallbackPrompt() ?: Constants::AATXT_OPENAI_DEFAULT_FALLBACK_PROMPT;
        return str_replace(Constants::AATXT_IMAGE_URL_TAG, $imageUrl, $prompt);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function cleanString(string $text): string
    {
        $patterns = array(
            '/\"/',        // Double quotes
            '/\s\s+/',     // Double or more consecutive white spaces
            '/&quot;/'     // HTML sequence for double quotes
        );

        return trim(preg_replace($patterns, '', $text));
    }
}