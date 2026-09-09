<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Web;

use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;
use App\Services\Agriculture\Intelligence\Normalization\WebResultNormalizer;

/**
 * Abstraction-only / config-driven web search.
 * Without API key + endpoint: NOT_CONFIGURED — never invents results.
 */
final class ConfigurableWebSearchProvider implements WebSearchProviderInterface
{
    public const PROVIDER_ID = 'web_search';

    public function __construct(
        private WebResultNormalizer $normalizer,
    ) {}

    public function providerId(): string
    {
        return self::PROVIDER_ID;
    }

    public function displayName(): string
    {
        return (string) config('agricultural_intelligence.web_search.display_name', 'Web Search');
    }

    public function isConfigured(): bool
    {
        if (! filter_var(config('agricultural_intelligence.web_search.enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }
        $key = trim((string) config('agricultural_intelligence.web_search.api_key', ''));
        $endpoint = trim((string) config('agricultural_intelligence.web_search.endpoint', ''));

        return $key !== '' && $endpoint !== '';
    }

    public function search(string $query, int $limit = 10, array $options = []): WebSearchOutcome
    {
        if (! $this->isConfigured()) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_NOT_CONFIGURED,
                error: 'NOT_CONFIGURED',
                observability: ['reason' => 'missing_key_or_endpoint_or_disabled'],
            );
        }

        if (trim($query) === '') {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_EMPTY,
                error: 'empty_query',
            );
        }

        $endpoint = rtrim((string) config('agricultural_intelligence.web_search.endpoint'), '/');
        $apiKey = (string) config('agricultural_intelligence.web_search.api_key');
        $timeout = max(1, min(60, (int) config('agricultural_intelligence.web_search.timeout', 15)));
        $provider = (string) config('agricultural_intelligence.web_search.provider', 'generic');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($endpoint, [
                    'q' => $query,
                    'query' => $query,
                    'limit' => max(1, min($limit, 20)),
                    'provider' => $provider,
                ]);
        } catch (\Throwable $e) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_FAILED,
                error: 'request_exception',
                observability: ['error_class' => $e::class],
            );
        }

        if (! $response->successful()) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_FAILED,
                error: 'http_'.$response->status(),
                httpStatus: $response->status(),
            );
        }

        $payload = $response->json();
        $hits = [];
        if (is_array($payload)) {
            $hits = $payload['results'] ?? $payload['items'] ?? $payload['organic'] ?? [];
            if (! is_array($hits)) {
                $hits = [];
            }
        }

        $normalized = $this->normalizer->normalizeMany(array_values($hits), $this->providerId());
        if ($normalized === []) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_EMPTY,
                error: 'empty_results',
                httpStatus: $response->status(),
            );
        }

        return new WebSearchOutcome(
            providerId: $this->providerId(),
            status: WebSearchOutcome::STATUS_SUCCESS,
            results: $normalized,
            httpStatus: $response->status(),
            observability: ['result_count' => count($normalized)],
        );
    }
}
