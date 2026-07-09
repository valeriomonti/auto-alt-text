<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\OpenAI;

use AATXT\App\AIProviders\OpenAI\OpenAIModelsRegistry;
use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * In-memory cache used to exercise OpenAIModelsRegistry without WordPress.
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

class OpenAIModelsRegistryTest extends TestCase
{
    private const TEST_API_KEY = 'sk-test-api-key-12345';

    public function testItFetchesFromApiAndCachesResultWhenCacheIsEmpty(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->with(
                Constants::AATXT_OPENAI_MODELS_ENDPOINT,
                $this->callback(function ($headers) {
                    return ($headers['Authorization'] ?? null) === 'Bearer ' . self::TEST_API_KEY;
                })
            )
            ->willReturn([
                'data' => [
                    ['object' => 'model', 'id' => 'gpt-5',      'created' => 1754000000, 'owned_by' => 'openai'],
                    ['object' => 'model', 'id' => 'gpt-5-nano', 'created' => 1753999999, 'owned_by' => 'openai'],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new OpenAIModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $models = $registry->getAvailableModels();

        $this->assertSame(
            ['gpt-5' => 'GPT-5', 'gpt-5-nano' => 'GPT-5 Nano'],
            $models
        );
        $this->assertArrayHasKey(Constants::AATXT_OPENAI_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(86400, $cache->store[Constants::AATXT_OPENAI_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItReturnsCachedListWithoutHittingTheApi(): void
    {
        $cache = new InMemoryCache();
        $cache->set(Constants::AATXT_OPENAI_MODELS_CACHE_KEY, ['gpt-5' => 'GPT-5'], 86400);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new OpenAIModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(['gpt-5' => 'GPT-5'], $registry->getAvailableModels());
    }

    public function testItSortsModelsByCreatedDescending(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-4o',     'created' => 1715367049],
                ['id' => 'gpt-5',      'created' => 1754425777],
                ['id' => 'gpt-4o-mini','created' => 1721172741],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            ['gpt-5', 'gpt-4o-mini', 'gpt-4o'],
            array_keys($registry->getAvailableModels())
        );
    }

    public function testItFiltersOutNonVisionAndSpecialPurposeModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5',                       'created' => 1754425777],
                ['id' => 'gpt-4o-audio-preview',        'created' => 1727389042],
                ['id' => 'gpt-4o-realtime-preview',     'created' => 1727659998],
                ['id' => 'gpt-4o-mini-tts',             'created' => 1742403959],
                ['id' => 'gpt-4o-transcribe',           'created' => 1742068463],
                ['id' => 'gpt-4o-mini-search-preview',  'created' => 1741391161],
                ['id' => 'gpt-5-codex',                 'created' => 1757527818],
                ['id' => 'gpt-image-1',                 'created' => 1745517030],
                ['id' => 'chatgpt-4o-latest',           'created' => 1723515131],
                ['id' => 'dall-e-3',                    'created' => 1698785189],
                ['id' => 'whisper-1',                   'created' => 1677532384],
                ['id' => 'text-embedding-3-small',      'created' => 1705948997],
                ['id' => 'omni-moderation-latest',      'created' => 1731689265],
                ['id' => 'gpt-3.5-turbo',               'created' => 1677610602],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(['gpt-5' => 'GPT-5'], $registry->getAvailableModels());
    }

    public function testItFiltersOutDatedSnapshots(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-4o',            'created' => 1715367049],
                ['id' => 'gpt-4o-2024-08-06', 'created' => 1722814719],
                ['id' => 'gpt-4.1-2025-04-14','created' => 1744315746],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(['gpt-4o' => 'GPT-4o'], $registry->getAvailableModels());
    }

    public function testItFallsBackToHardcodedListWhenApiKeyIsMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), '');

        $this->assertFalse($registry->hasApiKey());
        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testItFallsBackAndShortCachesOnHttpFailure(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willThrowException(new Exception('boom'));

        $cache = new InMemoryCache();
        $registry = new OpenAIModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS,
            $registry->getAvailableModels()
        );
        $this->assertArrayHasKey(Constants::AATXT_OPENAI_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(3600, $cache->store[Constants::AATXT_OPENAI_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItFallsBackWhenApiReturnsEmptyList(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn(['data' => []]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testIsAvailableReflectsTheAvailableModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5', 'created' => 1754425777],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertTrue($registry->isAvailable('gpt-5'));
        $this->assertFalse($registry->isAvailable('gpt-4o'));
        $this->assertFalse($registry->isAvailable(''));
    }

    public function testGetDefaultModelPrefersTheMostRecentNanoModel(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5',       'created' => 1754425777],
                ['id' => 'gpt-5-nano',  'created' => 1754425776],
                ['id' => 'gpt-4.1-nano','created' => 1744321025],
                ['id' => 'gpt-4o-mini', 'created' => 1721172741],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame('gpt-5-nano', $registry->getDefaultModel());
    }

    public function testGetDefaultModelFallsBackToMiniThenMostRecent(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5',       'created' => 1754425777],
                ['id' => 'gpt-4o-mini', 'created' => 1721172741],
                ['id' => 'gpt-4o',      'created' => 1715367049],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);
        $this->assertSame('gpt-4o-mini', $registry->getDefaultModel());

        $httpClientNoCheap = $this->createStub(HttpClientInterface::class);
        $httpClientNoCheap->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5',  'created' => 1754425777],
                ['id' => 'gpt-4o', 'created' => 1715367049],
            ],
        ]);

        $registryNoCheap = new OpenAIModelsRegistry($httpClientNoCheap, new InMemoryCache(), self::TEST_API_KEY);
        $this->assertSame('gpt-5', $registryNoCheap->getDefaultModel());
    }

    public function testFlushCacheClearsBothStoresAndForcesARefetch(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturn([
                'data' => [
                    ['id' => 'gpt-5', 'created' => 1754425777],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new OpenAIModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $registry->getAvailableModels();
        $this->assertArrayHasKey(Constants::AATXT_OPENAI_MODELS_CACHE_KEY, $cache->store);

        $registry->flushCache();
        $this->assertArrayNotHasKey(Constants::AATXT_OPENAI_MODELS_CACHE_KEY, $cache->store);

        $registry->getAvailableModels();
    }

    public function testItBuildsHumanReadableDisplayNames(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'data' => [
                ['id' => 'gpt-5-mini',  'created' => 1754425867],
                ['id' => 'gpt-4.1',     'created' => 1744316542],
                ['id' => 'gpt-4o-mini', 'created' => 1721172741],
            ],
        ]);

        $registry = new OpenAIModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            [
                'gpt-5-mini' => 'GPT-5 Mini',
                'gpt-4.1' => 'GPT-4.1',
                'gpt-4o-mini' => 'GPT-4o Mini',
            ],
            $registry->getAvailableModels()
        );
    }
}
