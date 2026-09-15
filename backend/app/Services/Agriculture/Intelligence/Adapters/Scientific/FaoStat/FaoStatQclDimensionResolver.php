<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Verified QCL dimension codes only. Does not invent unverified FAOSTAT codes.
 * Query element 2510 and response element 5510 remain separate; never +3000.
 */
final class FaoStatQclDimensionResolver
{
    public const DOMAIN_QCL = 'QCL';

    public const QUERY_ELEMENT_PRODUCTION_QUANTITY = '2510';

    /** @var array<string, string> */
    private const AREA_CODES = [
        'italy' => '106',
        'italia' => '106',
        'italien' => '106',
        'إيطاليا' => '106',
    ];

    /** @var array<string, string> */
    private const ITEM_CODES = [
        'wheat' => '15',
        'triticum aestivum' => '15',
        'قمح' => '15',
        'القمح' => '15',
    ];

    public static function sanitizeDomain(?string $domain): string
    {
        $code = strtoupper(trim((string) $domain));
        $allowed = FaoStatDeveloperPortalClient::allowedDomains();
        if ($code !== '' && in_array($code, $allowed, true)) {
            return $code;
        }

        return in_array(self::DOMAIN_QCL, $allowed, true) ? self::DOMAIN_QCL : ($allowed[0] ?? self::DOMAIN_QCL);
    }

    public static function areaCode(?string $location, string $blob): ?string
    {
        foreach (self::labelCandidates($location) as $label) {
            if (isset(self::AREA_CODES[$label])) {
                return self::AREA_CODES[$label];
            }
        }

        foreach (self::AREA_CODES as $label => $code) {
            if (self::containsTerm($blob, $label)) {
                return $code;
            }
        }

        return null;
    }

    public static function itemCode(?string $crop, string $blob): ?string
    {
        foreach (self::labelCandidates($crop) as $label) {
            if (isset(self::ITEM_CODES[$label])) {
                return self::ITEM_CODES[$label];
            }
        }

        foreach (self::ITEM_CODES as $label => $code) {
            if (self::containsTerm($blob, $label)) {
                return $code;
            }
        }

        return null;
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

    public static function queryElementCode(KnowledgeQueryPlan $plan): ?string
    {
        return self::hasQuantitativeStatisticalNeed($plan)
            ? self::QUERY_ELEMENT_PRODUCTION_QUANTITY
            : null;
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

        foreach ([
            'production quantity',
            'quantity produced',
            'amount produced',
            'production statistics',
            'harvested area',
            'area harvested',
            'crop yield',
            'yield statistics',
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
