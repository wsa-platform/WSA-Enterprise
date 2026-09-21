<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * FAOSTAT-owned claim classification from the plan text.
 * Does not call QueryUnderstandingService and is not an agricultural-question gate.
 */
final class FaoStatClaimSemantics
{
    public const QUANTITATIVE = 'quantitative';

    public const COMPARISON = 'comparison';

    public const CAUSAL = 'causal';

    public const RECOMMENDATION = 'recommendation';

    public const OTHER = 'other';

    /**
     * @return array{
     *     kind: string,
     *     years: list<string>,
     *     unit: ?string,
     *     area: ?string,
     *     item: ?string,
     *     element: ?string
     * }
     */
    public static function inspect(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $blob = strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            $query->topic,
            is_string($query->crop) ? $query->crop : '',
            is_string($query->location) ? $query->location : '',
            $plan->researchIntent,
            implode(' ', $plan->requestedInformation),
        ]))));

        return [
            'kind' => self::kind($blob),
            'years' => self::years($blob, $constraints),
            'unit' => self::unit($blob),
            'area' => self::firstNonEmpty([
                is_string($query->location) ? $query->location : null,
                is_string($constraints['location'] ?? null) ? (string) $constraints['location'] : null,
            ]),
            'item' => self::firstNonEmpty([
                is_string($query->crop) ? $query->crop : null,
                is_string($query->cropId) ? $query->cropId : null,
            ]),
            'element' => self::elementLabel($blob, $constraints),
        ];
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private static function elementLabel(string $blob, array $constraints): ?string
    {
        $surface = trim(implode(' ', array_filter([
            (string) ($constraints['requested_property_surface'] ?? ''),
            (string) ($constraints['element_label'] ?? ''),
            $blob,
        ])));
        $measures = FaoStatQclElementSemantics::detectMeasuresInSurface($surface);
        if (count($measures) !== 1) {
            return null;
        }

        return match ($measures[0]) {
            FaoStatQclElementSemantics::MEASURE_YIELD => 'yield',
            FaoStatQclElementSemantics::MEASURE_AREA_HARVESTED => 'area_harvested',
            FaoStatQclElementSemantics::MEASURE_PRODUCTION_QUANTITY => 'production',
            default => null,
        };
    }

    public static function kind(string $blob): string
    {
        if (self::isRecommendation($blob)) {
            return self::RECOMMENDATION;
        }
        if (self::isCausal($blob)) {
            return self::CAUSAL;
        }
        if (self::isComparison($blob)) {
            return self::COMPARISON;
        }
        if (self::isQuantitative($blob)) {
            return self::QUANTITATIVE;
        }

        return self::OTHER;
    }

    public static function isCausal(string $blob): bool
    {
        if (preg_match('/\b(because|caused by|due to|owing to|reason for|as a result of)\b/u', $blob) === 1) {
            return true;
        }

        return preg_match('/\bwhy\b/u', $blob) === 1
            && preg_match('/\bwhat\s+was\b/u', $blob) !== 1;
    }

    public static function isRecommendation(string $blob): bool
    {
        return preg_match('/\b(best fertilizer|recommended treatment|should apply|recommend(?:ed|ation)?|diagnos(?:e|is)|treatment recommendation|agronomic recommendation)\b/u', $blob) === 1;
    }

    public static function isComparison(string $blob): bool
    {
        if (preg_match('/\b(increased from|decreased from|compared (?:to|with)|from (?:19|20)\d{2} to (?:19|20)\d{2}|between (?:19|20)\d{2} and (?:19|20)\d{2})\b/u', $blob) === 1) {
            return true;
        }

        return count(self::yearsFromBlob($blob)) >= 2;
    }

    public static function isQuantitative(string $blob): bool
    {
        return preg_match('/\b(production quantity|produced|how much|tonnes|statistics|quantity)\b/u', $blob) === 1
            || preg_match('/\bwhat\s+was\b/u', $blob) === 1;
    }

    /**
     * @param  array<string, mixed>  $constraints
     * @return list<string>
     */
    public static function years(string $blob, array $constraints = []): array
    {
        $years = [];
        foreach (['year', 'year_code', 'fao_year'] as $key) {
            $code = trim((string) ($constraints[$key] ?? ''));
            if ($code !== '' && preg_match('/^(?:19|20)\d{2}$/', $code) === 1) {
                $years[] = $code;
            }
        }

        return array_values(array_unique(array_merge($years, self::yearsFromBlob($blob))));
    }

    /** @return list<string> */
    public static function yearsFromBlob(string $blob): array
    {
        if (preg_match_all('/\b((?:19|20)\d{2})\b/u', $blob, $matches) < 1) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    public static function unit(string $blob): ?string
    {
        if (preg_match('/\b(tonnes|tonne|metric tons|metric ton)\b/u', $blob) === 1) {
            return 't';
        }
        if (preg_match('/\bkg\b|\bkilograms?\b/u', $blob) === 1) {
            return 'kg';
        }
        if (preg_match('/\bhectares?\b|\bha\b/u', $blob) === 1) {
            return 'ha';
        }

        return null;
    }

    public static function normalizeUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match ($unit) {
            'tonnes', 'tonne', 't', 'mt', 'metric ton', 'metric tons' => 't',
            'kilogram', 'kilograms', 'kg' => 'kg',
            'hectare', 'hectares', 'ha' => 'ha',
            default => $unit,
        };
    }

    /**
     * @param  list<string>  $labels
     */
    private static function containsLabel(string $blob, array $labels): bool
    {
        foreach ($labels as $label) {
            if ($label !== '' && str_contains($blob, $label)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<?string>  $values
     */
    private static function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
