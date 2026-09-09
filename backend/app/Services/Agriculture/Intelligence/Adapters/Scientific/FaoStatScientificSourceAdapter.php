<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FAOSTAT API adapter via ScientificSourceAdapterInterface.
 * Base: https://fenixservices.fao.org/faostat/api/v1
 * Never fabricates FAO codes; skips invalid lookups; failure-isolated.
 */
final class FaoStatScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    public const SOURCE_KEY = 'fao_stat';

    public function __construct(
        private FaoStatResultNormalizer $faoNormalizer,
    ) {}

    public function sourceKey(): string
    {
        return self::SOURCE_KEY;
    }

    public function displayName(): string
    {
        return 'FAO / FAOSTAT';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (! filter_var(config('agricultural_intelligence.fao.enabled', false), FILTER_VALIDATE_BOOL)) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: 'disabled',
                observability: ['reason' => 'FAO_ENABLED=false'],
            );
        }

        if (trim($query) === '') {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'empty_query',
            );
        }

        $code = $this->resolveItemCode($options);
        if ($code === null) {
            // No fabricated codes — skip invalid / unknown lookups.
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'no_valid_fao_code',
                observability: [
                    'skipped_invalid_lookup' => true,
                    'note' => 'FAO codes must be supplied via options.item_code; never invented',
                ],
            );
        }

        $base = rtrim((string) config(
            'agricultural_intelligence.fao.base_url',
            'https://fenixservices.fao.org/faostat/api/v1',
        ), '/');
        $timeout = max(1, min(60, (int) config('agricultural_intelligence.fao.timeout', 15)));
        $lang = (string) ($options['lang'] ?? config('agricultural_intelligence.fao.lang', 'en'));
        $url = $base.'/'.$lang.'/data/QCL';

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'item' => $code,
                    'page_size' => max(1, min($limit, 20)),
                ]);
        } catch (\Throwable $e) {
            Log::warning('FAOSTAT search failed', [
                'provider' => self::SOURCE_KEY,
                'message' => $e->getMessage(),
            ]);

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'request_exception',
                observability: ['error_class' => $e::class],
            );
        }

        if ($response->status() >= 500) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_'.$response->status(),
                httpStatus: $response->status(),
            );
        }

        if (! $response->successful()) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_'.$response->status(),
                httpStatus: $response->status(),
            );
        }

        $payload = $response->json();
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $results = [];
        foreach (array_slice($rows, 0, max(1, min($limit, 20))) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized = $this->faoNormalizer->toScientificSearchResult($row, $query);
            if ($normalized !== null) {
                $results[] = $normalized;
            }
        }

        if ($results === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'empty_results',
                httpStatus: $response->status(),
            );
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: $results,
            httpStatus: $response->status(),
            observability: ['result_count' => count($results), 'item_code' => $code],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveItemCode(array $options): ?string
    {
        $code = trim((string) ($options['item_code'] ?? $options['fao_item_code'] ?? ''));
        if ($code === '' || ! preg_match('/^\d{1,8}$/', $code)) {
            return null;
        }

        return $code;
    }
}
