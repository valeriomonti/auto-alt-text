<?php

namespace AATXT\App\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Domain\Exceptions\EmptyResponseException;
use AATXT\App\Domain\ValueObjects\AltText;

/**
 * Decorator that validates and normalizes the response.
 *
 * This decorator ensures that responses from AI providers meet quality standards:
 * - Validates that the response is not empty
 * - Truncates responses that exceed the recommended maximum length
 *
 * Example usage:
 * ```php
 * $validatedProvider = new ValidationDecorator($originalProvider);
 * $altText = $validatedProvider->response($imageUrl);
 * // Response is validated and truncated if needed
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
final class ValidationDecorator extends AIProviderDecorator
{
    /**
     * Maximum allowed length for alt text.
     * Uses the value from AltText value object for consistency.
     *
     * @var int
     */
    private $maxLength;

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
     * @param int|null $maxLength Maximum length for alt text (defaults to AltText::MAX_LENGTH)
     * @param bool $throwOnEmpty Whether to throw exception on empty response (default: true)
     */
    public function __construct(
        AIProviderInterface $provider,
        ?int $maxLength = null,
        bool $throwOnEmpty = true
    ) {
        parent::__construct($provider);
        $this->maxLength = $maxLength ?? AltText::MAX_LENGTH;
        $this->throwOnEmpty = $throwOnEmpty;
    }

    /**
     * Get the validated response from the provider.
     *
     * Validates that the response is not empty and truncates if too long.
     *
     * @param string $imageUrl The URL of the image to analyze
     * @return string The validated and possibly truncated alt text
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

        // Truncate if exceeds max length
        if (mb_strlen($trimmed) > $this->maxLength) {
            return $this->truncate($trimmed);
        }

        return $trimmed;
    }

    /**
     * Truncate text to max length at word boundary.
     *
     * Attempts to truncate at a word boundary to avoid cutting words in half.
     * Uses the same algorithm as AltText::truncate() for consistency.
     *
     * @param string $text The text to truncate
     * @return string The truncated text
     */
    private function truncate(string $text): string
    {
        $truncated = mb_substr($text, 0, $this->maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');

        // Only truncate at word boundary if we're not losing too much text (80% threshold)
        if ($lastSpace !== false && $lastSpace > $this->maxLength * 0.8) {
            return mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated;
    }

    /**
     * Get the configured maximum length.
     *
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
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
