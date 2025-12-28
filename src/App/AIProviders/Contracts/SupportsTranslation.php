<?php

namespace AATXT\App\AIProviders\Contracts;

/**
 * Interface for AI providers that support text translation.
 *
 * Following the Interface Segregation Principle, this interface defines
 * only the methods related to translation capabilities.
 */
interface SupportsTranslation
{
    /**
     * Translate text to the specified target language.
     *
     * @param string $text The text to translate
     * @param string $targetLanguage The target language code (e.g., 'it', 'es', 'fr')
     * @return string The translated text
     */
    public function translate(string $text, string $targetLanguage): string;

    /**
     * Check if translation is enabled and configured.
     *
     * @return bool True if translation is available, false otherwise
     */
    public function isTranslationEnabled(): bool;

    /**
     * Get the configured target language for translation.
     *
     * @return string The language code, or empty string if not configured
     */
    public function getTargetLanguage(): string;
}
