<?php

namespace App\Services\Agriculture\Research\Search\Adapters;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAlexScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    public function __construct(
        private ScientificResultNormalizer $normalizer,
    ) {}

    public function sourceKey(): string
    {
        return 'openalex';
    }

    public function displayName(): string
    {
        return 'OpenAlex';
    }

    /**
     * @param  array<string, mixed>  $options  Unused — OpenAlex has no Consensus-style domain/country filters here.
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
                ->get('https://api.openalex.org/works', [
                    'search' => $query,
                    'per_page' => max(1, min($limit, 10)),
                    'mailto' => (string) config('wsa.openalex_mailto', 'wsa-platform@example.com'),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('OpenAlex Stage 3 search request failed', [
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
            $normalized = $this->normalizer->fromOpenAlexWork($work);
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
