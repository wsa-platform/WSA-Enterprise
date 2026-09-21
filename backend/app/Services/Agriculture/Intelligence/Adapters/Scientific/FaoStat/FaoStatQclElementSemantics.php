<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Verified QCL query↔response element semantics (ADR-004).
 * Query codes and response codes remain distinct; never +3000 arithmetic.
 */
final class FaoStatQclElementSemantics
{
    public const MEASURE_PRODUCTION_QUANTITY = 'production_quantity';

    public const MEASURE_YIELD = 'yield';

    public const MEASURE_AREA_HARVESTED = 'area_harvested';

    public const QUERY_PRODUCTION_QUANTITY = '2510';

    public const QUERY_YIELD = '2413';

    public const QUERY_AREA_HARVESTED = '2312';

    public const RESPONSE_PRODUCTION = '5510';

    public const RESPONSE_YIELD = '5412';

    public const RESPONSE_AREA_HARVESTED = '5312';

    /**
     * Verified dual-code pairs: query_code => [measure, response_code].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const QUERY_PAIRS = [
        self::QUERY_PRODUCTION_QUANTITY => [self::MEASURE_PRODUCTION_QUANTITY, self::RESPONSE_PRODUCTION],
        self::QUERY_YIELD => [self::MEASURE_YIELD, self::RESPONSE_YIELD],
        self::QUERY_AREA_HARVESTED => [self::MEASURE_AREA_HARVESTED, self::RESPONSE_AREA_HARVESTED],
    ];

    /**
     * @var array<string, string>
     */
    private const RESPONSE_TO_MEASURE = [
        self::RESPONSE_PRODUCTION => self::MEASURE_PRODUCTION_QUANTITY,
        self::RESPONSE_YIELD => self::MEASURE_YIELD,
        self::RESPONSE_AREA_HARVESTED => self::MEASURE_AREA_HARVESTED,
    ];

    /**
     * @var array<string, string>
     */
    private const MEASURE_TO_QUERY = [
        self::MEASURE_PRODUCTION_QUANTITY => self::QUERY_PRODUCTION_QUANTITY,
        self::MEASURE_YIELD => self::QUERY_YIELD,
        self::MEASURE_AREA_HARVESTED => self::QUERY_AREA_HARVESTED,
    ];

    public static function queryCodeForMeasure(string $measure): ?string
    {
        return self::MEASURE_TO_QUERY[$measure] ?? null;
    }

    public static function measureForQueryCode(string $code): ?string
    {
        $code = trim($code);

        return self::QUERY_PAIRS[$code][0] ?? null;
    }

    public static function measureForResponseCode(string $code): ?string
    {
        $code = trim($code);

        return self::RESPONSE_TO_MEASURE[$code] ?? null;
    }

    public static function measureForAnyCode(string $code): ?string
    {
        return self::measureForQueryCode($code) ?? self::measureForResponseCode($code);
    }

    public static function responseCodeForQueryCode(string $queryCode): ?string
    {
        $queryCode = trim($queryCode);

        return self::QUERY_PAIRS[$queryCode][1] ?? null;
    }

    /**
     * True when wanted query/response/any verified code matches an observed code
     * via exact equality or a verified dual-code pair for the same measure.
     */
    public static function codesCompatible(string $wanted, string $observed): bool
    {
        $wanted = trim($wanted);
        $observed = trim($observed);
        if ($wanted === '' || $observed === '') {
            return false;
        }
        if ($wanted === $observed) {
            return true;
        }

        $wantedMeasure = self::measureForAnyCode($wanted);
        $observedMeasure = self::measureForAnyCode($observed);

        return $wantedMeasure !== null
            && $observedMeasure !== null
            && $wantedMeasure === $observedMeasure;
    }

    /**
     * Detect distinct requested QCL measures from structured plan surfaces.
     * Does not invent a measure from a bare "statistical" / "quantity" flag.
     * Prefers explicit measure wording in the question over generic property keys.
     *
     * @return list<string>
     */
    public static function detectRequestedMeasures(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];

        // Concrete structured surfaces only — never treat bare "quantity" as Production.
        $concreteStructured = trim(implode(' ', array_filter([
            (string) ($constraints['requested_property_surface'] ?? ''),
            (string) ($constraints['element_label'] ?? ''),
            (string) ($constraints['measure'] ?? ''),
            (string) ($constraints['fao_element_label'] ?? ''),
            (string) ($constraints['requested_property_key'] ?? ''),
        ])));
        $fromConcrete = self::detectMeasuresInSurface($concreteStructured);

