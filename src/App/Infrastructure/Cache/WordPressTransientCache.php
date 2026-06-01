<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Cache;

/**
 * Cache implementation backed by the WordPress Transients API.
 */
class WordPressTransientCache implements CacheInterface
{
    public function get(string $key)
    {
        $value = get_transient($key);
        return $value === false ? null : $value;
    }

    public function set(string $key, $value, int $ttl): void
    {
        set_transient($key, $value, $ttl);
    }

    public function delete(string $key): void
    {
        delete_transient($key);
    }
}
