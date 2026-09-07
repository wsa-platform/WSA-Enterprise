<?php

namespace App\Services\Agriculture\Research\Search\Adapters;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stage 3 Semantic Scholar Academic Graph adapter.
 *
 * Optional SEMANTIC_SCHOLAR_API_KEY via x-api-key — never logged or exposed.
 * Missing key: still attempt unauthenticated search (provider rate limits apply).
 * HTTP 429: Retry-After or bounded exponential backoff, max 3 attempts, then graceful failure.
 */
class SemanticScholarScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    public const SOURCE_KEY = 'semantic_scholar';

    private const SEARCH_URL = 'https://api.semanticscholar.org/graph/v1/paper/search';

    private const FIELDS = 'title,abstract,authors,year,url,venue,publicationTypes,citationCount,externalIds,openAccessPdf';

    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private ScientificResultNormalizer $normalizer,
    ) {}

    public function sourceKey(): string
    {
        return self::SOURCE_KEY;
    }

    public function displayName(): string
    {
        return 'Semantic Scholar';
    }

    /**
     * @param  array<string, mixed>  $options  Unused — Semantic Scholar has no Consensus-style filters here.
     */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (trim($query) === '') {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'empty_query',
                observability: $this->observability(status: 'empty', resultCount: 0, retryCount: 0, rateLimited: false),
            );
        }

        $apiKey = trim((string) config('wsa.semantic_scholar_api_key', ''));
        $params = [
            'query' => $query,
            'limit' => max(1, min($limit, 20)),
            'fields' => self::FIELDS,
        ];

        $started = microtime(true);
        $retryCount = 0;
        $rateLimited = false;
        $lastStatus = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $pending = Http::timeout(ScientificHttp::timeoutSeconds())->acceptJson();
                if ($apiKey !== '') {
                    $pending = $pending->withHeaders(['x-api-key' => $apiKey]);
                }
                $response = $pending->get(self::SEARCH_URL, $params);
            } catch (ConnectionException $exception) {
                Log::warning('Semantic Scholar Stage 3 search timed out or connection failed', [
                    'provider' => self::SOURCE_KEY,
                    'message' => $exception->getMessage(),
                ]);

                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'timeout',
                    observability: $this->observability(
                        status: 'failed',
                        resultCount: 0,
                        retryCount: $retryCount,
                        rateLimited: $rateLimited,
                        latencyMs: $this->latencyMs($started),
                        failureReason: 'timeout',
                    ),
                );
            } catch (\Throwable $exception) {
                Log::warning('Semantic Scholar Stage 3 search request failed', [
                    'provider' => self::SOURCE_KEY,
                    'message' => $exception->getMessage(),
                ]);

                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'request_exception',
                    observability: $this->observability(
                        status: 'failed',
                        resultCount: 0,
                        retryCount: $retryCount,
                        rateLimited: $rateLimited,
                        latencyMs: $this->latencyMs($started),
                        failureReason: 'request_exception',
                    ),
                );
            }

            $lastStatus = $response->status();

            if ($lastStatus === 429) {
                $rateLimited = true;
                if ($attempt >= self::MAX_ATTEMPTS) {
                    break;
                }
                $retryCount++;
                ScientificHttp::sleepForRetryAfterOrBackoff($response, $attempt);
                continue;
            }

            if ($lastStatus === 401 || $lastStatus === 403) {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'provider_auth',
                    httpStatus: $lastStatus,
                    observability: $this->observability(
                        status: 'failed',
                        resultCount: 0,
                        retryCount: $retryCount,
                        rateLimited: $rateLimited,
                        latencyMs: $this->latencyMs($started),
                        failureReason: 'provider_auth',
                        httpStatus: $lastStatus,
                    ),
                );
            }

            if ($lastStatus >= 500) {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'http_5xx',
                    httpStatus: $lastStatus,
                    observability: $this->observability(
                        status: 'failed',
                        resultCount: 0,
                        retryCount: $retryCount,
                        rateLimited: $rateLimited,
                        latencyMs: $this->latencyMs($started),
                        failureReason: 'http_5xx',
                        httpStatus: $lastStatus,
                    ),
                );
            }

            if (! $response->successful()) {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->sourceKey(),
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'http_error',
                    httpStatus: $lastStatus,
                    observability: $this->observability(
                        status: 'failed',
                        resultCount: 0,
                        retryCount: $retryCount,
                        rateLimited: $rateLimited,
                        latencyMs: $this->latencyMs($started),
                        failureReason: 'http_error',
                        httpStatus: $lastStatus,
                    ),
                );
            }

            return $this->normalizeSuccessfulResponse($response, $started, $retryCount, $rateLimited);
        }

        Log::warning('Semantic Scholar Stage 3 rate limited after retries', [
            'provider' => self::SOURCE_KEY,
            'retry_count' => $retryCount,
            'http_status' => 429,
        ]);

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            error: 'rate_limited',
            httpStatus: 429,
            observability: $this->observability(
                status: 'unavailable',
                resultCount: 0,
                retryCount: $retryCount,
                rateLimited: true,
                latencyMs: $this->latencyMs($started),
                failureReason: 'rate_limited',
                httpStatus: $lastStatus ?? 429,
            ),
        );
    }

    private function normalizeSuccessfulResponse(
        Response $response,
        float $started,
        int $retryCount,
        bool $rateLimited,
    ): ScientificSourceSearchOutcome {
        $papers = $response->json('data');
        if (! is_array($papers) || $papers === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                observability: $this->observability(
                    status: 'empty',
                    resultCount: 0,
                    retryCount: $retryCount,
                    rateLimited: $rateLimited,
                    latencyMs: $this->latencyMs($started),
                    httpStatus: $response->status(),
                ),
            );
        }

        $results = [];
        foreach ($papers as $paper) {
            if (! is_array($paper)) {
                continue;
            }
            $normalized = $this->normalizer->fromSemanticScholarWork($paper);
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
                    status: 'empty',
                    resultCount: 0,
                    retryCount: $retryCount,
                    rateLimited: $rateLimited,
                    latencyMs: $this->latencyMs($started),
                    failureReason: 'malformed_or_unusable_results',
                    httpStatus: $response->status(),
                ),
            );
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: $results,
            observability: $this->observability(
                status: 'success',
                resultCount: count($results),
                retryCount: $retryCount,
                rateLimited: $rateLimited,
                latencyMs: $this->latencyMs($started),
                httpStatus: $response->status(),
            ),
        );
    }

    /**
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
    ): array {
        $meta = [
            'provider' => self::SOURCE_KEY,
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

        return $meta;
    }

    private function latencyMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
