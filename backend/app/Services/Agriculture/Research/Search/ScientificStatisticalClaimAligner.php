<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclElementSemantics;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Claim alignment for structured statistical observations.
 * Matches entity, location, year, and measure family — never provider identity.
 * Prefers structured FAOSTAT codes / requested property surfaces over keyword precedence.
 */
final class ScientificStatisticalClaimAligner
{
    /**
     * @return array{relevant: bool, mismatches: list<string>}
     */
    public function assess(KnowledgeQueryPlan $plan, ScientificStructuredObservation $observation): array
    {
        $mismatches = [];
        if (! $this->entityMatches($plan, $observation)) {
            $mismatches[] = 'entity';
        }
        if (! $this->locationMatches($plan, $observation)) {
            $mismatches[] = 'location';
        }
        if (! $this->yearMatches($plan, $observation)) {
            $mismatches[] = 'year';
        }
        if (! $this->propertyMatches($plan, $observation)) {
            $mismatches[] = 'property';
        }

        return [
            'relevant' => $mismatches === [],
            'mismatches' => $mismatches,
        ];
    }

    public static function measureFamily(string $surface): string
    {
        $families = FaoStatQclElementSemantics::detectMeasuresInSurface($surface);
        if (count($families) === 1) {
            return $families[0];
        }
        if (count($families) > 1) {
            // Ambiguous multi-measure surface — do not silently prefer yield or production.
            return '';
        }

        $blob = mb_strtolower(trim($surface));
        if ($blob === '') {
            return '';
        }
        if (preg_match('/\bimports?\b/u', $blob) === 1 || str_contains($blob, 'واردات')) {
            return 'imports';
        }
        if (preg_match('/\bexports?\b/u', $blob) === 1 || str_contains($blob, 'صادرات')) {
            return 'exports';
        }
        if (preg_match('/\bstocks?\b/u', $blob) === 1 || str_contains($blob, 'مخزون')) {
            return 'stock';
        }
        if (preg_match('/\bprices?\b/u', $blob) === 1 || str_contains($blob, 'سعر')) {
            return 'price';
        }
        if (preg_match('/\bvalues?\b/u', $blob) === 1 || str_contains($blob, 'قيمة')) {
            return 'value';
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '_', $blob) ?: $blob;
        $slug = trim((string) $slug, '_');
        if ($slug === '' || self::isYearOnlySlug($slug)) {
            return '';
        }

        return $slug;
    }

    private static function isYearOnlySlug(string $slug): bool
    {
        return preg_match('/^(?:(?:19|20)\d{2}_?)+$/', $slug) === 1;
    }

