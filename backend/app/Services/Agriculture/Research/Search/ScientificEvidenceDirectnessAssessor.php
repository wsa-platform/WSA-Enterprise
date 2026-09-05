<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Classifies how directly a scholarly result answers the structured scientific intent.
 *
 * DIRECT — studies the requested entity+topic+sense relationship.
 * SUPPORTING — on-entity/topic but incomplete for the full claim.
 * BACKGROUND — domain-adjacent only; must not become a definitive answer alone.
 * IRRELEVANT — fails relevance / wrong sense / off-topic.
 */
class ScientificEvidenceDirectnessAssessor
{
    public const DIRECT = 'direct';

    public const SUPPORTING = 'supporting';

    /** Verification-layer alias of SUPPORTING (DIRECT/SUPPORTED/RELATED labels). */
    public const SUPPORTED = 'supported';

    public const BACKGROUND = 'background';

    /** Verification-layer alias of BACKGROUND. */
    public const RELATED = 'related';

    public const IRRELEVANT = 'irrelevant';

    /** Study-country mismatch vs asked location (publisher geo must not trigger this alone). */
    public const GEOGRAPHIC_MISMATCH = 'geographic_mismatch';

    public function __construct(
        private ScientificEvidenceRelevanceGate $relevanceGate,
    ) {}

    /**
     * @return array{
     *     directness: string,
     *     score: float,
     *     reasons: list<string>,
     *     factor_coverage: float,
     *     sense_coverage: bool,
     *     entity_matched: bool,
     *     topic_matched: bool
     * }
     */
    public function assess(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
    ): array {
        $relevance = $this->relevanceGate->assess($plan, $title, $abstract, $doi, $extraText);
        if (! $relevance['relevant']) {
            return [
                'directness' => self::IRRELEVANT,
                'score' => 0.0,
                'reasons' => $relevance['rejection_reasons'] !== []
                    ? $relevance['rejection_reasons']
                    : ['relevance_gate_rejected'],
                'factor_coverage' => 0.0,
                'sense_coverage' => false,
                'entity_matched' => (bool) $relevance['entity_matched'],
                'topic_matched' => (bool) $relevance['topic_matched'],
            ];
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $title,
            $abstract,
            $extraText,
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));

        $factorHits = 0;
        foreach ($factors as $factor) {
            if ($this->factorPresentInHaystack((string) $factor, $sense, $haystack)) {
                $factorHits++;
            }
        }
        $factorCoverage = $factors === [] ? 1.0 : ($factorHits / max(count($factors), 1));

        $senseCoverage = $sense === '' || $this->sensePresentInHaystack($sense, $haystack);
        // Intent qualifier answerability is required for DIRECT — never auto-true.
        $qualifierCoverage = $qualifier === '' || $qualifier === 'general'
            || $this->qualifierPresentInHaystack($qualifier, $haystack);

        $reasons = [];
        $entityMatched = (bool) $relevance['entity_matched'];
        $topicMatched = (bool) $relevance['topic_matched'];
        $senseMatched = (bool) ($relevance['sense_matched'] ?? false);
        $contextMatched = (bool) ($relevance['context_matched'] ?? false);

        // Wrong primary sense factor present in query but missing in paper → not DIRECT.
        $requiredSenseFactorMissing = $this->requiredSenseFactorMissing($factors, $haystack);
        $oilDemotion = $this->essentialOilPrimaryDemotion($plan, $haystack);

