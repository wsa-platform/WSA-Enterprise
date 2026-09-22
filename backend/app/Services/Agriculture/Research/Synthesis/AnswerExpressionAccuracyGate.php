<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Phase 5 Unit B3 — Catalog-free answer-expression accuracy gate.
 *
 * Consumes Phase-4 signals and QuestionClaim metadata. Does not own relevance,
 * directness, claim_relation, FAOSTAT alignment, Catalog, persistence, or feedback.
 */
final class AnswerExpressionAccuracyGate
{
    /**
     * @param  array<string, mixed>  $questionClaim
     * @return array{
     *     allowed: bool,
     *     reasons: list<string>,
     *     claim_text: string,
     *     numerical_values: list<string>
     * }
     */
    public function evaluate(
        ScientificEvidenceItem $item,
        KnowledgeQueryPlan $plan,
        array $questionClaim,
        string $groundedText,
        string $relationship,
        callable $evidenceIdentityCompatible,
        callable $resolveDirectness,
        callable $extractSupportedPropertyValues,
        callable $requiresSupportedMeasurement,
    ): array {
        $reasons = [];
        $property = mb_strtolower(trim((string) ($questionClaim['property'] ?? '')));
        $askedLocation = trim((string) ($questionClaim['location'] ?? $plan->normalizedQuery->location ?? ''));
        $askedTime = trim((string) ($questionClaim['time'] ?? ''));
        if ($askedTime === '') {
            $askedTime = trim((string) ($plan->normalizedQuery->constraints['year']
                ?? $plan->normalizedQuery->constraints['year_code']
                ?? $plan->normalizedQuery->constraints['time']
                ?? ''));
        }

        $directness = (string) $resolveDirectness($item, $plan);
        if (! (bool) $evidenceIdentityCompatible($item, $plan, $directness)) {
            $reasons[] = 'accuracy_entity_incompatible';
        }

        $speciesRelation = (string) ($item->qualityFactors['species_relation'] ?? '');
        if (in_array($speciesRelation, ['different_species', 'entity_less', 'genus_only'], true)) {
            $reasons[] = 'accuracy_entity_incompatible';
        }
        if (array_key_exists('entity_matched', $item->qualityFactors)
            && $item->qualityFactors['entity_matched'] === false
            && $plan->normalizedQuery->isEntityDependent()) {
            $reasons[] = 'accuracy_entity_incompatible';
        }

        $verification = strtoupper(trim((string) ($item->qualityFactors['verification_label']
            ?? $item->sourceAttribution['verification_label']
            ?? '')));
        if ($verification === EvidenceVerificationLayer::LABEL_GEOGRAPHIC_MISMATCH
            || $directness === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            $reasons[] = 'accuracy_geography_unsupported';
        }

        // Geography may still use structured observation location when explicitly present.
        $observation = ScientificStructuredObservation::fromEvidenceItem($item);
        if ($askedLocation !== '' && $observation !== null && trim($observation->location) !== '') {
            $askedNorm = mb_strtolower($askedLocation);
            $obsNorm = mb_strtolower(trim($observation->location));
            if ($obsNorm !== '' && $askedNorm !== '' && $obsNorm !== $askedNorm
                && ! str_contains($obsNorm, $askedNorm) && ! str_contains($askedNorm, $obsNorm)) {
                $reasons[] = 'accuracy_geography_unsupported';
            }
        }

        // Temporal expression gate: explicit observation year only.
        // Never treat publicationYear / title / citation year as observation year.
        if ($askedTime !== '' && preg_match('/^(?:19|20)\d{2}$/', $askedTime) === 1) {
            $obsYear = $this->explicitObservationYear($item);
            if ($obsYear === '' || (int) $obsYear !== (int) $askedTime) {
                $reasons[] = 'accuracy_period_unsupported';
            }
        }

        if ($property !== '' && ! in_array($property, ['general', 'definition'], true)) {
            $hay = mb_strtolower(trim(implode(' ', array_filter([
                (string) $item->publicationTitle,
                (string) $item->evidenceText,
                $groundedText,
            ]))));
            $terms = $this->propertyAddressTerms($property);
            if ($terms !== [] && ! $this->haystackAddressesPropertyTerms($hay, $terms)) {
                $reasons[] = 'accuracy_property_unsupported';
            }
        }

        $numericPlan = $this->planWithClaimProperty($plan, $property);
        $numericalValues = array_values(array_filter(
            (array) $extractSupportedPropertyValues($groundedText, $numericPlan),
            static fn ($v): bool => is_string($v) && trim($v) !== '',
        ));

        if ($this->hasAmbiguousNumericMeasurements($groundedText, $numericalValues)) {
            $reasons[] = 'accuracy_numeric_ambiguous';
            $numericalValues = [];
        }

        $claimText = $groundedText;
        $needsMeasurement = (bool) $requiresSupportedMeasurement($numericPlan)
            || $this->textContainsMeasurementCandidate($groundedText);

        if ($numericalValues !== [] && $this->findingHasUncoveredMeasurement($groundedText, $numericalValues)) {
            // Keep supported values only when uncovered tokens are a different unit class;
            // do not blank an otherwise supported single-measurement statement.
            if ($this->hasAmbiguousNumericMeasurements($groundedText, $numericalValues)) {
                $reasons[] = 'accuracy_numeric_ambiguous';
                $claimText = '';
                $numericalValues = [];
            }
        } elseif ($numericalValues === [] && $needsMeasurement) {
            // PARTIAL qualitative support may omit numbers; do not force a numeric gate.
            if ($relationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED
                && ! $this->textContainsMeasurementCandidate($groundedText)) {
                // keep grounded qualitative partial prose
            } else {
                if ($reasons === []) {
                    $reasons[] = 'accuracy_numeric_unsupported';
                }
                $claimText = '';
            }
        }

        $blocking = array_values(array_unique($reasons));
        if ($claimText === '' && $blocking === []) {
            $blocking[] = 'accuracy_numeric_unsupported';
        }
        if ($blocking !== []) {
            return [
                'allowed' => false,
                'reasons' => $blocking,
                'claim_text' => '',
                'numerical_values' => [],
            ];
        }

        if ($relationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED && $claimText === '') {
            return [
                'allowed' => false,
                'reasons' => ['accuracy_numeric_unsupported'],
                'claim_text' => '',
                'numerical_values' => [],
            ];
        }

        return [
            'allowed' => $claimText !== '',
            'reasons' => [],
            'claim_text' => $claimText,
            'numerical_values' => $numericalValues,
        ];
    }

