<?php

namespace AATXT\Tests\Unit\AIProviders\Decorators;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AIProviders\Decorators\CachingDecorator;
use AATXT\App\AIProviders\Decorators\CleaningDecorator;
use AATXT\App\AIProviders\Decorators\DecoratorBuilder;
use AATXT\App\AIProviders\Decorators\ValidationDecorator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DecoratorBuilder
 */
class DecoratorBuilderTest extends TestCase
{
    /**
     * Test wrap returns builder instance
     */
    public function testWrapReturnsBuilderInstance(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $builder = DecoratorBuilder::wrap($provider);

        $this->assertInstanceOf(DecoratorBuilder::class, $builder);
    }

    /**
     * Test build without decorators returns original provider
     */
    public function testBuildWithoutDecoratorsReturnsOriginalProvider(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)->build();

        $this->assertSame($provider, $result);
    }

    /**
     * Test withCleaning returns self for fluent interface
     */
    public function testWithCleaningReturnsSelf(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $builder = DecoratorBuilder::wrap($provider);
        $result = $builder->withCleaning();

        $this->assertSame($builder, $result);
    }

    /**
     * Test withCleaning adds CleaningDecorator
     */
    public function testWithCleaningAddsCleaningDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withCleaning()
            ->build();

        $this->assertInstanceOf(CleaningDecorator::class, $result);
    }

    /**
     * Test withValidation returns self for fluent interface
     */
    public function testWithValidationReturnsSelf(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $builder = DecoratorBuilder::wrap($provider);
        $result = $builder->withValidation();

        $this->assertSame($builder, $result);
    }

    /**
     * Test withValidation adds ValidationDecorator
     */
    public function testWithValidationAddsValidationDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withValidation()
            ->build();

        $this->assertInstanceOf(ValidationDecorator::class, $result);
    }

    /**
     * Test withValidation with throwOnEmpty false
     */
    public function testWithValidationWithThrowOnEmptyFalse(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        /** @var ValidationDecorator $result */
        $result = DecoratorBuilder::wrap($provider)
            ->withValidation(false)
            ->build();

        $this->assertInstanceOf(ValidationDecorator::class, $result);
        $this->assertFalse($result->throwsOnEmpty());
    }

    /**
     * Test withCaching returns self for fluent interface
     */
    public function testWithCachingReturnsSelf(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $builder = DecoratorBuilder::wrap($provider);
        $result = $builder->withCaching();

        $this->assertSame($builder, $result);
    }

    /**
     * Test withCaching adds CachingDecorator
     */
    public function testWithCachingAddsCachingDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withCaching()
            ->build();

        $this->assertInstanceOf(CachingDecorator::class, $result);
    }

    /**
     * Test withAllDecorators adds all decorators
     */
    public function testWithAllDecoratorsAddsAllDecorators(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withAllDecorators('test')
            ->build();

        // Outermost is CachingDecorator
        $this->assertInstanceOf(CachingDecorator::class, $result);
    }

    /**
     * Test decorator order is Provider -> Cleaning -> Validation -> Caching
     */
    public function testDecoratorOrderIsCorrect(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('response')
            ->willReturn('   "Test   response"   ');

        $decorated = DecoratorBuilder::wrap($provider)
            ->withCleaning()
            ->withValidation()
            ->build();

        // The response goes through cleaning first (removes quotes and extra spaces)
        // then validation (trims the result)
        $result = $decorated->response('http://example.com/test.jpg');

        // Should have quotes removed and be trimmed
        $this->assertStringNotContainsString('"', $result);
    }

    /**
     * Test fluent interface chaining
     */
    public function testFluentInterfaceChaining(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withCleaning()
            ->withValidation(false)
            ->withCaching(7200, 'openai')
            ->build();

        $this->assertInstanceOf(AIProviderInterface::class, $result);
    }

    /**
     * Test multiple builders are independent
     */
    public function testMultipleBuildersAreIndependent(): void
    {
        $provider1 = $this->createMock(AIProviderInterface::class);
        $provider2 = $this->createMock(AIProviderInterface::class);

        $builder1 = DecoratorBuilder::wrap($provider1)->withCleaning();
        $builder2 = DecoratorBuilder::wrap($provider2)->withValidation();

        $result1 = $builder1->build();
        $result2 = $builder2->build();

        $this->assertInstanceOf(CleaningDecorator::class, $result1);
        $this->assertInstanceOf(ValidationDecorator::class, $result2);
    }

    /**
     * Test only cleaning decorator
     */
    public function testOnlyCleaningDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withCleaning()
            ->build();

        $this->assertInstanceOf(CleaningDecorator::class, $result);
        $this->assertNotInstanceOf(ValidationDecorator::class, $result);
        $this->assertNotInstanceOf(CachingDecorator::class, $result);
    }

    /**
     * Test only validation decorator
     */
    public function testOnlyValidationDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withValidation()
            ->build();

        $this->assertInstanceOf(ValidationDecorator::class, $result);
        $this->assertNotInstanceOf(CleaningDecorator::class, $result);
        $this->assertNotInstanceOf(CachingDecorator::class, $result);
    }

    /**
     * Test only caching decorator
     */
    public function testOnlyCachingDecorator(): void
    {
        $provider = $this->createMock(AIProviderInterface::class);

        $result = DecoratorBuilder::wrap($provider)
            ->withCaching()
            ->build();

        $this->assertInstanceOf(CachingDecorator::class, $result);
        $this->assertNotInstanceOf(CleaningDecorator::class, $result);
        $this->assertNotInstanceOf(ValidationDecorator::class, $result);
    }
}
