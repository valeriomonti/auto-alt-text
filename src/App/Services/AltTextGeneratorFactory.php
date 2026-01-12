<?php

namespace AATXT\App\Services;

use AATXT\App\AltTextGeneratorInterface;
use AATXT\App\Domain\Exceptions\UnsupportedGeneratorException;

/**
 * Factory interface for creating AltTextGenerator instances.
 *
 * This interface defines the contract for creating different types of
 * alt text generators based on a configuration type.
 *
 * Following the Open/Closed Principle, new generator types can be added
 * without modifying existing code, just by registering them in the factory.
 */
interface AltTextGeneratorFactory
{
    /**
     * Create an alt text generator instance for the given type.
     *
     * @param string $type The generator type identifier (e.g., 'openai', 'anthropic', 'azure')
     * @return AltTextGeneratorInterface The generator instance
     * @throws UnsupportedGeneratorException If the type is not registered
     */
    public function create(string $type): AltTextGeneratorInterface;
}
