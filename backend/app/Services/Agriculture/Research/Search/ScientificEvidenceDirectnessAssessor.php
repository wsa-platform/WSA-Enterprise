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

    public const BACKGROUND = 'background';

    public const IRRELEVANT = 'irrelevant';

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
        $qualifierCoverage = $qualifier === '' || $qualifier === 'general'
            || $this->qualifierPresentInHaystack($qualifier, $haystack);

        $reasons = [];
        $entityMatched = (bool) $relevance['entity_matched'];
        $topicMatched = (bool) $relevance['topic_matched'];
        $senseMatched = (bool) ($relevance['sense_matched'] ?? false);
        $contextMatched = (bool) ($relevance['context_matched'] ?? false);

        // Wrong primary sense factor present in query but missing in paper → not DIRECT.
        $requiredSenseFactorMissing = $this->requiredSenseFactorMissing($factors, $haystack);

        if ($entityMatched && $topicMatched && $senseCoverage && ! $requiredSenseFactorMissing
            && ($senseMatched || $contextMatched || $factorCoverage >= 0.99)
            && ($qualifierCoverage || $factorCoverage >= 0.99)) {
            return [
                'directness' => self::DIRECT,
                'score' => 40.0 + (20.0 * $factorCoverage),
                'reasons' => ['entity_topic_sense_aligned'],
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => true,
                'topic_matched' => true,
            ];
        }

        if ($entityMatched && $topicMatched && ! $requiredSenseFactorMissing) {
            $reasons[] = 'entity_topic_partial_sense';

            return [
                'directness' => self::SUPPORTING,
                'score' => 18.0 + (12.0 * $factorCoverage),
                'reasons' => $reasons,
                'factor_coverage' => round($factorCoverage, 3),
                'sense_coverage' => $senseCoverage,
                'entity_matched' => true,
                'topic_matched' => true,
            ];
        }

        if ($entityMatched && $topicMatched && $requiredSenseFactorMissing) {
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
        $signals = AgriculturalEntityCatalog::intentQualifierSignals()[$qualifier] ?? [];
        foreach ($signals as $signal) {
            if (preg_match('/\p{Arabic}/u', $signal) === 1) {
                continue;
            }
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        // English scholarly abstracts rarely repeat "optimal"; effect/requirement language is enough.
        return in_array($qualifier, ['effect', 'requirement', 'optimal_range'], true);
    }
}
