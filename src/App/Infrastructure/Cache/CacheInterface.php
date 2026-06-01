<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Cache;

/**
 * Minimal cache abstraction for short-lived key/value storage.
 *
 * Implementations wrap a backing store (e.g. WordPress transients) and allow
 * dependency injection so consumers can be unit-tested without WordPress.
 */
interface CacheInterface
{
    /**
     * Retrieve a value from the cache.
     *
     * @param string $key
     * @return mixed|null Null when the key is missing.
     */
    public function get(string $key);

    /**
     * Store a value in the cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl Time-to-live in seconds.
     */
    public function set(string $key, $value, int $ttl): void;

    /**
     * Remove a value from the cache.
     *
     * @param string $key
     */
    public function delete(string $key): void;
}
