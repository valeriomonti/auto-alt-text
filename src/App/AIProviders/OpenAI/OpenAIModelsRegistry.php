<?php

declare(strict_types=1);

namespace AATXT\App\AIProviders\OpenAI;

use AATXT\App\Infrastructure\Cache\CacheInterface;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\Config\Constants;
use Exception;

/**
 * Registry that exposes the list of OpenAI models currently available on the
 * user's account, fetched from the public /v1/models endpoint and cached
 * via the injected cache backend.
 *
 * The endpoint returns every model on the account (embeddings, TTS, Whisper,
 * DALL-E, ...), so the list is filtered down to the vision-capable chat
 * families usable with the Responses API (gpt-4o, gpt-4.x, gpt-5), excluding
 * special-purpose variants and dated snapshots.
 *
 * On any failure (missing API key, HTTP error, empty/malformed response) it
 * falls back to the static list in Constants so the plugin keeps working.
 */
class OpenAIModelsRegistry
{
    /**
     * Cache TTL for a successful response (24 hours).
     */
    private const CACHE_TTL_SUCCESS = 86400;

    /**
     * Cache TTL applied to the fallback list after a fetch failure (1 hour),
     * so we do not hammer the API while OpenAI is unreachable.
     */
    private const CACHE_TTL_FALLBACK = 3600;

    /**
     * Vision-capable chat model families compatible with the Responses API.
     */
    private const INCLUDED_FAMILIES_PATTERN = '/^gpt-(4o|4\.\d|5)/i';

    /**
     * Special-purpose variants that cannot generate alt text from an image,
     * plus dated snapshots and preview builds (their alias is already listed).
     */
    private const EXCLUDED_VARIANTS_PATTERN = '/(audio|realtime|search|transcribe|tts|instruct|image|moderation|embedding|codex|preview)|-\d{4}-\d{2}-\d{2}$/i';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string Decrypted OpenAI API key, possibly empty.
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

        $cached = $this->cache->get(Constants::AATXT_OPENAI_MODELS_CACHE_KEY);
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
                Constants::AATXT_OPENAI_MODELS_CACHE_KEY,
                $fallback,
                self::CACHE_TTL_FALLBACK
            );
            return $this->memoizedModels = $fallback;
        }

        if (empty($models)) {
            return $this->memoizedModels = $this->fallbackList();
        }

        $this->cache->set(
            Constants::AATXT_OPENAI_MODELS_CACHE_KEY,
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
     * OpenAI does not expose pricing through the API, so the model family is
     * used as a proxy: "nano" variants are cheaper than "mini" variants, which
     * are cheaper than the full-size models. Within the same family the most
     * recent model wins. When no "nano"/"mini" tier is available the most recent
     * full-size model is returned as a last resort.
     */
    public function getDefaultModel(): string
    {
        $models = $this->getAvailableModels();
        if (empty($models)) {
            return Constants::AATXT_OPENAI_FALLBACK_MODEL;
        }

        foreach (['nano', 'mini'] as $family) {
            foreach (array_keys($models) as $modelId) {
                if (strpos($modelId, '-' . $family) !== false) {
                    return (string) $modelId;
                }
            }
        }

        // No "nano"/"mini" tier is available: as a last resort return the most
        // recent model (the list is sorted newest-first).
        return (string) array_key_first($models);
    }

    public function flushCache(): void
    {
        $this->cache->delete(Constants::AATXT_OPENAI_MODELS_CACHE_KEY);
        $this->memoizedModels = null;
    }

    /**
     * @return array<string,string>
     * @throws Exception
     */
    private function fetchFromApi(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        $data = $this->httpClient->get(Constants::AATXT_OPENAI_MODELS_ENDPOINT, $headers);
        $items = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

        $items = array_values(array_filter($items, function ($item) {
            if (!is_array($item)) {
                return false;
            }
            $id = isset($item['id']) ? (string) $item['id'] : '';
            if ($id === '') {
                return false;
            }
            return preg_match(self::INCLUDED_FAMILIES_PATTERN, $id)
                && !preg_match(self::EXCLUDED_VARIANTS_PATTERN, $id);
        }));

        usort($items, function ($a, $b) {
            $aCreated = isset($a['created']) ? (int) $a['created'] : 0;
            $bCreated = isset($b['created']) ? (int) $b['created'] : 0;
            return $bCreated <=> $aCreated;
        });

        $result = [];
        foreach ($items as $item) {
            $id = (string) $item['id'];
            $result[$id] = $this->displayName($id);
        }

        return $result;
    }

    /**
     * Build a human-readable label from a model id, since the OpenAI API does
     * not return display names (e.g. "gpt-4o-mini" => "GPT-4o Mini").
     */
    private function displayName(string $id): string
    {
        $parts = explode('-', $id);
        if (count($parts) < 2 || strtolower($parts[0]) !== 'gpt') {
            return $id;
        }

        $label = 'GPT-' . $parts[1];
        $suffixes = array_map('ucfirst', array_slice($parts, 2));
        if (!empty($suffixes)) {
            $label .= ' ' . implode(' ', $suffixes);
        }

        return $label;
    }

    /**
     * @return array<string,string>
     */
    private function fallbackList(): array
    {
        return Constants::AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS;
    }
}
