<?php

namespace AATXT\App\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\Domain\ValueObjects\AltText;

/**
 * Builder for assembling decorated AI providers.
 *
 * This builder provides a fluent interface for wrapping AI providers
 * with decorators in the correct order.
 *
 * Recommended decorator order (inside to outside):
 * 1. Provider (innermost) - The actual AI provider
 * 2. Cleaning - Clean the raw response
 * 3. Validation - Validate and truncate if needed
 * 4. Caching (outermost) - Cache the final result
 *
 * Example usage:
 * ```php
 * $decoratedProvider = DecoratorBuilder::wrap($openAIProvider)
 *     ->withCleaning()
 *     ->withValidation()
 *     ->withCaching(3600, 'openai')
 *     ->build();
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
final class DecoratorBuilder
{
    /**
     * The provider being decorated
     *
     * @var AIProviderInterface
     */
    private $provider;

    /**
     * Whether to add cleaning decorator
     *
     * @var bool
     */
    private $withCleaning = false;

    /**
     * Whether to add validation decorator
     *
     * @var bool
     */
    private $withValidation = false;

    /**
     * Validation max length
     *
     * @var int|null
     */
    private $validationMaxLength;

    /**
     * Whether validation throws on empty
     *
     * @var bool
     */
    private $validationThrowOnEmpty = true;

    /**
     * Whether to add caching decorator
     *
     * @var bool
     */
    private $withCaching = false;

    /**
     * Cache TTL in seconds
     *
     * @var int
     */
    private $cacheTtl = 3600;

    /**
     * Cache provider identifier
     *
     * @var string
     */
    private $cacheProviderIdentifier = '';

    /**
     * Private constructor - use static factory method.
     *
     * @param AIProviderInterface $provider The base provider
     */
    private function __construct(AIProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Start building a decorated provider.
     *
     * @param AIProviderInterface $provider The base provider to decorate
     * @return self
     */
    public static function wrap(AIProviderInterface $provider): self
    {
        return new self($provider);
    }

    /**
     * Add the cleaning decorator.
     *
     * Removes unwanted characters like quotes and extra whitespace.
     *
     * @return self Fluent interface
     */
    public function withCleaning(): self
    {
        $this->withCleaning = true;
        return $this;
    }

    /**
     * Add the validation decorator.
     *
     * Validates response is not empty and truncates if too long.
     *
     * @param int|null $maxLength Maximum length (defaults to AltText::MAX_LENGTH)
     * @param bool $throwOnEmpty Whether to throw exception on empty (default: true)
     * @return self Fluent interface
     */
    public function withValidation(?int $maxLength = null, bool $throwOnEmpty = true): self
    {
        $this->withValidation = true;
        $this->validationMaxLength = $maxLength;
        $this->validationThrowOnEmpty = $throwOnEmpty;
        return $this;
    }

    /**
     * Add the caching decorator.
     *
     * Caches responses to avoid redundant API calls.
     *
     * @param int $ttl Cache TTL in seconds (default: 1 hour)
     * @param string $providerIdentifier Optional identifier for cache namespacing
     * @return self Fluent interface
     */
    public function withCaching(int $ttl = 3600, string $providerIdentifier = ''): self
    {
        $this->withCaching = true;
        $this->cacheTtl = $ttl;
        $this->cacheProviderIdentifier = $providerIdentifier;
        return $this;
    }

    /**
     * Add all standard decorators with default settings.
     *
     * Equivalent to:
     * ```php
     * ->withCleaning()
     * ->withValidation()
     * ->withCaching(3600, $providerIdentifier)
     * ```
     *
     * @param string $providerIdentifier Cache provider identifier
     * @return self Fluent interface
     */
    public function withAllDecorators(string $providerIdentifier = ''): self
    {
        return $this
            ->withCleaning()
            ->withValidation()
            ->withCaching(3600, $providerIdentifier);
    }

    /**
     * Build the decorated provider.
     *
     * Applies decorators in the correct order:
     * Provider → Cleaning → Validation → Caching
     *
     * @return AIProviderInterface The decorated provider
     */
    public function build(): AIProviderInterface
    {
        $provider = $this->provider;

        // Apply cleaning first (closest to provider)
        if ($this->withCleaning) {
            $provider = new CleaningDecorator($provider);
        }

        // Then validation
        if ($this->withValidation) {
            $provider = new ValidationDecorator(
                $provider,
                $this->validationMaxLength,
                $this->validationThrowOnEmpty
            );
        }

        // Finally caching (outermost, caches the final result)
        if ($this->withCaching) {
            $provider = new CachingDecorator(
                $provider,
                $this->cacheTtl,
                $this->cacheProviderIdentifier
            );
        }

        return $provider;
    }
}
