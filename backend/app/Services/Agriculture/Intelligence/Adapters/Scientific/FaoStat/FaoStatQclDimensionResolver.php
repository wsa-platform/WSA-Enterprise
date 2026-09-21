<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Verified QCL dimension codes only. Does not invent unverified FAOSTAT codes.
 * Query element codes and response element codes remain separate (ADR-004).
 * WSA crop taxonomy IDs are never treated as FAOSTAT item codes.
 */
final class FaoStatQclDimensionResolver
{
    public const DOMAIN_QCL = 'QCL';

    /** @deprecated Use FaoStatQclElementSemantics::QUERY_PRODUCTION_QUANTITY */
    public const QUERY_ELEMENT_PRODUCTION_QUANTITY = FaoStatQclElementSemantics::QUERY_PRODUCTION_QUANTITY;

    public static function sanitizeDomain(?string $domain): string
    {
        $code = strtoupper(trim((string) $domain));
        $allowed = FaoStatDeveloperPortalClient::allowedDomains();
        $fallback = in_array(self::DOMAIN_QCL, $allowed, true) ? self::DOMAIN_QCL : ($allowed[0] ?? self::DOMAIN_QCL);
        if ($code === '' || $code === 'AGRI') {
            return $fallback;
        }
        if (preg_match('/^[A-Z][A-Z0-9]{1,7}$/', $code) === 1) {
            return $code;
        }

        return $fallback;
    }

    public static function areaCode(?string $location, string $blob): ?string
    {
        $areas = FaoStatQclVerifiedDimensionMap::areas();
        foreach (self::labelCandidates($location) as $label) {
            if (isset($areas[$label])) {
                return $areas[$label];
            }
        }

        foreach ($areas as $label => $code) {
            if (self::containsTerm($blob, $label)) {
                return $code;
            }
        }

        return null;
    }

    public static function itemCode(?string $crop, string $blob): ?string
    {
        $items = FaoStatQclVerifiedDimensionMap::items();
        foreach (self::labelCandidates($crop) as $label) {
            $mapped = self::itemCodeForLabel($label);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        foreach ($items as $label => $code) {
            if (self::containsTerm($blob, $label)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Explicit crop-identity → FAOSTAT item mapping.
     * Never treats a bare numeric string as a FAOSTAT item code.
     */
    public static function itemCodeFromCropIdentity(?string $cropId, ?string $cropLabel, ?string $scientificName, string $blob): ?string
    {
        $candidates = [];
        foreach ([$cropLabel, $cropId, $scientificName] as $value) {
            $normalized = mb_strtolower(trim((string) $value));
            if ($normalized === '' || preg_match('/^\d{1,8}$/', $normalized) === 1) {
                // Numeric-only values are not FAO item codes unless supplied via fao_item_code.
                continue;
            }
            $candidates[] = $normalized;
        }

        if (is_string($cropId) && $cropId !== '' && preg_match('/^\d{1,8}$/', $cropId) !== 1) {
            foreach (FieldCropTaxonomyCatalog::searchTermsFor($cropId) as $term) {
                $normalized = mb_strtolower(trim($term));
                if ($normalized !== '') {
                    $candidates[] = $normalized;
                }
            }
        }

        foreach (array_values(array_unique($candidates)) as $label) {
            $mapped = self::itemCodeForLabel($label);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        return self::itemCode(null, $blob);
    }

    public static function yearCode(array $constraints, string $blob): ?string
    {
        foreach (['year', 'year_code', 'fao_year'] as $key) {
            $code = trim((string) ($constraints[$key] ?? ''));
            if ($code !== '' && preg_match('/^(?:19|20)\d{2}$/', $code) === 1) {
                return $code;
            }
        }

        if (preg_match('/\b((?:19|20)\d{2})\b/u', $blob, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Resolve the QCL query element for a statistical need.
     * Returns null when no measure is requested or multiple measures are ambiguous.
     *
     * @return array{element: ?string, measures: list<string>, status: 'resolved'|'unresolved'|'ambiguous'|'not_statistical'}
     */
    public static function resolveQueryElement(KnowledgeQueryPlan $plan): array
    {
        if (! self::hasQuantitativeStatisticalNeed($plan)) {
            return ['element' => null, 'measures' => [], 'status' => 'not_statistical'];
        }

        return FaoStatQclElementSemantics::resolveQueryElement($plan);
    }

    public static function queryElementCode(KnowledgeQueryPlan $plan): ?string
    {
        $resolved = self::resolveQueryElement($plan);

        return $resolved['status'] === 'resolved' ? $resolved['element'] : null;
    }

    public static function hasQuantitativeStatisticalNeed(KnowledgeQueryPlan $plan): bool
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $questionType = strtolower(trim((string) ($constraints['question_type'] ?? '')));
        $intent = strtolower(trim($plan->researchIntent));
        $requested = strtolower(implode(' ', $plan->requestedInformation));
        $blob = strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            $query->topic,
            $requested,
            $intent,
            $questionType,
        ]))));

        if ($questionType === 'statistical' || $intent === 'agricultural_economics') {
            return true;
        }

        if (FaoStatQclElementSemantics::detectMeasuresInSurface($blob) !== []) {
            return true;
        }

        foreach ([
            'production statistics',
            'national production',
            'official statistics',
            'faostat',
            'how much',
        ] as $token) {
            if (str_contains($blob, $token)) {
                return true;
            }
        }

        if (preg_match('/\bproduction\s+(system|systems|method|methods|practice|practices|recommendation|recommendations)\b/u', $blob) === 1) {
            return false;
        }

        $hasProduction = preg_match('/\bproduction\b/u', $blob) === 1
            || preg_match('/\byield\b/u', $blob) === 1
            || preg_match('/\boutput\b/u', $blob) === 1;
        if (! $hasProduction) {
            return false;
        }

        return $questionType === 'quantity'
            || preg_match('/\bwhat\s+was\b/u', $blob) === 1
            || preg_match('/\bhow\s+much\b/u', $blob) === 1
            || preg_match('/\b((?:19|20)\d{2})\b/u', $blob) === 1
            || str_contains($blob, 'statistics')
            || str_contains($blob, 'quantity');
    }

    private static function itemCodeForLabel(string $label): ?string
    {
        $label = mb_strtolower(trim($label));
        if ($label === '') {
            return null;
        }

        return FaoStatQclVerifiedDimensionMap::items()[$label] ?? null;
    }

    /**
     * @return list<string>
     */
    private static function labelCandidates(?string $value): array
    {
        $normalized = mb_strtolower(trim((string) $value));
        if ($normalized === '') {
            return [];
        }

        $normalized = trim((string) preg_replace('/\s+(?:in|at|near|on)\b.*$/u', '', $normalized));
        $normalized = trim((string) preg_replace('/\s+\d{4}\b.*$/u', '', $normalized));
        if ($normalized === '') {
            return [];
        }

        return [$normalized];
    }

    private static function containsTerm(string $haystack, string $term): bool
    {
        $term = mb_strtolower(trim($term));
        $haystack = mb_strtolower($haystack);
        if ($term === '' || $haystack === '') {
            return false;
        }

        if (preg_match('/[^\p{L}\p{N}]/u', $term) === 1) {
            return mb_strpos($haystack, $term) !== false;
        }

        return preg_match('/(?<!\p{L})'.preg_quote($term, '/').'(?!\p{L})/u', $haystack) === 1;
    }
}
