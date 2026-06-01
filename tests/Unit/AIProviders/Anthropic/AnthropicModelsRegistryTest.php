<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Anthropic;

use AATXT\App\AIProviders\Anthropic\AnthropicModelsRegistry;
use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * In-memory cache used to exercise AnthropicModelsRegistry without WordPress.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, ttl: int}> */
    public array $store = [];

    public function get(string $key)
    {
        return $this->store[$key]['value'] ?? null;
    }

    public function set(string $key, $value, int $ttl): void
    {
        $this->store[$key] = ['value' => $value, 'ttl' => $ttl];
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }
}

class AnthropicModelsRegistryTest extends TestCase
{
    private const TEST_API_KEY = 'sk-ant-test-api-key-12345';

    public function testItFetchesFromApiAndCachesResultWhenCacheIsEmpty(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->with(
                Constants::AATXT_ANTHROPIC_MODELS_ENDPOINT,
                $this->callback(function ($headers) {
                    return ($headers['x-api-key'] ?? null) === self::TEST_API_KEY
                        && ($headers['anthropic-version'] ?? null) === Constants::AATXT_API_VERSION;
                })
            )
            ->willReturn([
                'data' => [
                    ['type' => 'model', 'id' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6', 'created_at' => '2026-04-01T00:00:00Z'],
                    ['type' => 'model', 'id' => 'claude-haiku-4-5',  'display_name' => 'Claude Haiku 4.5',  'created_at' => '2025-10-01T00:00:00Z'],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new AnthropicModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $models = $registry->getAvailableModels();

        $this->assertSame(
            ['claude-sonnet-4-6' => 'Claude Sonnet 4.6', 'claude-haiku-4-5' => 'Claude Haiku 4.5'],
            $models
        );
        $this->assertArrayHasKey(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(86400, $cache->store[Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItReturnsCachedListWithoutHittingTheApi(): void
    {
        $cache = new InMemoryCache();
        $cache->set(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY, ['claude-sonnet-4-6' => 'Claude Sonnet 4.6'], 86400);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new AnthropicModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(
            ['claude-sonnet-4-6' => 'Claude Sonnet 4.6'],
            $registry->getAvailableModels()
        );
    }

    public function testItSortsModelsByCreatedAtDescending(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-old',    'display_name' => 'Old',    'created_at' => '2024-01-01T00:00:00Z'],
                ['id' => 'claude-newest', 'display_name' => 'Newest', 'created_at' => '2026-06-01T00:00:00Z'],
                ['id' => 'claude-mid',    'display_name' => 'Mid',    'created_at' => '2025-05-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            ['claude-newest', 'claude-mid', 'claude-old'],
            array_keys($registry->getAvailableModels())
        );
    }

    public function testItFiltersOutLegacyModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-2.1',         'display_name' => 'Claude 2.1',          'created_at' => '2023-11-01T00:00:00Z'],
                ['id' => 'claude-instant-1.2', 'display_name' => 'Claude Instant 1.2',  'created_at' => '2023-08-01T00:00:00Z'],
                ['id' => 'claude-sonnet-4-6',  'display_name' => 'Claude Sonnet 4.6',   'created_at' => '2026-04-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(['claude-sonnet-4-6' => 'Claude Sonnet 4.6'], $registry->getAvailableModels());
    }

    public function testItFallsBackToHardcodedListWhenApiKeyIsMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), '');

        $this->assertFalse($registry->hasApiKey());
        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_ANTHROPIC_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testItFallsBackAndShortCachesOnHttpFailure(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willThrowException(new Exception('boom'));

        $cache = new InMemoryCache();
        $registry = new AnthropicModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_ANTHROPIC_OPTIONS,
            $registry->getAvailableModels()
        );
        $this->assertArrayHasKey(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(3600, $cache->store[Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItFallsBackWhenApiReturnsEmptyList(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn(['data' => []]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_ANTHROPIC_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testIsAvailableReflectsTheAvailableModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6', 'created_at' => '2026-04-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertTrue($registry->isAvailable('claude-sonnet-4-6'));
        $this->assertFalse($registry->isAvailable('claude-3-5-haiku-20241022'));
        $this->assertFalse($registry->isAvailable(''));
    }

    public function testGetDefaultModelReturnsTheMostRecentOne(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-haiku-4-5',  'display_name' => 'Claude Haiku 4.5',  'created_at' => '2025-10-01T00:00:00Z'],
                ['id' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6', 'created_at' => '2026-04-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame('claude-sonnet-4-6', $registry->getDefaultModel());
    }

    public function testFlushCacheClearsBothStoresAndForcesARefetch(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturn([
                'data' => [
                    ['id' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6', 'created_at' => '2026-04-01T00:00:00Z'],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new AnthropicModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $registry->getAvailableModels();
        $this->assertArrayHasKey(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY, $cache->store);

        $registry->flushCache();
        $this->assertArrayNotHasKey(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY, $cache->store);

        $registry->getAvailableModels();
    }

    public function testRetiredClaude4And35IdsAreDetectedAsUnavailable(): void
    {
        // Real-world migration scenario: users upgrading from a plugin version that
        // stored these ids will have them in wp_options, but Anthropic no longer
        // returns them from /v1/models.
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-sonnet-4-6', 'display_name' => 'Claude Sonnet 4.6', 'created_at' => '2026-04-01T00:00:00Z'],
                ['id' => 'claude-haiku-4-5',  'display_name' => 'Claude Haiku 4.5',  'created_at' => '2025-10-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertFalse($registry->isAvailable('claude-sonnet-4-20250514'));
        $this->assertFalse($registry->isAvailable('claude-3-5-haiku-20241022'));
        $this->assertSame('claude-sonnet-4-6', $registry->getDefaultModel());
    }

    public function testItUsesModelIdAsDisplayNameWhenApiOmitsIt(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'claude-future-model', 'created_at' => '2026-12-01T00:00:00Z'],
            ],
        ]);

        $registry = new AnthropicModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            ['claude-future-model' => 'claude-future-model'],
            $registry->getAvailableModels()
        );
    }
}