    /**
     * Explicit observation / statistical / structured year only.
     * Never falls back to publicationYear or surface publication metadata.
     */
    private function explicitObservationYear(ScientificEvidenceItem $item): string
    {
        foreach ([$item->qualityFactors, $item->sourceAttribution] as $meta) {
            if (! is_array($meta)) {
                continue;
            }

            foreach (['observation', 'statistical', 'structured'] as $bagKey) {
                $bag = $meta[$bagKey] ?? null;
                if (! is_array($bag)) {
                    continue;
                }
                foreach (['year', 'year_code', 'time'] as $yearKey) {
                    $value = trim((string) ($bag[$yearKey] ?? ''));
                    if ($value !== '' && preg_match('/^(?:19|20)\d{2}$/', $value) === 1) {
                        return $value;
                    }
                }
            }

            if (array_key_exists('observation_year', $meta)) {
                $value = trim((string) $meta['observation_year']);
                if ($value !== '' && preg_match('/^(?:19|20)\d{2}$/', $value) === 1) {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Catalog-free property-term address check (Phase 5 B3).
     * Consumes plan/mapper lexical terms only — does not call AgriculturalEntityCatalog.
     *
     * @param  list<mixed>  $propertyTerms
     */
    public function haystackAddressesPropertyTerms(string $haystack, array $propertyTerms): bool
    {
        $haystack = mb_strtolower(trim($haystack));
        if ($haystack === '') {
            return false;
        }

        foreach ($propertyTerms as $term) {
            $normalized = mb_strtolower(trim((string) $term));
            if ($normalized === '' || in_array($normalized, ['general_knowledge', 'agriculture', 'farming', 'general', 'definition'], true)) {
                continue;
            }
            if (mb_strpos($haystack, $normalized) === false) {
                continue;
            }
            if ($this->propertyMentionIsNegated($haystack, $normalized)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Catalog-free negation guard for property mentions ("without reporting yield").
     */
    private function propertyMentionIsNegated(string $haystack, string $term): bool
    {
        $quoted = preg_quote($term, '/');

        return preg_match(
            '/\b(?:without|no|not|never|lacking|absent|excluding)\b(?:\s+\w+){0,4}\s+'.$quoted.'\b/u',
            $haystack,
        ) === 1
            || preg_match(
                '/\b'.$quoted.'\b(?:\s+\w+){0,3}\s+\b(?:not|never)\s+(?:reported|present|found|measured)\b/u',
                $haystack,
            ) === 1;
    }

    public function measurementWindowAddressesProperty(string $text, string $number, string $propertyKey): bool
    {
        $propertyKey = mb_strtolower(trim($propertyKey));
        if ($propertyKey === '' || in_array($propertyKey, ['general', 'definition'], true)) {
            return true;
        }

        $terms = $this->propertyAddressTerms($propertyKey);
        if ($terms === []) {
            return true;
        }

        $hay = mb_strtolower($text);
        if ($this->haystackAddressesPropertyTerms($hay, $terms)) {
            return true;
        }

        $digits = preg_replace('/[^\d.,]/', '', $number) ?? '';
        $pos = $digits !== '' ? mb_strpos($hay, mb_strtolower($digits)) : false;
        if ($pos === false) {
            return false;
        }

        $start = max(0, $pos - 120);
        $window = mb_substr($hay, $start, 240);

        return $this->haystackAddressesPropertyTerms($window, $terms);
    }

    /**
     * @return list<string>
     */
    public function propertyAddressTerms(string $property): array
    {
        $aliases = [
            'quantity' => ['quantity', 'production', 'produced', 'output', 'tonnage', 'tons', 'tonnes'],
            'yield' => ['yield', 'productivity', 'productive', 't/ha', 'kg/ha'],
            'area' => ['area', 'harvested', 'hectare', 'hectares', 'ha'],
            'rate' => ['rate', 'dosage', 'seed rate', 'seeding'],
            'irrigation' => ['irrigation', 'water requirement', 'watering'],
            'temperature' => ['temperature', 'celsius', '°c', 'deg c'],
            'production' => ['production', 'quantity', 'produced', 'output', 'tons', 'tonnes'],
            'concentration' => ['concentration', 'ppm', 'mg/l', 'salinity'],
        ];

        $terms = $aliases[$property] ?? [];
        $terms[] = $property;

        return array_values(array_unique(array_map(
            static fn (string $term): string => mb_strtolower(trim($term)),
            $terms,
        )));
    }

    public function measurementUnitClass(string $unit): string
    {
        $unit = mb_strtolower(trim($unit));

        return match (true) {
            in_array($unit, ['°c', 'deg c', 'celsius', 'kelvin', 'c'], true) => 'temperature',
            in_array($unit, ['ppm', 'mg/l', 'mg/kg', '%', 'ph'], true) => 'concentration',
            in_array($unit, ['kg/ha', 'kg ha-1', 't/ha', 'kg/day', 'kg d-1', 'l/day', 'mm/day', 'm3/ha', 'mm'], true) => 'rate',
            in_array($unit, ['ha', 'hectare', 'hectares'], true) => 'area',
            in_array($unit, ['kg', 'g', 'mg', 'l', 'ml', 'tons', 'ton', 'tonnes', 'tonne', 't'], true) => 'quantity',
            in_array($unit, ['days', 'day', 'weeks', 'week', 'months', 'month'], true) => 'time',
            default => 'quantity',
        };
    }

    /**
     * @return list<string>|null
     */
    public function requestedPropertyUnitClasses(string $target): ?array
    {
        return match ($target) {
            'temperature', 'range' => ['temperature'],
            'concentration' => ['concentration'],
            'quantity', 'yield', 'rate', 'production', 'irrigation', 'requirements' => ['quantity', 'rate'],
            'area' => ['area'],
            'classification', 'species', 'definition', 'causes', 'symptoms', 'comparison' => [],
            default => null,
        };
    }

    private function planWithClaimProperty(KnowledgeQueryPlan $plan, string $property): KnowledgeQueryPlan
    {
        if ($property === '') {
            return $plan;
        }

        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        $constraints['requested_property'] = $property;
        if (! isset($constraints['requested_property_query_terms'])
            || ! is_array($constraints['requested_property_query_terms'])
            || $constraints['requested_property_query_terms'] === []) {
            $constraints['requested_property_query_terms'] = $this->propertyAddressTerms($property);
        }
        $constraints['requested_property_surface'] = $constraints['requested_property_surface']
            ?? $property;

        $q = $plan->normalizedQuery;

        return new KnowledgeQueryPlan(
            normalizedQuery: new AgriculturalKnowledgeQuery(
                originalQuestion: $q->originalQuestion,
                normalizedQuestion: $q->normalizedQuestion,
                language: $q->language,
                agriculturalDomain: $q->agriculturalDomain,
                subject: $q->subject,
                crop: $q->crop,
                cropId: $q->cropId,
                scientificName: $q->scientificName,
                topic: $q->topic,
                subtopic: $q->subtopic,
                requestedInformation: $q->requestedInformation,
                constraints: $constraints,
                location: $q->location,
                researchRequired: $q->researchRequired,
                ambiguityState: $q->ambiguityState,
                clarificationRequirements: $q->clarificationRequirements,
                researchIntent: $q->researchIntent,
            ),
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $plan->topics,
            subtopics: $plan->subtopics,
            requestedInformation: $plan->requestedInformation,
            evidenceRequirements: $plan->evidenceRequirements,
            sourcePriorities: $plan->sourcePriorities,
            primaryResearchStrategy: $plan->primaryResearchStrategy,
            researchSequence: $plan->researchSequence,
            ambiguityState: $plan->ambiguityState,
            clarificationRequirements: $plan->clarificationRequirements,
            contextInput: $plan->contextInput,
            readyForStage3: $plan->readyForStage3,
        );
    }

    private function textContainsMeasurementCandidate(string $text): bool
    {
        return preg_match(
            '/\d+(?:[.,]\d+)?\s*(?:million|billion)?\s*(?:%|kg\/ha|t\/ha|kg|ha|hectares?|mm|cm|m|l|ml|°c|ph|ppm|mg|g|tons?|tonnes?|t|days?|weeks?|months?)\b/iu',
            $text,
        ) === 1;
    }

    /**
     * @param  list<string>  $supportedValues
     */
    private function findingHasUncoveredMeasurement(string $text, array $supportedValues): bool
    {
        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            return false;
        }

        foreach ($matches as $match) {
            $raw = trim((string) ($match[0] ?? ''));
            $number = trim((string) ($match[1] ?? ''));
            if ($raw === '') {
                continue;
            }
            $digits = preg_replace('/[^\d]/', '', $number) ?? '';
            if (preg_match('/^(?:19|20)\d{2}$/', $digits) === 1) {
                continue;
            }
            $covered = false;
            foreach ($supportedValues as $value) {
                if (str_contains(mb_strtolower($value), mb_strtolower($number))
                    || str_contains(mb_strtolower($raw), mb_strtolower($value))) {
                    $covered = true;
                    break;
                }
            }
            if (! $covered) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $supported
     */
    private function hasAmbiguousNumericMeasurements(string $text, array $supported): bool
    {
        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        $classes = [];
        foreach ($matches as $match) {
            $unit = mb_strtolower(trim((string) ($match[2] ?? '')));
            if ($unit === '') {
                continue;
            }
            $classes[$this->measurementUnitClass($unit)] = true;
        }

        if (count($classes) <= 1) {
            return false;
        }

        // Any extra unit class in the evidence prose beyond what was accepted as
        // supported for the claim property is treated as unresolved ambiguity.
        return true;
    }
}
