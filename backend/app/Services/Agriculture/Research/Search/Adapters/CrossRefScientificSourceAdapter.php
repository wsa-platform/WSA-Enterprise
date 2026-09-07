<?php

namespace App\Services\Agriculture\Research\Search\Adapters;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrossRefScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    /**
     * Crossref relevance often places on-topic national inventory works past the first page.
     * Fetch a deeper page than the Stage-3 caller limit so normalization can keep those hits.
     */
    private const MIN_FETCH_ROWS = 50;

    private const MAX_FETCH_ROWS = 100;

    public function __construct(
        private ScientificResultNormalizer $normalizer,
    ) {}

    public function sourceKey(): string
    {
        return 'crossref';
    }

    public function displayName(): string
    {
        return 'Crossref';
    }

    /**
     * @param  array<string, mixed>  $options  Unused — Crossref has no Consensus-style domain/country filters here.
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

        try {
            $response = Http::timeout(ScientificHttp::timeoutSeconds())
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => (string) config('wsa.crossref_mailto', 'wsa-platform/1.0 (mailto:wsa-platform@example.com)'),
                ])
                ->get('https://api.crossref.org/works', [
                    'query' => $query,
                    'rows' => $this->fetchRows($limit),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Crossref Stage 3 search request failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'request_exception',
            );
        }

        if ($response->status() === 429) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: 'rate_limited',
                httpStatus: 429,
            );
        }

        if (! $response->successful()) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_error',
                httpStatus: $response->status(),
            );
        }

        $items = $response->json('message.items');
        if (! is_array($items) || $items === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
            );
        }

        $results = [];
        foreach ($items as $work) {
            if (! is_array($work)) {
                continue;
            }
            $normalized = $this->normalizer->fromCrossRefWork($work);
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

    /**
     * Country-agnostic fetch depth: at least MIN_FETCH_ROWS, capped at MAX_FETCH_ROWS.
     */
    private function fetchRows(int $limit): int
    {
        return max(1, min(max($limit, self::MIN_FETCH_ROWS), self::MAX_FETCH_ROWS));
    }
}
