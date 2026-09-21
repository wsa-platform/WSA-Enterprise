<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Log;

/**
 * FAOSTAT Developer Portal Stage 3 adapter. Isolated from the FENIX adapter.
 * Does not invent codes. Does not query /codes/areas/. Does not send page_size.
 */
final class FaoStatDeveloperPortalAdapter implements ScientificSourceAdapterInterface
{
    public const SOURCE_KEY = 'fao_stat';

    public function __construct(
        private FaoStatDeveloperPortalClient $client,
        private FaoStatDeveloperPortalResultNormalizer $normalizer,
        private FaoStatDeveloperPortalTokenManager $tokens,
        private FaoStatReadinessReporter $readiness,
        private FaoStatOperationalLogger $logger,
        private FaoStatDomainScopedCodeResolver $codes,
    ) {}

    public function sourceKey(): string
    {
        return self::SOURCE_KEY;
    }

    public function displayName(): string
    {
        return 'FAO / FAOSTAT';
    }

    public function health(): ProviderHealthStatus
    {
        return $this->readiness->health();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (! $this->isEnabled()) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: FaoStatErrorCategory::DISABLED,
                observability: [
                    'reason' => 'FAOSTAT_ENABLED=false',
                    'readiness' => FaoStatReadinessState::DISABLED,
                    'selected' => false,
                ],
            );
        }

        $domain = strtoupper(trim((string) ($options['domain'] ?? $options['domain_code'] ?? 'QCL')));
        try {
            $this->client->assertDomainAllowed($domain);
            FaoStatActivationPolicy::assertCanActivate($domain);
        } catch (FaoStatPortalException $e) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: $e->category,
                observability: ['domain' => $domain],
            );
        }

        // Resolve dimensions before any HTTP (auth/preflight/data). Incomplete filters must
        // never trigger portal traffic.
        $filters = $this->resolveFilters($domain, $options);
        if (($filters['error'] ?? null) === FaoStatErrorCategory::AMBIGUOUS_CODE) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: FaoStatErrorCategory::AMBIGUOUS_CODE,
                observability: ['reason' => 'ambiguous_label'],
            );
        }

        $area = $filters['area'] ?? null;
        $item = $filters['item'] ?? null;
        $element = $filters['element'] ?? null;
        $year = $filters['year'] ?? null;
        if ($area === null || $item === null || $element === null || $year === null) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: FaoStatErrorCategory::INCOMPLETE_FILTERS,
                observability: [
                    'considered' => true,
                    'forced' => false,
                    'domain' => $domain,
                    'activation_state' => FaoStatActivationPolicy::activationState($domain),
                    'readiness' => FaoStatReadinessState::READY,
                    'missing' => array_keys(array_filter([
                        'area' => $area === null,
                        'item' => $item === null,
                        'element' => $element === null,
                        'year' => $year === null,
                    ])),
                ],
            );
        }

        $preflight = $this->readiness->searchPreflight();
        if ($preflight['ok'] !== true) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: $preflight['error'],
                observability: [
                    'reason' => $preflight['readiness'],
                    'readiness' => $preflight['readiness'],
                    'activation_state' => $preflight['details']['activation_state'] ?? null,
                    'circuit_open' => $preflight['details']['circuit_open'] ?? false,
                    'selected' => true,
                    'considered' => true,
                ],
            );
        }

        $started = hrtime(true);

        $queryUrl = $this->reconstructQueryUrl($domain, $area, $item, $element, $year);

        try {
            $result = $this->client->getData($domain, [
                'area' => $area,
                'item' => $item,
                'element' => $element,
                'year' => $year,
            ]);
        } catch (FaoStatPortalException $e) {
            Log::warning('FAOSTAT portal search failed', [
                'provider' => self::SOURCE_KEY,
                'category' => $e->category,
                'http_status' => $e->httpStatus,
            ]);
            $this->logger->event('FAOSTAT portal search failed', [
                'operation' => 'search',
                'domain' => $domain,
                'item' => $item,
                'area' => $area,
                'element' => $element,
                'year' => $year,
                'success' => false,
                'error_category' => $e->category,
                'http_status' => $e->httpStatus,
                'latency_ms' => $this->elapsedMs($started),
                'readiness' => $preflight['readiness'],
                'activation_state' => FaoStatActivationPolicy::activationState($domain),
            ], 'warning');

            $status = $e->category === FaoStatErrorCategory::AUTHORIZATION_ERROR
                || $e->category === FaoStatErrorCategory::AUTHENTICATION_ERROR
                ? ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
                : ScientificSourceSearchOutcome::STATUS_FAILED;

            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: $status,
                error: $e->category,
                httpStatus: $e->httpStatus,
                observability: ['category' => $e->category],
            );
        }

        $payload = $result['payload'] ?? [];
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        if ($rows === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: FaoStatErrorCategory::EMPTY_RESULT,
                httpStatus: $result['status'],
                observability: ['observation_count' => 0],
            );
        }

        $cap = max(1, min($limit, 20));
        $results = [];
        foreach (array_slice($rows, 0, $cap) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $observation = $this->normalizer->normalize($row, $element, $queryUrl);
            if ($observation === null) {
                continue;
            }
            $mapped = $this->toSearchResult($observation, $query);
            if ($mapped !== null) {
                $results[] = $mapped;
            }
        }

        if ($results === []) {
            return new ScientificSourceSearchOutcome(
                sourceKey: $this->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: FaoStatErrorCategory::EMPTY_RESULT,
                httpStatus: $result['status'],
            );
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->sourceKey(),
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: $results,
            httpStatus: $result['status'],
            observability: [
                'observation_count' => count($results),
                'evidence_count' => count($results),
                'domain' => $domain,
                'item' => $item,
                'area' => $area,
                'element' => $element,
                'year' => $year,
                'query_element_code' => $element,
                'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                'latency_ms' => $this->elapsedMs($started),
                'readiness' => $preflight['readiness'],
                'activation_state' => FaoStatActivationPolicy::activationState($domain),
                'considered' => true,
                'selected' => true,
            ],
        );
    }

    private function elapsedMs(int $startedHrtime): int
    {
        return (int) max(0, (hrtime(true) - $startedHrtime) / 1_000_000);
    }

    private function isEnabled(): bool
    {
        return FaoStatRuntimePolicy::isEnabled();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{area: ?string, item: ?string, element: ?string, year: ?string, error?: string}
     */
    private function resolveFilters(string $domain, array $options): array
    {
        $area = $this->explicitCode($options, ['area_code', 'area', 'fao_area_code']);
        $item = $this->explicitCode($options, ['item_code', 'item', 'fao_item_code']);
        $element = $this->explicitCode($options, ['element_code', 'element', 'query_element_code', 'fao_element_code']);
        $year = $this->explicitCode($options, ['year', 'year_code', 'fao_year']);

        if ($area === null) {
            $area = $this->resolveLabel($domain, 'countries', $options['area_label'] ?? $options['location'] ?? '');
            if ($area === '__ambiguous') {
                return ['area' => null, 'item' => $item, 'element' => $element, 'year' => $year, 'error' => FaoStatErrorCategory::AMBIGUOUS_CODE];
            }
        }
        if ($item === null) {
            $item = $this->resolveLabel($domain, 'items', $options['item_label'] ?? $options['crop'] ?? '');
            if ($item === '__ambiguous') {
                return ['area' => $area, 'item' => null, 'element' => $element, 'year' => $year, 'error' => FaoStatErrorCategory::AMBIGUOUS_CODE];
            }
        }
        if ($element === null) {
            $element = $this->resolveLabel($domain, 'elements', $options['element_label'] ?? '');
            if ($element === '__ambiguous') {
                return ['area' => $area, 'item' => $item, 'element' => null, 'year' => $year, 'error' => FaoStatErrorCategory::AMBIGUOUS_CODE];
            }
        }
        if ($year === null) {
            $yearCandidate = trim((string) ($options['year_label'] ?? ''));
            if ($yearCandidate !== '' && preg_match('/^(?:19|20)\d{2}$/', $yearCandidate) === 1) {
                $year = $yearCandidate;
            }
        }

        return [
            'area' => $area,
            'item' => $item,
            'element' => $element,
            'year' => $year,
        ];
    }

    private function resolveLabel(string $domain, string $dimension, mixed $label): ?string
    {
        $text = trim((string) $label);
        if ($text === '') {
            return null;
        }
        try {
            $got = $this->codes->resolve($domain, $dimension, $text);
        } catch (FaoStatPortalException) {
            return null;
        }
        if (($got['status'] ?? '') === 'ambiguous') {
            return '__ambiguous';
        }
        if (($got['status'] ?? '') === 'resolved' && is_string($got['code'] ?? null) && $got['code'] !== '') {
            return $got['code'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $keys
     */
    private function explicitCode(array $options, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $options)) {
                continue;
            }
            $code = trim((string) $options[$key]);
            if ($code !== '' && preg_match('/^\d{1,8}$/', $code) === 1) {
                return $code;
            }
        }

        return null;
    }

    private function reconstructQueryUrl(string $domain, string $area, string $item, string $element, string $year): string
    {
        $base = FaoStatDeveloperPortalClient::normalizedBaseUrl();
        $lang = FaoStatDeveloperPortalClient::lang();
        $query = http_build_query([
            'area' => $area,
            'item' => $item,
            'element' => $element,
            'year' => $year,
        ], '', '&', PHP_QUERY_RFC3986);

        return $base.'/'.$lang.'/data/'.$domain.'?'.$query;
    }

    private function toSearchResult(FaoStatObservation $observation, string $query): ?ScientificSearchResult
    {
        $titleParts = array_filter([
            $observation->item,
            $observation->element,
            $observation->area,
            $observation->year !== '' ? $observation->year : null,
        ]);
        $title = implode(' — ', $titleParts);
        if ($title === '') {
            $title = 'FAOSTAT observation';
        }

        $abstract = $observation->value !== ''
            ? 'Value: '.$observation->value.($observation->unit !== '' ? ' '.$observation->unit : '')
            : '';

        $identifier = implode('|', array_filter([
            $observation->domainCode,
            $observation->areaCode,
            $observation->itemCode,
            $observation->responseElementCode,
            $observation->year,
        ]));

        return new ScientificSearchResult(
            sourceKey: self::SOURCE_KEY,
            sourceIdentifier: $identifier !== '' ? $identifier : null,
            title: $title,
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: $observation->provenance['query_url'] ?? null,
            abstract: $abstract !== '' ? $abstract : null,
            journal: null,
            foundBySources: [self::SOURCE_KEY],
            relevanceMetadata: [
                'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                'evidence_family' => 'official_statistics',
                'not_literature' => true,
                'query_element_code' => $observation->queryElementCode,
                'response_element_code' => $observation->responseElementCode,
            ],
            rawMetadata: [
                'faostat' => $observation->toArray(),
                'raw_observation' => $observation->raw,
                'provenance' => $observation->provenance,
            ],
        );
    }
}