        $questionBlob = strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            $query->topic,
            implode(' ', $plan->requestedInformation),
            $plan->researchIntent,
        ]))));
        $fromQuestion = self::detectMeasuresInSurface($questionBlob);

        // User wording wins when it names one or more concrete measures.
        if ($fromQuestion !== []) {
            return $fromQuestion;
        }
        if ($fromConcrete !== []) {
            return $fromConcrete;
        }

        $explicitCode = trim((string) (
            $constraints['element_code']
            ?? $constraints['element']
            ?? $constraints['query_element_code']
            ?? $constraints['fao_element_code']
            ?? ''
        ));
        if ($explicitCode !== '' && preg_match('/^\d{1,8}$/', $explicitCode) === 1) {
            $measure = self::measureForAnyCode($explicitCode);
            if ($measure !== null) {
                return [$measure];
            }
        }

        // Generic keys like requested_property=quantity are not measures by themselves.
        return [];
    }

    /**
     * @return list<string>
     */
    public static function detectMeasuresInSurface(string $surface): array
    {
        $blob = mb_strtolower(trim($surface));
        if ($blob === '') {
            return [];
        }

        $found = [];

        if (str_contains($blob, 'area harvested')
            || str_contains($blob, 'harvested area')
            || str_contains($blob, 'مساحة محصودة')
            || (str_contains($blob, 'مساحة') && ! str_contains($blob, 'إنتاج'))) {
            $found[self::MEASURE_AREA_HARVESTED] = true;
        }

        if (str_contains($blob, 'production quantity')
            || str_contains($blob, 'quantity produced')
            || str_contains($blob, 'amount produced')
            || str_contains($blob, 'كمية الإنتاج')
            || str_contains($blob, 'كمية المنتج')) {
            $found[self::MEASURE_PRODUCTION_QUANTITY] = true;
        }

        if (str_contains($blob, 'crop yield')
            || str_contains($blob, 'yield statistics')
            || preg_match('/\byield\b/u', $blob) === 1
            || str_contains($blob, 'إنتاجية')
            || str_contains($blob, 'محصول')) {
            $found[self::MEASURE_YIELD] = true;
        }

        // Bare production/produced — never invent production from a generic "quantity" property key
        // or merely because the question is flagged statistical.
        $hasTradeOrStockSignal = preg_match('/\bimports?\b/u', $blob) === 1
            || preg_match('/\bexports?\b/u', $blob) === 1
            || preg_match('/\bstocks?\b/u', $blob) === 1
            || str_contains($blob, 'واردات')
            || str_contains($blob, 'صادرات')
            || str_contains($blob, 'مخزون');
        $hasProductionSignal = str_contains($blob, 'production')
            || preg_match('/\bproduced\b/u', $blob) === 1
            || str_contains($blob, 'إنتاج')
            || str_contains($blob, 'المنتج');
        $isProductionSystem = preg_match(
            '/\bproduction\s+(system|systems|method|methods|practice|practices|recommendation|recommendations)\b/u',
            $blob,
        ) === 1;
        if (! isset($found[self::MEASURE_PRODUCTION_QUANTITY])
            && $hasProductionSignal
            && ! $isProductionSystem
            && ! $hasTradeOrStockSignal
        ) {
            $found[self::MEASURE_PRODUCTION_QUANTITY] = true;
        }

        return array_keys($found);
    }

    /**
     * Unique query element when exactly one measure is requested; null when none or ambiguous.
     *
     * @return array{element: ?string, measures: list<string>, status: 'resolved'|'unresolved'|'ambiguous'}
     */
    public static function resolveQueryElement(KnowledgeQueryPlan $plan): array
    {
        $measures = self::detectRequestedMeasures($plan);
        if ($measures === []) {
            return ['element' => null, 'measures' => [], 'status' => 'unresolved'];
        }
        if (count($measures) > 1) {
            return ['element' => null, 'measures' => $measures, 'status' => 'ambiguous'];
        }

        $element = self::queryCodeForMeasure($measures[0]);

        return [
            'element' => $element,
            'measures' => $measures,
            'status' => $element !== null ? 'resolved' : 'unresolved',
        ];
    }
}
