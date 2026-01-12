<?php

namespace AATXT\App\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when an AI provider returns an empty response.
 *
 * This exception indicates that the AI provider successfully responded
 * but returned an empty or invalid alt text.
 */
class EmptyResponseException extends Exception
{
    /**
     * Constructor
     *
     * @param string $provider The provider name that returned empty response
     */
    public function __construct(string $provider = 'AI Provider')
    {
        parent::__construct(
            sprintf('%s returned an empty response. Unable to generate alt text.', $provider)
        );
    }
}
