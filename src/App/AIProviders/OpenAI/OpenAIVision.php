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
     * @var OpenAIModelsRegistry|null
     */
    private $modelsRegistry;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param AIProviderConfig $config Configuration with API key, prompt, and model
     * @param OpenAIModelsRegistry|null $modelsRegistry Optional registry used to fall back
     *        to the least expensive available model when the configured one has been retired
     */
    public function __construct(
        HttpClientInterface $httpClient,
        AIProviderConfig $config,
        ?OpenAIModelsRegistry $modelsRegistry = null
    ) {
        parent::__construct($httpClient, $config);
        $this->modelsRegistry = $modelsRegistry;
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
        $requestBody = parent::prepareRequestBody($this->resolveModel(), $prompt, $imageUrl);
        $decodedBody = parent::decodedResponseBody($requestBody, Constants::AATXT_OPENAI_RESPONSES_API_ENDPOINT);

        foreach($decodedBody['output'] as $output) {
            if($output['type'] === 'message') {
                return $this->cleanString($output['content'][0]['text']);
            }
        }

        return '';
    }

    /**
     * Return the model id to send to the API, transparently falling back to the
     * least expensive available model when the configured one is no longer
     * listed by the registry.
     */
    private function resolveModel(): string
    {
        $configured = $this->config->getModel();

        if ($this->modelsRegistry === null || $configured === '') {
            return $configured;
        }

        if ($this->modelsRegistry->isAvailable($configured)) {
            return $configured;
        }

        return $this->modelsRegistry->getDefaultModel();
    }
}