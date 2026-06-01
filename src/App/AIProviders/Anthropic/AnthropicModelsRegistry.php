<?php

declare(strict_types=1);

namespace AATXT\App\AIProviders\Anthropic;

use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;

/**
 * Registry that exposes the list of Claude models currently available on the
 * Anthropic account, fetched from the public /v1/models endpoint and cached
 * via the injected cache backend.
 *
 * On any failure (missing API key, HTTP error, empty/malformed response) it
 * falls back to the static list in Constants so the plugin keeps working.
 */
class AnthropicModelsRegistry
{
    /**
     * Cache TTL for a successful response (24 hours).
     */
    private const CACHE_TTL_SUCCESS = 86400;

    /**
     * Cache TTL applied to the fallback list after a fetch failure (1 hour),
     * so we do not hammer the API while Anthropic is unreachable.
     */
    private const CACHE_TTL_FALLBACK = 3600;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string Decrypted Anthropic API key, possibly empty.
     */
    private $apiKey;

    /**
     * @var array<string,string>|null In-request memoization.
     */
    private $memoizedModels = null;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheInterface $cache,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->apiKey = $apiKey;
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Return the available models as `id => display_name`, ordered with the
     * most recent first. Falls back to the static list on any failure.
     *
     * @return array<string,string>
     */
    public function getAvailableModels(): array
    {
        if ($this->memoizedModels !== null) {
            return $this->memoizedModels;
        }

        $cached = $this->cache->get(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY);
        if (is_array($cached)) {
            return $this->memoizedModels = $cached;
        }

        if (!$this->hasApiKey()) {
            return $this->memoizedModels = $this->fallbackList();
        }

        try {
            $models = $this->fetchFromApi();
        } catch (Exception $e) {
            $fallback = $this->fallbackList();
            $this->cache->set(
                Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY,
                $fallback,
                self::CACHE_TTL_FALLBACK
            );
            return $this->memoizedModels = $fallback;
        }

        if (empty($models)) {
            return $this->memoizedModels = $this->fallbackList();
        }

        $this->cache->set(
            Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY,
            $models,
            self::CACHE_TTL_SUCCESS
        );
        return $this->memoizedModels = $models;
    }

    public function isAvailable(string $modelId): bool
    {
        if ($modelId === '') {
            return false;
        }
        return array_key_exists($modelId, $this->getAvailableModels());
    }

    /**
     * Return the most recent model id from the available list, used as
     * runtime fallback when the configured model is no longer available.
     */
    public function getDefaultModel(): string
    {
        $models = $this->getAvailableModels();
        if (empty($models)) {
            return Constants::AATXT_CLAUDE_FALLBACK_MODEL;
        }
        return (string) array_key_first($models);
    }

    public function flushCache(): void
    {
        $this->cache->delete(Constants::AATXT_ANTHROPIC_MODELS_CACHE_KEY);
        $this->memoizedModels = null;
    }

    /**
     * @return array<string,string>
     * @throws Exception
     */
    private function fetchFromApi(): array
    {
        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => Constants::AATXT_API_VERSION,
        ];

        $data = $this->httpClient->get(Constants::AATXT_ANTHROPIC_MODELS_ENDPOINT, $headers);
        $items = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

        $items = array_values(array_filter($items, function ($item) {
            if (!is_array($item)) {
                return false;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ($id === '') {
                return false;
            }
            return !preg_match('/^claude-(2|instant)/i', $id);
        }));

        usort($items, function ($a, $b) {
            $aCreated = isset($a['created_at']) ? (string) $a['created_at'] : '';
            $bCreated = isset($b['created_at']) ? (string) $b['created_at'] : '';
            return strcmp($bCreated, $aCreated);
        });

        $result = [];
        foreach ($items as $item) {
            $id = (string) $item['id'];
            $display = isset($item['display_name']) && $item['display_name'] !== ''
                ? (string) $item['display_name']
                : $id;
            $result[$id] = $display;
        }

        return $result;
    }

    /**
     * @return array<string,string>
     */
    private function fallbackList(): array
    {
        return Constants::AATXT_OPTION_FIELD_MODEL_ANTHROPIC_OPTIONS;
    }
}
