<?php

namespace App\Services\Agriculture\Research\Search\Adapters;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAlexScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    private const MAX_ATTEMPTS = 3;

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
                observability: $this->observability('empty', 0, 0, false),
            );
        }

        $params = [
            'search' => $query,
            'per_page' => max(1, min($limit, 10)),
            'mailto' => (string) config('wsa.openalex_mailto', 'wsa-platform@example.com'),
        ];
        $apiKey = trim((string) config('wsa.openalex_api_key', ''));
        if ($apiKey !== '') {
            $params['api_key'] = $apiKey;
        }

        $started = microtime(true);
        $retryCount = 0;
        $rateLimited = false;
        $lastStatus = null;
        $rateHeaders = [];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(ScientificHttp::timeoutSeconds())
                    ->acceptJson()
                    ->get('https://api.openalex.org/works', $params);
            } catch (\Throwable $exception) {
                Log::warning('OpenAlex Stage 3 search request failed', [
                    'provider' => 'openalex',
                    'message' => $exception->getMessage(),
                ]);

                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'request_exception',
                    observability: $this->observability(
                        'failed',
                        0,
                        $retryCount,
                        $rateLimited,
                        (int) round((microtime(true) - $started) * 1000),
                        'request_exception',
                    ),
                );
            }

            $lastStatus = $response->status();
            $rateHeaders = $this->safeRateHeaders($response);

            if ($lastStatus === 429) {
                $rateLimited = true;
                Log::info('OpenAlex Stage 3 rate limited', array_merge([
                    'provider' => 'openalex',
                    'attempt' => $attempt,
                    'retry_count' => $retryCount,
                ], $rateHeaders));

                if ($attempt >= self::MAX_ATTEMPTS) {
                    break;
                }
                $retryCount++;
                ScientificHttp::sleepForRetryAfterOrBackoff($response, $attempt);

                continue;
            }

            if (! $response->successful()) {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'http_error',
                    httpStatus: $lastStatus,
                    observability: $this->observability(
                        'failed',
                        0,
                        $retryCount,
                        $rateLimited,
                        (int) round((microtime(true) - $started) * 1000),
                        'http_error',
                        $lastStatus,
                        $rateHeaders,
                    ),
                );
            }

            $works = $response->json('results');
            if (! is_array($works) || $works === []) {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                    observability: $this->observability(
                        'empty',
                        0,
                        $retryCount,
                        $rateLimited,
                        (int) round((microtime(true) - $started) * 1000),
                        null,
                        $lastStatus,
                        $rateHeaders,
                    ),
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
                    observability: $this->observability(
                        'empty',
                        0,
                        $retryCount,
                        $rateLimited,
                        (int) round((microtime(true) - $started) * 1000),
                        'malformed_or_unusable_results',
                        $lastStatus,
                        $rateHeaders,
                    ),
                );
            }

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
                results: $results,
                observability: $this->observability(
                    'success',
                    count($results),
                    $retryCount,
                    $rateLimited,
                    (int) round((microtime(true) - $started) * 1000),
                    null,
                    $lastStatus,
                    $rateHeaders,
                ),
            );
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            error: 'rate_limited',
            httpStatus: 429,
            observability: $this->observability(
                'unavailable',
                0,
                $retryCount,
                true,
                (int) round((microtime(true) - $started) * 1000),
                'rate_limited',
                $lastStatus ?? 429,
                $rateHeaders,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function safeRateHeaders(Response $response): array
    {
        $out = [];
        foreach ([
            'x-ratelimit-remaining',
            'x-ratelimit-reset',
            'ratelimit-remaining',
            'ratelimit-reset',
            'retry-after',
        ] as $header) {
            $value = $response->header($header);
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }
            if (is_string($value) && trim($value) !== '') {
                $out[$header] = trim($value);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $rateHeaders
     * @return array<string, mixed>
     */
    private function observability(
        string $status,
        int $resultCount,
        int $retryCount,
        bool $rateLimited,
        ?int $latencyMs = null,
        ?string $failureReason = null,
        ?int $httpStatus = null,
        array $rateHeaders = [],
    ): array {
        $meta = [
            'provider' => 'openalex',
            'status' => $status,
            'result_count' => $resultCount,
            'retry_count' => $retryCount,
            'rate_limited' => $rateLimited,
            'fallback_used' => false,
        ];
        if ($latencyMs !== null) {
            $meta['latency_ms'] = $latencyMs;
        }
        if ($failureReason !== null) {
            $meta['failure_reason_category'] = $failureReason;
        }
        if ($httpStatus !== null) {
            $meta['http_status'] = $httpStatus;
        }
        if ($rateHeaders !== []) {
            $meta['rate_limit'] = $rateHeaders;
        }

        return $meta;
    }
}
