<?php

namespace AATXT\App\AIProviders\OpenAI;

use AATXT\App\Configuration\AIProviderConfig;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;

/**
 * OpenAI Vision provider for generating alt text using GPT models with vision capabilities.
 *
 * This class uses dependency injection to receive HTTP client and configuration,
 * removing static dependencies on PluginOptions.
 */
class OpenAIVision extends OpenAIResponse
{
    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AIProviderConfig $config Configuration with API key, prompt, and model
     */
    public function __construct(HttpClientInterface $httpClient, AIProviderConfig $config)
    {
        parent::__construct($httpClient, $config);
    }

    /**
     * Make a request to OpenAI Chat APIs to retrieve a description for the image passed
     * @param string $imageUrl
     * @return string
     * @throws OpenAIException
     */
    public function response(string $imageUrl): string
    {
        $prompt = parent::prompt();
        $requestBody = parent::prepareRequestBody($this->config->getModel(), $prompt, $imageUrl);
        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT);
        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }
}