    private function entityMatches(KnowledgeQueryPlan $plan, ScientificStructuredObservation $observation): bool
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];

        // Prefer structured FAOSTAT item identity when present (never taxonomy-ID equality alone).
        $wantedCodes = array_values(array_filter([
            trim((string) ($constraints['item'] ?? '')),
            trim((string) ($constraints['item_code'] ?? '')),
            trim((string) ($constraints['fao_item_code'] ?? '')),
        ], static fn (string $code): bool => $code !== '' && preg_match('/^\d{1,8}$/', $code) === 1));
        if ($wantedCodes !== [] && $observation->entityCode !== '') {
            foreach ($wantedCodes as $code) {
                if ($code === $observation->entityCode) {
                    return true;
                }
            }
        }

        $wanted = array_values(array_filter([
            is_string($query->cropId) ? $query->cropId : '',
            is_string($query->crop) ? $query->crop : '',
            is_string($query->scientificName) ? $query->scientificName : '',
        ]));

        // Subject contributes only when it is an entity/crop subject — not a topic slug.
        if (is_array($query->subject)) {
            $subjectType = strtolower(trim((string) ($query->subject['type'] ?? '')));
            if (in_array($subjectType, ['crop', 'entity', 'organism', 'species', 'item'], true)) {
                $wanted[] = (string) ($query->subject['value'] ?? '');
                $wanted[] = (string) ($query->subject['label'] ?? '');
            }
        }
        $wanted = array_values(array_filter($wanted));

        $observed = array_values(array_filter([$observation->entity, $observation->entityCode]));
        if ($wanted === []) {
            return $this->anyTokenInBlob($observed, $this->questionBlob($plan));
        }

        return $this->labelsOverlap($wanted, $observed);
    }

    private function locationMatches(KnowledgeQueryPlan $plan, ScientificStructuredObservation $observation): bool
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $wanted = array_values(array_filter([
            is_string($query->location) ? $query->location : '',
            (string) ($constraints['location'] ?? ''),
            (string) ($constraints['area'] ?? ''),
            (string) ($constraints['area_code'] ?? ''),
        ]));
        $observed = array_values(array_filter([$observation->location, $observation->locationCode]));
        if ($wanted === []) {
            return true;
        }

        return $this->labelsOverlap($wanted, $observed);
    }

    private function yearMatches(KnowledgeQueryPlan $plan, ScientificStructuredObservation $observation): bool
    {
        $wanted = $this->requestedYear($plan);
        if ($wanted === '') {
            return true;
        }

        return $wanted === trim($observation->year);
    }

    private function propertyMatches(KnowledgeQueryPlan $plan, ScientificStructuredObservation $observation): bool
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $wantedCode = trim((string) (
            $constraints['element']
            ?? $constraints['element_code']
            ?? $constraints['query_element_code']
            ?? ''
        ));
        // Element-code short-circuit only for quantitative statistical needs — never for
        // mechanism/physiology questions that merely inherited forced FAOSTAT filters.
        if ($wantedCode !== ''
            && $observation->propertyCode !== ''
            && FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan)
            && FaoStatQclElementSemantics::codesCompatible($wantedCode, $observation->propertyCode)
        ) {
            return true;
        }

        $observedFamily = self::measureFamily($observation->property);
        if ($observedFamily === '' && $observation->propertyCode !== '') {
            $observedFamily = (string) (FaoStatQclElementSemantics::measureForAnyCode($observation->propertyCode) ?? '');
        }

        $surfaceFamily = self::measureFamily(trim(implode(' ', array_filter([
            (string) ($constraints['requested_property_surface'] ?? ''),
            (string) ($constraints['requested_property_key'] ?? ''),
            (string) ($constraints['requested_property'] ?? ''),
            (string) ($constraints['element_label'] ?? ''),
        ]))));

        if ($surfaceFamily !== '' && $observedFamily === $surfaceFamily) {
            return true;
        }
        if ($surfaceFamily !== '' && $surfaceFamily !== $observedFamily) {
            return false;
        }

        $requestedMeasures = FaoStatQclElementSemantics::detectRequestedMeasures($plan);
        if (count($requestedMeasures) === 1 && $observedFamily === $requestedMeasures[0]) {
            return true;
        }
        if (count($requestedMeasures) > 1) {
            // Decomposed multi-measure: accept an observation whose measure is one of the requested set.
            if ($observedFamily !== '' && in_array($observedFamily, $requestedMeasures, true)) {
                return true;
            }
            if ($observation->propertyCode !== '') {
                $codeMeasure = FaoStatQclElementSemantics::measureForAnyCode($observation->propertyCode);
                if ($codeMeasure !== null && in_array($codeMeasure, $requestedMeasures, true)) {
                    return true;
                }
            }

            return false;
        }

        $questionFamily = self::measureFamily(trim($query->originalQuestion.' '.$query->normalizedQuestion));
        if ($questionFamily !== '' && $observedFamily === $questionFamily) {
            return true;
        }
        if ($questionFamily !== '' && $questionFamily !== $observedFamily) {
            return false;
        }

        return $observedFamily !== '' || $observation->property !== '';
    }

    private function requestedYear(KnowledgeQueryPlan $plan): string
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        foreach (['year', 'year_code', 'fao_year'] as $key) {
            $code = trim((string) ($constraints[$key] ?? ''));
            if (preg_match('/^(?:19|20)\d{2}$/', $code) === 1) {
                return $code;
            }
        }
        $blob = $this->questionBlob($plan);
        if (preg_match('/((?:19|20)\d{2})/u', $blob, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function questionBlob(KnowledgeQueryPlan $plan): string
    {
        $query = $plan->normalizedQuery;

        return mb_strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            is_string($query->crop) ? $query->crop : '',
            is_string($query->cropId) ? $query->cropId : '',
            is_string($query->location) ? $query->location : '',
        ]))));
    }

    /**
     * @param  list<string>  $wanted
     * @param  list<string>  $observed
     */
    private function labelsOverlap(array $wanted, array $observed): bool
    {
        foreach ($wanted as $want) {
            foreach ($observed as $got) {
                if ($this->sameLabel($want, $got)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function sameLabel(string $left, string $right): bool
    {
        $a = mb_strtolower(trim($left));
        $b = mb_strtolower(trim($right));
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }

        return str_contains($a, $b) || str_contains($b, $a);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function anyTokenInBlob(array $tokens, string $blob): bool
    {
        foreach ($tokens as $token) {
            $needle = mb_strtolower(trim($token));
            if ($needle !== '' && str_contains($blob, $needle)) {
                return true;
            }
        }

        return false;
    }
}
