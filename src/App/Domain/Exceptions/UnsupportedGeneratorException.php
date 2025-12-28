<?php

namespace AATXT\App\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when trying to create a generator for an unsupported type.
 *
 * This exception is thrown by the AltTextGeneratorFactory when a generator
 * type is requested that hasn't been registered in the factory.
 */
class UnsupportedGeneratorException extends Exception
{
    /**
     * Constructor
     *
     * @param string $type The unsupported generator type
     */
    public function __construct(string $type)
    {
        parent::__construct(
            sprintf('Unsupported generator type: "%s". Please register this generator type in the factory.', $type)
        );
    }
}
