<?php

declare(strict_types=1);

namespace AATXT\App\AIProviders\Gemini;

use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;

/**
 * Registry that exposes the list of Gemini models currently available,
 * fetched from the public /v1beta/models endpoint and cached via the
 * injected cache backend.
 *
 * The endpoint returns every model exposed by the Gemini API (embeddings,
 * TTS, image generation, ...), so the list is filtered down to the
 * vision-capable "gemini-*" text generation models, excluding special-purpose
 * variants, experimental builds and preview snapshots.
 *
 * On any failure (missing API key, HTTP error, empty/malformed response) it
 * falls back to the static list in Constants so the plugin keeps working.
 */
class GeminiModelsRegistry
{
    /**
     * Cache TTL for a successful response (24 hours).
     */
    private const CACHE_TTL_SUCCESS = 86400;

    /**
     * Cache TTL applied to the fallback list after a fetch failure (1 hour),
     * so we do not hammer the API while Google is unreachable.
     */
    private const CACHE_TTL_FALLBACK = 3600;

    /**
     * Vision-capable Gemini text generation model families.
     */
    private const INCLUDED_FAMILIES_PATTERN = '/^gemini-\d/i';

    /**
     * Special-purpose variants that cannot generate alt text from an image
     * (embeddings, TTS, audio/live dialog, image generation), plus
     * experimental and preview builds.
     */
    private const EXCLUDED_VARIANTS_PATTERN = '/(embedding|image|imagen|tts|audio|live|dialog|thinking|exp|preview)/i';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string Decrypted Gemini API key, possibly empty.
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

        $cached = $this->cache->get(Constants::AATXT_GEMINI_MODELS_CACHE_KEY);
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
                Constants::AATXT_GEMINI_MODELS_CACHE_KEY,
                $fallback,
                self::CACHE_TTL_FALLBACK
            );
            return $this->memoizedModels = $fallback;
        }

        if (empty($models)) {
            return $this->memoizedModels = $this->fallbackList();
        }

        $this->cache->set(
            Constants::AATXT_GEMINI_MODELS_CACHE_KEY,
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
     * Return the model id used as runtime fallback when the configured model is
     * no longer available, favouring the least expensive one.
     *
     * Google does not expose pricing through the API, so the model family is
     * used as a proxy: "flash-lite" variants are cheaper than "flash" variants,
     * which are cheaper than the "pro" models. Within the same family the most
     * recent model wins (the list is sorted newest-first). When no
     * "flash-lite"/"flash" tier is available the most recent model is returned
     * as a last resort.
     */
    public function getDefaultModel(): string
    {
        $models = $this->getAvailableModels();
        if (empty($models)) {
            return Constants::AATXT_GEMINI_FALLBACK_MODEL;
        }

        foreach (['flash-lite', 'flash'] as $family) {
            foreach (array_keys($models) as $modelId) {
                if (strpos($modelId, '-' . $family) !== false) {
                    return (string) $modelId;
                }
            }
        }

        // No "flash" tier is available: as a last resort return the most
        // recent model (the list is sorted newest-first).
        return (string) array_key_first($models);
    }

    public function flushCache(): void
    {
        $this->cache->delete(Constants::AATXT_GEMINI_MODELS_CACHE_KEY);
        $this->memoizedModels = null;
    }

    /**
     * @return array<string,string>
     * @throws Exception
     */
    private function fetchFromApi(): array
    {
        $headers = [
            'x-goog-api-key' => $this->apiKey,
        ];

        // The endpoint is paginated (default page size 50): request a large
        // page to get the whole catalog in a single call.
        $data = $this->httpClient->get(Constants::AATXT_GEMINI_MODELS_ENDPOINT . '?pageSize=1000', $headers);
        $items = isset($data['models']) && is_array($data['models']) ? $data['models'] : [];

        $items = array_values(array_filter($items, function ($item) {
            if (!is_array($item)) {
                return false;
            }
            $id = $this->modelId($item);
            if ($id === '') {
                return false;
            }
            if (!preg_match(self::INCLUDED_FAMILIES_PATTERN, $id)
                || preg_match(self::EXCLUDED_VARIANTS_PATTERN, $id)) {
                return false;
            }
            // When the API declares the supported generation methods, keep only
            // the models able to generate content from a prompt.
            if (isset($item['supportedGenerationMethods']) && is_array($item['supportedGenerationMethods'])) {
                return in_array('generateContent', $item['supportedGenerationMethods'], true);
            }
            return true;
        }));

        // The endpoint does not expose a creation date: sort by the version
        // number embedded in the id (e.g. "gemini-3.5-flash" => 3.5) so the
        // most recent family comes first.
        usort($items, function ($a, $b) {
            return $this->modelVersion($this->modelId($b)) <=> $this->modelVersion($this->modelId($a));
        });

        $result = [];
        foreach ($items as $item) {
            $id = $this->modelId($item);
            $display = isset($item['displayName']) && $item['displayName'] !== ''
                ? (string) $item['displayName']
                : $id;
            $result[$id] = $display;
        }

        return $result;
    }

    /**
     * Extract the bare model id from an API item, stripping the
     * "models/" resource prefix (e.g. "models/gemini-3.5-flash").
     *
     * @param array<string, mixed> $item
     */
    private function modelId(array $item): string
    {
        $name = isset($item['name']) ? (string) $item['name'] : '';
        if ($name === '') {
            return '';
        }
        return strpos($name, 'models/') === 0 ? substr($name, strlen('models/')) : $name;
    }

    /**
     * Extract the family version number from a model id
     * (e.g. "gemini-3.5-flash" => 3.5). Unknown formats sort last.
     */
    private function modelVersion(string $modelId): float
    {
        if (preg_match('/^gemini-(\d+(?:\.\d+)?)/i', $modelId, $matches)) {
            return (float) $matches[1];
        }
        return 0.0;
    }

    /**
     * @return array<string,string>
     */
    private function fallbackList(): array
    {
        return Constants::AATXT_OPTION_FIELD_MODEL_GEMINI_OPTIONS;
    }
}
