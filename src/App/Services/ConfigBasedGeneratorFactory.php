<?php

namespace AATXT\App\Services;

use AATXT\App\AltTextGeneratorInterface;
use AATXT\App\Domain\Exceptions\UnsupportedGeneratorException;

/**
 * Configuration-based implementation of AltTextGeneratorFactory.
 *
 * This factory allows registering generator creators (callables) for different
 * types and then creating instances on demand. This implements the Factory Pattern
 * and follows the Open/Closed Principle.
 *
 * Usage:
 * ```php
 * $factory = new ConfigBasedGeneratorFactory();
 * $factory->register('openai', fn() => new AltTextGeneratorAi(new OpenAIVision(...)));
 * $generator = $factory->create('openai');
 * ```
 */
final class ConfigBasedGeneratorFactory implements AltTextGeneratorFactory
{
    /**
     * Registered generator creators.
     *
     * Maps generator type strings to callables that create generator instances.
     *
     * @var array<string, callable>
     */
    private $generators;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->generators = [];
    }

    /**
     * Register a generator creator for a specific type.
     *
     * The creator callable should return an instance of AltTextGeneratorInterface.
     *
     * @param string $type The generator type identifier
     * @param callable $creator A callable that returns an AltTextGeneratorInterface instance
     * @return void
     */
    public function register(string $type, callable $creator): void
    {
        $this->generators[$type] = $creator;
    }

    /**
     * Create an alt text generator instance for the given type.
     *
     * @param string $type The generator type identifier
     * @return AltTextGeneratorInterface The generator instance
     * @throws UnsupportedGeneratorException If the type is not registered
     */
    public function create(string $type): AltTextGeneratorInterface
    {
        if (!isset($this->generators[$type])) {
            throw new UnsupportedGeneratorException($type);
        }

        $creator = $this->generators[$type];
        return $creator();
    }

    /**
     * Check if a generator type is registered.
     *
     * @param string $type The generator type to check
     * @return bool True if registered, false otherwise
     */
    public function has(string $type): bool
    {
        return isset($this->generators[$type]);
    }

    /**
     * Get all registered generator types.
     *
     * @return array<string> List of registered types
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->generators);
    }
}
