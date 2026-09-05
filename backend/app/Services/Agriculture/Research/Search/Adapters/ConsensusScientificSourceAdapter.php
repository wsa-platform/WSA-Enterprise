<?php

namespace App\Services\Agriculture\Research\Search\Adapters;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stage 3 Consensus.app scientific search adapter.
 *
 * Missing CONSENSUS_API_KEY → unavailable (OpenAlex/Crossref continue).
 * Never logs or returns the API key.
 */
class ConsensusScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    public const SOURCE_KEY = 'consensus';

    public function __construct(
        private ScientificResultNormalizer $normalizer,
    ) {}

    public function sourceKey(): string
    {
        return self::SOURCE_KEY;
    }

    public function displayName(): string
    {
        return 'Consensus';
    }

    /**
     * @param  array<string, mixed>  $options  Optional Consensus filters: domain, country, include_semantic_score
     */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (trim($query) === '') {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'empty_query',
            );
        }

        $apiKey = trim((string) config('wsa.consensus_api_key', ''));
        if ($apiKey === '') {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: 'missing_api_key',
            );
        }

        $baseUrl = rtrim((string) config('wsa.consensus_base_url', 'https://api.consensus.app'), '/');
        $params = [
            'query' => $query,
            'page_size' => max(1, min($limit, 20)),
            'include_semantic_score' => (bool) ($options['include_semantic_score'] ?? true),
        ];

        $domain = trim((string) ($options['domain'] ?? ''));
        if ($domain !== '') {
            // Academic field short code (e.g. agri) — never a publisher or web domain.
            $params['domain'] = $domain;
        }

        $country = trim((string) ($options['country'] ?? ''));
        if ($country !== '') {
            // ISO 3166-1 alpha-2 study-country filter — never publisher geo.
            $params['country'] = strtolower($country);
        }

        try {
            $response = Http::timeout(ScientificHttp::timeoutSeconds())
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                ])
                ->get($baseUrl.'/v1/search', $params);
        } catch (ConnectionException $exception) {
            Log::warning('Consensus Stage 3 search timed out or connection failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'timeout',
            );
        } catch (\Throwable $exception) {
            Log::warning('Consensus Stage 3 search request failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'request_exception',
            );
        }

        $status = $response->status();

        if ($status === 401 || $status === 402) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'provider_auth_or_billing',
                httpStatus: $status,
            );
        }

        if ($status === 429) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: 'rate_limited',
                httpStatus: 429,
            );
        }

        if ($status >= 500) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_5xx',
                httpStatus: $status,
            );
        }

        if (! $response->successful()) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_error',
                httpStatus: $status,
            );
        }

        $works = $response->json('results');
        if (! is_array($works) || $works === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
            );
        }

        $results = [];
        foreach ($works as $work) {
            if (! is_array($work)) {
                continue;
            }
            $normalized = $this->normalizer->fromConsensusWork($work);
            if ($normalized !== null) {
                $results[] = $normalized;
            }
        }

        if ($results === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'malformed_or_unusable_results',
            );
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: $results,
        );
    }
}
