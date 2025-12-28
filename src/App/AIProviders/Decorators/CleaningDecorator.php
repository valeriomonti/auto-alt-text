<?php

namespace AATXT\App\AIProviders\Decorators;

/**
 * Decorator that cleans the response from unwanted characters.
 *
 * This decorator applies text cleaning to the response from any AI provider,
 * removing unwanted characters like extra quotes, multiple spaces, and HTML entities.
 *
 * Example usage:
 * ```php
 * $cleanProvider = new CleaningDecorator($originalProvider);
 * $altText = $cleanProvider->response($imageUrl);
 * // Response is automatically cleaned
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
final class CleaningDecorator extends AIProviderDecorator
{
    /**
     * Patterns to remove from the response.
     *
     * @var array<string>
     */
    private const CLEANING_PATTERNS = [
        '/\"/',        // Double quotes
        '/\s\s+/',     // Two or more consecutive whitespaces
        '/&quot;/',    // HTML entity for double quotes
    ];

    /**
     * Get the cleaned response from the provider.
     *
     * Calls the wrapped provider's response method and cleans the result.
     *
     * @param string $imageUrl The URL of the image to analyze
     * @return string The cleaned alt text
     */
    public function response(string $imageUrl): string
    {
        $response = $this->provider->response($imageUrl);
        return $this->cleanString($response);
    }

    /**
     * Clean response string from unwanted characters.
     *
     * Removes:
     * - Double quotes (")
     * - Multiple consecutive whitespaces
     * - HTML quote entities (&quot;)
     *
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    private function cleanString(string $text): string
    {
        $cleaned = preg_replace(self::CLEANING_PATTERNS, '', $text);

        // preg_replace returns null on error, fallback to original
        if ($cleaned === null) {
            return trim($text);
        }

        return trim($cleaned);
    }
}
