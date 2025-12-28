<?php

namespace AATXT\App\Infrastructure\Repositories;

/**
 * WordPress Config Repository
 *
 * Concrete implementation of ConfigRepositoryInterface using WordPress options API.
 * This class wraps WordPress functions for configuration management,
 * providing a clean abstraction layer.
 */
final class WordPressConfigRepository implements ConfigRepositoryInterface
{
    /**
     * Get a configuration value
     *
     * Wraps WordPress get_option() function.
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, $default = null)
    {
        $value = get_option($key, $default);

        // WordPress returns false when option doesn't exist
        // Return the default value in this case
        if ($value === false && $default !== null) {
            return $default;
        }

        return $value;
    }

    /**
     * Set a configuration value
     *
     * Wraps WordPress update_option() function.
     * Creates the option if it doesn't exist, updates if it does.
     *
     * @param string $key The configuration key
     * @param mixed $value The value to store
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value): bool
    {
        return update_option($key, $value);
    }

    /**
     * Delete a configuration value
     *
     * Wraps WordPress delete_option() function.
     *
     * @param string $key The configuration key to delete
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool
    {
        return delete_option($key);
    }

    /**
     * Check if a configuration key exists
     *
     * WordPress doesn't have a direct has_option() function,
     * so we use get_option() with a unique default value to check existence.
     *
     * @param string $key The configuration key to check
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        // Use a unique object as default to distinguish between
        // "option doesn't exist" and "option exists with false value"
        $default = new \stdClass();
        $value = get_option($key, $default);

        return $value !== $default;
    }
}
