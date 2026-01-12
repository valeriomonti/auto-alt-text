<?php

namespace AATXT\App\Infrastructure\Repositories;

/**
 * Config Repository Interface
 *
 * Defines the contract for configuration storage and retrieval.
 * This interface abstracts WordPress options API for better testability
 * and adherence to Dependency Inversion Principle.
 */
interface ConfigRepositoryInterface
{
    /**
     * Get a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, $default = null);

    /**
     * Set a configuration value
     *
     * @param string $key The configuration key
     * @param mixed $value The value to store
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value): bool;

    /**
     * Delete a configuration value
     *
     * @param string $key The configuration key to delete
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool;

    /**
     * Check if a configuration key exists
     *
     * @param string $key The configuration key to check
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool;
}
