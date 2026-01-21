<?php

namespace AATXT\App\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Domain\Exceptions\EmptyResponseException;

/**
 * Decorator that validates the response.
 *
 * This decorator ensures that responses from AI providers meet quality standards:
 * - Validates that the response is not empty
 *
 * Note: This decorator does NOT truncate responses. The alt text length should be
 * controlled by the user's prompt to the AI provider.
 *
 * Example usage:
 * ```php
 * $validatedProvider = new ValidationDecorator($originalProvider);
 * $altText = $validatedProvider->response($imageUrl);
 * // Response is validated (non-empty check)
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
final class ValidationDecorator extends AIProviderDecorator
{
    /**
     * Whether to throw an exception on empty response.
     *
     * @var bool
     */
    private $throwOnEmpty;

    /**
     * Constructor
     *
     * @param AIProviderInterface $provider The provider to decorate
     * @param bool $throwOnEmpty Whether to throw exception on empty response (default: true)
     */
    public function __construct(
        AIProviderInterface $provider,
        bool $throwOnEmpty = true
    ) {
        parent::__construct($provider);
        $this->throwOnEmpty = $throwOnEmpty;
    }

    /**
     * Get the validated response from the provider.
     *
     * Validates that the response is not empty.
     *
     * @param string $imageUrl The URL of the image to analyze
     * @return string The validated alt text
     * @throws EmptyResponseException If response is empty and throwOnEmpty is true
     */
    public function response(string $imageUrl): string
    {
        $response = $this->provider->response($imageUrl);
        $trimmed = trim($response);

        // Validate non-empty
        if ($this->throwOnEmpty && $trimmed === '') {
            throw new EmptyResponseException(
                'AI provider returned an empty response for image: ' . $imageUrl
            );
        }

        return $trimmed;
    }

    /**
     * Check if decorator throws on empty response.
     *
     * @return bool
     */
    public function throwsOnEmpty(): bool
    {
        return $this->throwOnEmpty;
    }
}
