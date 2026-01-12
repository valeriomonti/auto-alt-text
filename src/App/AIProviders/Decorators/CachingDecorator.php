<?php

namespace AATXT\App\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;

/**
 * Decorator that caches AI provider responses using WordPress Transients API.
 *
 * This decorator prevents redundant API calls by caching responses for a
 * configurable TTL. This is especially useful for:
 * - Reducing API costs
 * - Improving response times for repeated requests
 * - Handling retries more gracefully
 *
 * Example usage:
 * ```php
 * $cachedProvider = new CachingDecorator($originalProvider, 3600); // 1 hour cache
 * $altText = $cachedProvider->response($imageUrl);
 * // Subsequent calls with same URL will return cached result
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
final class CachingDecorator extends AIProviderDecorator
{
    /**
     * Default cache TTL: 1 hour
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache key prefix to namespace our transients
     */
    private const CACHE_PREFIX = 'aatxt_alt_';

    /**
     * Cache TTL in seconds
     *
     * @var int
     */
    private $ttl;

    /**
     * Optional provider identifier for cache key namespacing
     *
     * @var string
     */
    private $providerIdentifier;

    /**
     * Constructor
     *
     * @param AIProviderInterface $provider The provider to decorate
     * @param int $ttl Cache TTL in seconds (default: 1 hour)
     * @param string $providerIdentifier Optional identifier to namespace cache keys by provider
     */
    public function __construct(
        AIProviderInterface $provider,
        int $ttl = self::DEFAULT_TTL,
        string $providerIdentifier = ''
    ) {
        parent::__construct($provider);
        $this->ttl = max(0, $ttl);
        $this->providerIdentifier = $providerIdentifier;
    }

    /**
     * Get the response, using cache if available.
     *
     * First checks the cache for a stored response. If found, returns it.
     * Otherwise, calls the wrapped provider and caches the result.
     *
     * @param string $imageUrl The URL of the image to analyze
     * @return string The alt text (from cache or freshly generated)
     */
    public function response(string $imageUrl): string
    {
        $cacheKey = $this->generateCacheKey($imageUrl);

        // Try to get from cache
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        // Get fresh response from provider
        $response = $this->provider->response($imageUrl);

        // Cache the response (only cache non-empty responses)
        if ($response !== '') {
            $this->saveToCache($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the image URL.
     *
     * Uses MD5 hash of the URL to create a consistent, safe key.
     * Optionally namespaced by provider identifier to allow different
     * providers to cache different results for the same image.
     *
     * @param string $imageUrl The image URL
     * @return string The cache key
     */
    private function generateCacheKey(string $imageUrl): string
    {
        $key = self::CACHE_PREFIX;

        if ($this->providerIdentifier !== '') {
            $key .= $this->providerIdentifier . '_';
        }

        // WordPress transient keys are limited to 172 characters
        // MD5 produces 32 characters, prefix is max ~20, so we're safe
        $key .= md5($imageUrl);

        return $key;
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key The cache key
     * @return string|false The cached value or false if not found
     */
    private function getFromCache(string $key)
    {
        if (!function_exists('get_transient')) {
            return false;
        }

        return get_transient($key);
    }

    /**
     * Save a value to the cache.
     *
     * @param string $key The cache key
     * @param string $value The value to cache
     * @return bool True if saved successfully
     */
    private function saveToCache(string $key, string $value): bool
    {
        if (!function_exists('set_transient')) {
            return false;
        }

        return set_transient($key, $value, $this->ttl);
    }

    /**
     * Clear the cache for a specific image URL.
     *
     * @param string $imageUrl The image URL
     * @return bool True if deleted successfully
     */
    public function clearCache(string $imageUrl): bool
    {
        if (!function_exists('delete_transient')) {
            return false;
        }

        $cacheKey = $this->generateCacheKey($imageUrl);
        return delete_transient($cacheKey);
    }

    /**
     * Get the configured TTL.
     *
     * @return int TTL in seconds
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Get the provider identifier used for cache namespacing.
     *
     * @return string
     */
    public function getProviderIdentifier(): string
    {
        return $this->providerIdentifier;
    }
}
