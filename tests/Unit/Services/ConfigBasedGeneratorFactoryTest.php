<?php

namespace AATXT\Tests\Unit\Services;

use AATXT\App\AltTextGeneratorInterface;
use AATXT\App\Domain\Exceptions\UnsupportedGeneratorException;
use AATXT\App\Services\ConfigBasedGeneratorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConfigBasedGeneratorFactory
 */
class ConfigBasedGeneratorFactoryTest extends TestCase
{
    /**
     * @var ConfigBasedGeneratorFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new ConfigBasedGeneratorFactory();
    }

    /**
     * Test factory starts with no registered generators
     */
    public function testFactoryStartsEmpty(): void
    {
        $this->assertEquals([], $this->factory->getRegisteredTypes());
        $this->assertFalse($this->factory->has('anything'));
    }

    /**
     * Test registering a generator
     */
    public function testRegisterGenerator(): void
    {
        $generator = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('test_type', function () use ($generator) {
            return $generator;
        });

        $this->assertTrue($this->factory->has('test_type'));
    }

    /**
     * Test registering multiple generators
     */
    public function testRegisterMultipleGenerators(): void
    {
        $generator1 = $this->createStub(AltTextGeneratorInterface::class);
        $generator2 = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('openai', fn() => $generator1);
        $this->factory->register('anthropic', fn() => $generator2);

        $this->assertTrue($this->factory->has('openai'));
        $this->assertTrue($this->factory->has('anthropic'));
        $this->assertEquals(['openai', 'anthropic'], $this->factory->getRegisteredTypes());
    }

    /**
     * Test create returns correct generator
     */
    public function testCreateReturnsGenerator(): void
    {
        $generator = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('test', function () use ($generator) {
            return $generator;
        });

        $result = $this->factory->create('test');

        $this->assertSame($generator, $result);
    }

    /**
     * Test create invokes callable each time
     */
    public function testCreateInvokesCallableEachTime(): void
    {
        $callCount = 0;

        $this->factory->register('test', function () use (&$callCount) {
            $callCount++;
            return $this->createStub(AltTextGeneratorInterface::class);
        });

        $this->factory->create('test');
        $this->factory->create('test');
        $this->factory->create('test');

        $this->assertEquals(3, $callCount);
    }

    /**
     * Test create throws exception for unregistered type
     */
    public function testCreateThrowsExceptionForUnregisteredType(): void
    {
        $this->expectException(UnsupportedGeneratorException::class);
        $this->expectExceptionMessage('unknown_type');

        $this->factory->create('unknown_type');
    }

    /**
     * Test has returns false for unregistered type
     */
    public function testHasReturnsFalseForUnregisteredType(): void
    {
        $this->assertFalse($this->factory->has('nonexistent'));
    }

    /**
     * Test re-registering same type overwrites previous
     */
    public function testReRegisterOverwritesPrevious(): void
    {
        $generator1 = $this->createStub(AltTextGeneratorInterface::class);
        $generator2 = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('test', fn() => $generator1);
        $this->factory->register('test', fn() => $generator2);

        $result = $this->factory->create('test');

        $this->assertSame($generator2, $result);
    }

    /**
     * Test getRegisteredTypes returns empty array when nothing registered
     */
    public function testGetRegisteredTypesReturnsEmptyArrayWhenEmpty(): void
    {
        $this->assertEquals([], $this->factory->getRegisteredTypes());
    }

    /**
     * Test type comparison is case sensitive
     */
    public function testTypeComparisonIsCaseSensitive(): void
    {
        $generator = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('OpenAI', fn() => $generator);

        $this->assertTrue($this->factory->has('OpenAI'));
        $this->assertFalse($this->factory->has('openai'));
        $this->assertFalse($this->factory->has('OPENAI'));
    }

    /**
     * Test registering with empty string type
     */
    public function testRegisterWithEmptyStringType(): void
    {
        $generator = $this->createStub(AltTextGeneratorInterface::class);

        $this->factory->register('', fn() => $generator);

        $this->assertTrue($this->factory->has(''));
        $this->assertSame($generator, $this->factory->create(''));
    }

}