        if ($entityMatched && $topicMatched && $senseCoverage && ! $requiredSenseFactorMissing
            && ($senseMatched || $contextMatched || $factorCoverage >= 0.99)
            && $qualifierCoverage) {
            // Germination / growth+temperature: essential-oil papers are not primary DIRECT answers.
            if ($oilDemotion === 'supporting') {
                return [
                    'directness' => self::SUPPORTING,
                    'score' => 14.0 + (8.0 * $factorCoverage),
                    'reasons' => [$this->essentialOilDemotionReason($plan, 'supporting')],
                    'factor_coverage' => round($factorCoverage, 3),
                    'sense_coverage' => $senseCoverage,
                    'entity_matched' => true,
                    'topic_matched' => true,
                ];
            }
            if ($oilDemotion === 'background') {
                return [
                    'directness' => self::BACKGROUND,
                    'score' => 4.0,
                    'reasons' => [$this->essentialOilDemotionReason($plan, 'background')],
                    'factor_coverage' => round($factorCoverage, 3),
                    'sense_coverage' => $senseCoverage,
                    'entity_matched' => true,
                    'topic_matched' => true,
                ];
            }

            $directScore = 40.0 + (20.0 * $factorCoverage);
            $reasons = ['entity_topic_sense_aligned'];
            if ($this->isGerminationIntent($plan) && $this->hasGerminationEvidenceSignals($haystack)) {
                $directScore += 6.0;
                $reasons[] = 'germination_evidence_preferred';
            }
            if ($this->isPlantGrowthTemperatureIntent($plan) && $this->hasPlantGrowthEvidenceSignals($haystack)) {
                $directScore += 6.0;
                $reasons[] = 'growth_evidence_preferred';
            }

            return [
                'directness' => self::DIRECT,
                'score' => $directScore,
                'reasons' => $reasons,
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => true,
                'topic_matched' => true,
            ];
        }

        if ($entityMatched && $topicMatched && ! $requiredSenseFactorMissing) {
            if ($oilDemotion === 'background') {
                return [
                    'directness' => self::BACKGROUND,
                    'score' => 3.0,
                    'reasons' => [$this->essentialOilDemotionReason($plan, 'background')],
                    'factor_coverage' => round($factorCoverage, 3),
                    'sense_coverage' => $senseCoverage,
                    'entity_matched' => true,
                    'topic_matched' => true,
                ];
            }

            $reasons[] = $qualifierCoverage
                ? 'entity_topic_partial_sense'
                : 'missing_qualifier_answerability';
            if ($oilDemotion === 'supporting') {
                $reasons[] = $this->essentialOilDemotionReason($plan, 'supporting');
            }

            return [
                'directness' => self::SUPPORTING,
                'score' => 18.0 + (12.0 * $factorCoverage) - ($oilDemotion === 'supporting' ? 6.0 : 0.0),
                'reasons' => $reasons,
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => true,
                'topic_matched' => true,
            ];
        }

        if ($entityMatched && $topicMatched && $requiredSenseFactorMissing) {
            if ($oilDemotion === 'background' || $oilDemotion === 'supporting') {
                return [
                    'directness' => $oilDemotion === 'background' ? self::BACKGROUND : self::SUPPORTING,
                    'score' => $oilDemotion === 'background' ? 3.0 : 8.0,
                    'reasons' => [
                        'missing_required_sense_factor',
                        $this->essentialOilDemotionReason($plan, $oilDemotion),
                    ],
                    'factor_coverage' => round($factorCoverage, 3),
                    'sense_coverage' => false,
                    'entity_matched' => true,
                    'topic_matched' => true,
                ];
            }

            return [
                'directness' => self::SUPPORTING,
                'score' => 12.0,
                'reasons' => ['missing_required_sense_factor'],
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => false,
                'entity_matched' => true,
                'topic_matched' => true,
            ];
        }

        // General (non crop+topic) relevant hits are usable supporting evidence — not BACKGROUND.
        if ($relevance['relevant'] && (! $relevance['requires_entity'] || ! $relevance['requires_topic'])) {
            return [
                'directness' => self::SUPPORTING,
                'score' => 16.0 + (10.0 * $factorCoverage),
                'reasons' => ['general_relevant_evidence'],
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => $entityMatched,
                'topic_matched' => $topicMatched,
            ];
        }

        if ($entityMatched || $topicMatched) {
            return [
                'directness' => self::BACKGROUND,
                'score' => 4.0,
                'reasons' => ['partial_overlap_only'],
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => $entityMatched,
                'topic_matched' => $topicMatched,
            ];
        }

