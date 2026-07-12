<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit\AIProviders\Gemini;

use AATXT\App\AIProviders\Gemini\GeminiModelsRegistry;
use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * In-memory cache used to exercise GeminiModelsRegistry without WordPress.
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

class GeminiModelsRegistryTest extends TestCase
{
    private const TEST_API_KEY = 'AIza-test-api-key-12345';

    public function testItFetchesFromApiAndCachesResultWhenCacheIsEmpty(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->with(
                $this->stringStartsWith(Constants::AATXT_GEMINI_MODELS_ENDPOINT),
                $this->callback(function ($headers) {
                    return ($headers['x-goog-api-key'] ?? null) === self::TEST_API_KEY;
                })
            )
            ->willReturn([
                'models' => [
                    ['name' => 'models/gemini-3.5-flash', 'displayName' => 'Gemini 3.5 Flash', 'supportedGenerationMethods' => ['generateContent']],
                    ['name' => 'models/gemini-2.5-flash', 'displayName' => 'Gemini 2.5 Flash', 'supportedGenerationMethods' => ['generateContent']],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new GeminiModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $models = $registry->getAvailableModels();

        $this->assertSame(
            ['gemini-3.5-flash' => 'Gemini 3.5 Flash', 'gemini-2.5-flash' => 'Gemini 2.5 Flash'],
            $models
        );
        $this->assertArrayHasKey(Constants::AATXT_GEMINI_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(86400, $cache->store[Constants::AATXT_GEMINI_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItReturnsCachedListWithoutHittingTheApi(): void
    {
        $cache = new InMemoryCache();
        $cache->set(Constants::AATXT_GEMINI_MODELS_CACHE_KEY, ['gemini-3.5-flash' => 'Gemini 3.5 Flash'], 86400);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new GeminiModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(
            ['gemini-3.5-flash' => 'Gemini 3.5 Flash'],
            $registry->getAvailableModels()
        );
    }

    public function testItSortsModelsByFamilyVersionDescending(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-2.5-flash', 'displayName' => 'Gemini 2.5 Flash'],
                ['name' => 'models/gemini-3.5-flash', 'displayName' => 'Gemini 3.5 Flash'],
                ['name' => 'models/gemini-3.0-pro',   'displayName' => 'Gemini 3.0 Pro'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            ['gemini-3.5-flash', 'gemini-3.0-pro', 'gemini-2.5-flash'],
            array_keys($registry->getAvailableModels())
        );
    }

    public function testItFiltersOutSpecialPurposeAndPreviewModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-embedding-001',           'displayName' => 'Gemini Embedding'],
                ['name' => 'models/gemini-2.5-flash-image',         'displayName' => 'Gemini 2.5 Flash Image'],
                ['name' => 'models/gemini-2.5-flash-preview-tts',   'displayName' => 'Gemini 2.5 Flash Preview TTS'],
                ['name' => 'models/gemini-2.5-flash-live',          'displayName' => 'Gemini 2.5 Flash Live'],
                ['name' => 'models/gemini-3-pro-preview',           'displayName' => 'Gemini 3 Pro Preview'],
                ['name' => 'models/gemini-exp-1206',                'displayName' => 'Gemini Experimental'],
                ['name' => 'models/imagen-4.0-generate-001',        'displayName' => 'Imagen 4'],
                ['name' => 'models/gemini-3.5-flash',               'displayName' => 'Gemini 3.5 Flash'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(['gemini-3.5-flash' => 'Gemini 3.5 Flash'], $registry->getAvailableModels());
    }

    public function testItFiltersOutModelsThatCannotGenerateContent(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-3.5-flash', 'displayName' => 'Gemini 3.5 Flash', 'supportedGenerationMethods' => ['generateContent', 'countTokens']],
                ['name' => 'models/gemini-3.5-other', 'displayName' => 'Gemini 3.5 Other', 'supportedGenerationMethods' => ['embedContent']],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(['gemini-3.5-flash' => 'Gemini 3.5 Flash'], $registry->getAvailableModels());
    }

    public function testItFallsBackToHardcodedListWhenApiKeyIsMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('get');

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), '');

        $this->assertFalse($registry->hasApiKey());
        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_GEMINI_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testItFallsBackAndShortCachesOnHttpFailure(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willThrowException(new Exception('boom'));

        $cache = new InMemoryCache();
        $registry = new GeminiModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_GEMINI_OPTIONS,
            $registry->getAvailableModels()
        );
        $this->assertArrayHasKey(Constants::AATXT_GEMINI_MODELS_CACHE_KEY, $cache->store);
        $this->assertSame(3600, $cache->store[Constants::AATXT_GEMINI_MODELS_CACHE_KEY]['ttl']);
    }

    public function testItFallsBackWhenApiReturnsEmptyList(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn(['models' => []]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            Constants::AATXT_OPTION_FIELD_MODEL_GEMINI_OPTIONS,
            $registry->getAvailableModels()
        );
    }

    public function testIsAvailableReflectsTheAvailableModels(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-3.5-flash', 'displayName' => 'Gemini 3.5 Flash'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertTrue($registry->isAvailable('gemini-3.5-flash'));
        $this->assertFalse($registry->isAvailable('gemini-1.5-flash'));
        $this->assertFalse($registry->isAvailable(''));
    }

    public function testGetDefaultModelFavoursTheLeastExpensiveFamily(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-3.5-pro',        'displayName' => 'Gemini 3.5 Pro'],
                ['name' => 'models/gemini-3.5-flash',      'displayName' => 'Gemini 3.5 Flash'],
                ['name' => 'models/gemini-3.5-flash-lite', 'displayName' => 'Gemini 3.5 Flash-Lite'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame('gemini-3.5-flash-lite', $registry->getDefaultModel());
    }

    public function testGetDefaultModelReturnsTheMostRecentOneWhenNoFlashTierExists(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-3.5-pro', 'displayName' => 'Gemini 3.5 Pro'],
                ['name' => 'models/gemini-2.5-pro', 'displayName' => 'Gemini 2.5 Pro'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame('gemini-3.5-pro', $registry->getDefaultModel());
    }

    public function testFlushCacheClearsBothStoresAndForcesARefetch(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturn([
                'models' => [
                    ['name' => 'models/gemini-3.5-flash', 'displayName' => 'Gemini 3.5 Flash'],
                ],
            ]);

        $cache = new InMemoryCache();
        $registry = new GeminiModelsRegistry($httpClient, $cache, self::TEST_API_KEY);

        $registry->getAvailableModels();
        $this->assertArrayHasKey(Constants::AATXT_GEMINI_MODELS_CACHE_KEY, $cache->store);

        $registry->flushCache();
        $this->assertArrayNotHasKey(Constants::AATXT_GEMINI_MODELS_CACHE_KEY, $cache->store);

        $registry->getAvailableModels();
    }

    public function testItUsesModelIdAsDisplayNameWhenApiOmitsIt(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('get')->willReturn([
            'models' => [
                ['name' => 'models/gemini-4.0-future'],
            ],
        ]);

        $registry = new GeminiModelsRegistry($httpClient, new InMemoryCache(), self::TEST_API_KEY);

        $this->assertSame(
            ['gemini-4.0-future' => 'gemini-4.0-future'],
            $registry->getAvailableModels()
        );
    }
}
