<?php

namespace AATXT\App\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;

/**
 * Abstract base decorator for AI providers.
 *
 * This class implements the Decorator pattern, allowing additional behavior
 * to be added to AI providers dynamically without modifying their code.
 *
 * Decorators can be stacked to add multiple behaviors:
 * ```php
 * $provider = new CachingDecorator(
 *     new ValidationDecorator(
 *         new CleaningDecorator(
 *             new OpenAIVision($httpClient, $config)
 *         )
 *     )
 * );
 * ```
 *
 * @package AATXT\App\AIProviders\Decorators
 */
abstract class AIProviderDecorator implements AIProviderInterface
{
    /**
     * The wrapped provider
     *
     * @var AIProviderInterface
     */
    protected $provider;

    /**
     * Constructor
     *
     * @param AIProviderInterface $provider The provider to decorate
     */
    public function __construct(AIProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get the wrapped provider.
     *
     * Useful for accessing the original provider or unwrapping decorators.
     *
     * @return AIProviderInterface
     */
    public function getWrappedProvider(): AIProviderInterface
    {
        return $this->provider;
    }

    /**
     * Recursively unwrap all decorators to get the original provider.
     *
     * @return AIProviderInterface The innermost provider
     */
    public function getOriginalProvider(): AIProviderInterface
    {
        $provider = $this->provider;

        while ($provider instanceof self) {
            $provider = $provider->getWrappedProvider();
        }

        return $provider;
    }
}