        return [
            'directness' => self::BACKGROUND,
            'score' => 2.0,
            'reasons' => ['weak_overlap'],
            'factor_coverage' => round($factorCoverage, 3),
            'sense_coverage' => $senseCoverage,
            'entity_matched' => $entityMatched,
            'topic_matched' => $topicMatched,
        ];
    }

    /**
     * @param  list<string>  $factors
     */
    private function requiredSenseFactorMissing(array $factors, string $haystack): bool
    {
        foreach (['germination', 'drying', 'storage'] as $critical) {
            if (! in_array($critical, $factors, true)) {
                continue;
            }
            if (! $this->factorPresentInHaystack($critical, $critical === 'germination' ? 'seed_germination' : $critical, $haystack)) {
                return true;
            }
        }

        return false;
    }

    private function factorPresentInHaystack(string $factor, string $sense, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::scientificSynonymsForFactor($factor, $sense !== '' ? $sense : null) as $synonym) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($synonym)))) {
                return true;
            }
        }

        $label = AgriculturalEntityCatalog::topicFactorEnglishLabels()[$factor] ?? $factor;

        return AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($label)));
    }

    private function sensePresentInHaystack(string $sense, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::senseQueryTerms($sense) as $term) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                return true;
            }
        }

        // Growth/physiology sense also accepts agricultural context markers.
        if ($sense === 'plant_growth') {
            foreach (AgriculturalEntityCatalog::agriculturalContextSignals() as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function qualifierPresentInHaystack(string $qualifier, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        $signals = AgriculturalEntityCatalog::intentQualifierSignals()[$qualifier] ?? [];
        foreach ($signals as $signal) {
            $normalized = mb_strtolower(trim($signal));
            if ($normalized === '') {
                continue;
            }
            if ($this->signalPresentPositively($haystack, $normalized)) {
                return true;
            }
        }

        // Semantic answerability — never auto-true for effect/requirement/optimal_range.
        return match ($qualifier) {
            'optimal_range' => $this->hasOptimalRangeAnswerability($haystack),
            'effect' => $this->hasEffectAnswerability($haystack),
            'requirement' => $this->hasRequirementAnswerability($haystack),
            default => false,
        };
    }

    /**
     * True when $signal appears and is not under local negation
     * ("without … optima", "no optimal", "not reporting °C", etc.).
     */
    private function signalPresentPositively(string $haystack, string $signal): bool
    {
        if (! AgriculturalEntityCatalog::containsTerm($haystack, $signal)
            && mb_strpos($haystack, $signal) === false) {
            return false;
        }

        $quoted = preg_quote($signal, '/');
        if (preg_match(
            '/\b(?:without|no|not|never|lacks?|absent|missing|neither|nor)\b[\s\w\-,]{0,48}'.$quoted.'/u',
            $haystack,
        ) === 1) {
            return false;
        }

        return true;
    }

    private function hasOptimalRangeAnswerability(string $haystack): bool
    {
        // Temperature / numeric range signals (°C, C, ranges, between X and Y).
        if (preg_match('/\d+(?:[.,]\d+)?\s*(?:°\s*)?[cCfF]\b/u', $haystack) === 1
            && preg_match('/\b(?:without|no|not|never)\b[\s\w\-,]{0,40}\d+(?:[.,]\d+)?\s*(?:°\s*)?[cCfF]\b/u', $haystack) !== 1) {
            return true;
        }
        if (preg_match('/\b(?:celsius|centigrade|fahrenheit)\b/u', $haystack) === 1) {
            return true;
        }
        if (preg_match('/\b\d+(?:[.,]\d+)?\s*[-–—]\s*\d+(?:[.,]\d+)?/u', $haystack) === 1) {
            return true;
        }
        if (preg_match('/\bbetween\s+\d+(?:[.,]\d+)?\s+and\s+\d+(?:[.,]\d+)?/u', $haystack) === 1) {
            return true;
        }
        if ($this->signalPresentPositively($haystack, 'temperature range')
            || $this->signalPresentPositively($haystack, 'thermal range')) {
            return true;
        }

        return false;
    }

    private function hasEffectAnswerability(string $haystack): bool
    {
        return preg_match(
            '/\b(?:affect(?:s|ed|ing)?|effect(?:s)?|impact(?:s|ed|ing)?|influenc(?:e|es|ed|ing)|respons(?:e|es)|increas(?:e|es|ed|ing)|decreas(?:e|es|ed|ing)|reduc(?:e|es|ed|ing)|improv(?:e|es|ed|ing)|inhibit(?:s|ed|ing)?)\b/u',
            $haystack,
        ) === 1;
    }

    private function hasRequirementAnswerability(string $haystack): bool
    {
        return preg_match(
            '/\b(?:requirement(?:s)?|required|requir(?:e|es|ing)|need(?:s|ed|ing)?|demand(?:s)?|evapotranspiration|crop\s+water)\b/u',
            $haystack,
        ) === 1;
    }

    /**
     * Demote essential-oil primary papers for germination and plant_growth+temperature intents.
     * Oils questions are never demoted.
     *
     * @return 'supporting'|'background'|null
     */
    private function essentialOilPrimaryDemotion(KnowledgeQueryPlan $plan, string $haystack): ?string
    {
        if ($haystack === '') {
            return null;
        }

        $isGermination = $this->isGerminationIntent($plan);
        $isGrowthTemp = $this->isPlantGrowthTemperatureIntent($plan);
        if (! $isGermination && ! $isGrowthTemp) {
            return null;
        }

        $questionHay = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));
        if (AgriculturalEntityCatalog::userAskedAboutOils($questionHay)) {
            return null;
        }

        if (! $this->hasEssentialOilPrimary($haystack)) {
            return null;
        }

        // Soft demote when on-intent evidence is also present; background when oil-only / off-topic.
        if ($isGermination) {
            return $this->hasGerminationEvidenceSignals($haystack) ? 'supporting' : 'background';
        }

        return $this->hasPlantGrowthEvidenceSignals($haystack) ? 'supporting' : 'background';
    }

    private function essentialOilDemotionReason(KnowledgeQueryPlan $plan, string $level): string
    {
        if ($this->isGerminationIntent($plan)) {
            return $level === 'background'
                ? 'essential_oil_off_topic_for_germination'
                : 'essential_oil_primary_demoted_for_germination';
        }

        return $level === 'background'
            ? 'essential_oil_off_topic_for_growth'
            : 'essential_oil_primary_demoted_for_growth';
    }

    private function isGerminationIntent(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'seed_germination') {
            return true;
        }

        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];

        return in_array('germination', $factors, true);
    }

    private function isPlantGrowthTemperatureIntent(KnowledgeQueryPlan $plan): bool
    {
        // Germination owns its demotion path; do not double-apply growth rules.
        if ($this->isGerminationIntent($plan)) {
            return false;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];

        return $sense === 'plant_growth' && in_array('temperature', $factors, true);
    }

    private function hasGerminationEvidenceSignals(string $haystack): bool
    {
        foreach (AgriculturalEntityCatalog::germinationEvidenceSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Growth/physiology evidence (excludes bare "yield" which oil-yield papers often contain).
     */
    private function hasPlantGrowthEvidenceSignals(string $haystack): bool
    {
        foreach ([
            'plant growth', 'rhizome growth', 'vegetative growth', 'shoot growth',
            'root growth', 'growth rate', 'physiology', 'cultivation',
            'biomass accumulation', 'plant physiology',
        ] as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $signal)
                || mb_strpos($haystack, $signal) !== false) {
                return true;
            }
        }

        // Standalone "growth" that is not oil/yield framing.
        if (AgriculturalEntityCatalog::containsTerm($haystack, 'growth')
            && ! preg_match('/\b(?:oil|essential|volatile)\s+growth\b/u', $haystack)) {
            return true;
        }

        return false;
    }

    private function hasEssentialOilPrimary(string $haystack): bool
    {
        foreach (AgriculturalEntityCatalog::essentialOilPrimaryMarkers() as $marker) {
            $normalized = mb_strtolower(trim($marker));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                || mb_strpos($haystack, $normalized) !== false
            )) {
                return true;
            }
        }

        return false;
    }
}
