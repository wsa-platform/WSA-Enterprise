<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Generic claim-to-evidence matching without inventing support.
 *
 * Incorporates evidence directness so background/irrelevant hits cannot
 * become SUPPORTED claims for the user's question.
 */
class ClaimEvidenceMatcher
{
    public function __construct(
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
    ) {}

    /**
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    public function match(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        ?string $evidenceText,
        string $validationStatus,
    ): array {
        $match = $this->matchCore($plan, $result, $evidenceText, $validationStatus);

        return $this->applyPrimaryEvidenceIntentFactors($plan, $result->title, $evidenceText, $match);
    }

    /**
     * Prefer germination/growth+temperature evidence; demote essential-oil primary unless oils asked.
     *
     * @param  array{relationship: string, confidence: float, factors: array<string, mixed>}  $match
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    public function applyPrimaryEvidenceIntentFactors(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $evidenceText,
        array $match,
    ): array {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        $isGermination = $sense === 'seed_germination' || in_array('germination', $factors, true);
        $isGrowthTemp = ! $isGermination
            && $sense === 'plant_growth'
            && in_array('temperature', $factors, true);
        if (! $isGermination && ! $isGrowthTemp) {
            return $match;
        }

        $questionHay = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));
        if (AgriculturalEntityCatalog::userAskedAboutOils($questionHay)) {
            return $match;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $title,
            $evidenceText,
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        $hasOnIntent = false;
        if ($isGermination) {
            foreach (AgriculturalEntityCatalog::germinationEvidenceSignals() as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                    $hasOnIntent = true;
                    break;
                }
            }
        } else {
            foreach ([
                'plant growth', 'rhizome growth', 'vegetative growth', 'shoot growth',
                'root growth', 'growth rate', 'physiology', 'cultivation',
                'biomass accumulation', 'plant physiology', 'growth',
            ] as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, $signal)
                    || mb_strpos($haystack, $signal) !== false) {
                    $hasOnIntent = true;
                    break;
                }
            }
        }

        $hasOil = false;
        foreach (AgriculturalEntityCatalog::essentialOilPrimaryMarkers() as $marker) {
            $normalized = mb_strtolower(trim($marker));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                || mb_strpos($haystack, $normalized) !== false
            )) {
                $hasOil = true;
                break;
            }
        }

        $matchFactors = is_array($match['factors'] ?? null) ? $match['factors'] : [];
        if ($hasOnIntent) {
            if ($isGermination) {
                $matchFactors['germination_evidence_preferred'] = true;
            } else {
                $matchFactors['growth_evidence_preferred'] = true;
            }
        }
        if ($hasOil) {
            $matchFactors['essential_oil_primary_demotion'] = true;
            $match['confidence'] = max(0.05, ((float) ($match['confidence'] ?? 0.0)) * ($hasOnIntent ? 0.55 : 0.35));
            // Oil-only leftovers should not stay SUPPORTED as primary germination/growth answers.
            if (! $hasOnIntent && ($match['relationship'] ?? '') === ClaimEvidenceRelationship::SUPPORTED) {
                $match['relationship'] = ClaimEvidenceRelationship::PARTIALLY_SUPPORTED;
            }
        }
        $match['factors'] = $matchFactors;

        return $match;
    }

    /**
     * @deprecated Prefer applyPrimaryEvidenceIntentFactors; kept for call-site compatibility.
     *
     * @param  array{relationship: string, confidence: float, factors: array<string, mixed>}  $match
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    public function applyGerminationPrimaryFactors(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $evidenceText,
        array $match,
    ): array {
        return $this->applyPrimaryEvidenceIntentFactors($plan, $title, $evidenceText, $match);
    }

    /**
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    private function matchCore(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        ?string $evidenceText,
        string $validationStatus,
    ): array {
        if ($validationStatus === EvidenceValidationStatus::REJECTED) {
            return [
                'relationship' => ClaimEvidenceRelationship::NOT_VALIDATED,
                'confidence' => 0.0,
                'factors' => ['reason' => 'validation_rejected'],
            ];
        }

        if ($evidenceText === null || trim($evidenceText) === '') {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.0,
                'factors' => ['reason' => 'no_evidence_text'],
            ];
        }

        $assessment = $this->relevanceGate->assess(
            $plan,
            $result->title,
            $evidenceText,
            $result->doi,
        );
        $directness = $this->directnessAssessor->assess(
            $plan,
            $result->title,
            $evidenceText,
            $result->doi,
        );

        if (! $assessment['relevant']
            || $directness['directness'] === ScientificEvidenceDirectnessAssessor::IRRELEVANT) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.05,
                'factors' => [
                    'reason' => 'relevance_gate_rejected',
                    'rejection_reasons' => $assessment['rejection_reasons'],
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'evidence_directness' => $directness['directness'],
                    'doi_alone_insufficient' => true,
                ],
            ];
        }

        if ($directness['directness'] === ScientificEvidenceDirectnessAssessor::BACKGROUND) {
            // Background leftovers must not answer crop+topic questions; general queries keep partial path.
            if ($assessment['requires_entity'] && $assessment['requires_topic']) {
                return [
                    'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                    'confidence' => 0.12,
                    'factors' => [
                        'reason' => 'background_evidence_only',
                        'evidence_directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                        'entity_matched' => $assessment['entity_matched'],
                        'topic_matched' => $assessment['topic_matched'],
                        'directness_reasons' => $directness['reasons'],
                    ],
                ];
            }
        }

        $queryTerms = $this->terms($this->queryText($plan));
        $evidenceTerms = $this->terms($evidenceText);
        $askedLocation = $this->askedLocation($plan);

        // Country/geo terms only count toward support when the user asked for a location.
        if ($askedLocation === null) {
            $queryTerms = $this->withoutUnaskedGeoTerms($queryTerms);
            $evidenceTerms = $this->withoutUnaskedGeoTerms($evidenceTerms);
        }

        if ($queryTerms === []) {
            if ($assessment['requires_entity'] && $assessment['requires_topic'] && ! ($assessment['context_adequate'] ?? false)) {
                return [
                    'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                    'confidence' => 0.08,
                    'factors' => [
                        'matched_terms' => 0,
                        'query_terms' => 0,
                        'entity_matched' => $assessment['entity_matched'],
                        'topic_matched' => $assessment['topic_matched'],
                        'context_adequate' => false,
                        'evidence_directness' => $directness['directness'],
                        'reason' => 'insufficient_topic_sense',
                    ],
                ];
            }

            return [
                'relationship' => $assessment['entity_matched'] || $assessment['topic_matched']
                    ? ClaimEvidenceRelationship::PARTIALLY_SUPPORTED
                    : ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.4,
                'factors' => [
                    'matched_terms' => 0,
                    'query_terms' => 0,
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'context_adequate' => (bool) ($assessment['context_adequate'] ?? false),
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        $matched = array_values(array_intersect($queryTerms, $evidenceTerms));
        $matchRatio = count($matched) / max(count($queryTerms), 1);
        $synonymSupport = $this->synonymFactorSupport($plan, $evidenceText);

        $contextAdequate = (bool) ($assessment['context_adequate'] ?? false);
        $senseMatched = (bool) ($assessment['sense_matched'] ?? false);
        $contextMatched = (bool) ($assessment['context_matched'] ?? false);
        $isDirect = $directness['directness'] === ScientificEvidenceDirectnessAssessor::DIRECT;
        $strictCropTopic = $assessment['requires_entity'] && $assessment['requires_topic'];
        $qualifier = trim((string) ($plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? ''));

        // Strict reject: crop+topic evidence that fails sense/context and lacks synonym factor support.
        if ($strictCropTopic && ! $contextAdequate && ! $synonymSupport) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.08,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'context_adequate' => false,
                    'evidence_directness' => $directness['directness'],
                    'reason' => 'insufficient_topic_sense',
                ],
            ];
        }

        // Entity ∩ weak topic alone is not enough for SUPPORTED on crop+topic questions.
        // DIRECT requires real qualifier answerability (e.g. °C for optimal_range).
        if ($strictCropTopic && $assessment['entity_matched']
            && $assessment['topic_matched']
            && $contextAdequate
            && $isDirect
            && ($senseMatched || $contextMatched || $matchRatio >= 0.35 || $synonymSupport)) {
            return [
                'relationship' => ClaimEvidenceRelationship::SUPPORTED,
                'confidence' => min(0.95, 0.55 + ($matchRatio * 0.4)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'sense_matched' => $senseMatched,
                    'context_matched' => $contextMatched,
                    'context_adequate' => true,
                    'synonym_support' => $synonymSupport,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                ],
            ];
        }

        if ($strictCropTopic && ! $contextAdequate) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.08,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'context_adequate' => false,
                    'evidence_directness' => $directness['directness'],
                    'reason' => 'insufficient_topic_sense',
                ],
            ];
        }

        // Optimal-range questions: keyword overlap without DIRECT answerability is not SUPPORTED.
        if ($strictCropTopic && $qualifier === 'optimal_range' && ! $isDirect) {
            if ($matchRatio >= 0.25 || $synonymSupport || ($assessment['entity_matched'] && $assessment['topic_matched'])) {
                return [
                    'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                    'confidence' => min(0.55, 0.2 + ($matchRatio * 0.35)),
                    'factors' => [
                        'matched_terms' => count($matched),
                        'query_terms' => count($queryTerms),
                        'match_ratio' => round($matchRatio, 3),
                        'entity_matched' => $assessment['entity_matched'],
                        'topic_matched' => $assessment['topic_matched'],
                        'evidence_directness' => $directness['directness'],
                        'reason' => 'optimal_range_lacks_answerability',
                    ],
                ];
            }

            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.1,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'evidence_directness' => $directness['directness'],
                    'reason' => 'non_supporting_optimal_range',
                ],
            ];
        }

        if ($matchRatio >= 0.6 && (! $assessment['requires_entity'] || $assessment['entity_matched'])
            && (! $strictCropTopic || $isDirect)) {
            return [
                'relationship' => ClaimEvidenceRelationship::SUPPORTED,
                'confidence' => min(0.95, 0.5 + ($matchRatio * 0.45)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        // Non-crop / non-topic-constrained questions: restore classic overlap SUPPORTED path.
        if (! $strictCropTopic && $matchRatio >= 0.6) {
            return [
                'relationship' => ClaimEvidenceRelationship::SUPPORTED,
                'confidence' => min(0.95, 0.5 + ($matchRatio * 0.45)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        if ($matchRatio < 0.15 && ! $assessment['entity_matched'] && ! $assessment['topic_matched'] && ! $synonymSupport) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.1,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'low_relevance' => true,
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        if ($directness['directness'] === ScientificEvidenceDirectnessAssessor::SUPPORTING
            || $matchRatio >= 0.25
            || $synonymSupport
            || ($assessment['entity_matched'] && ! $assessment['requires_topic'])) {
            return [
                'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                'confidence' => min(0.7, 0.25 + ($matchRatio * 0.45)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'synonym_support' => $synonymSupport,
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        if ($assessment['entity_matched'] && $assessment['topic_matched']) {
            return [
                'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                'confidence' => 0.45,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        if (($result->relevanceScore ?? 0.0) < 1.0 && $matchRatio < 0.1) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.1,
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                    'low_relevance' => true,
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        return [
            'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            'confidence' => 0.2,
            'factors' => [
                'matched_terms' => count($matched),
                'query_terms' => count($queryTerms),
                'match_ratio' => round($matchRatio, 3),
                'entity_matched' => $assessment['entity_matched'],
                'topic_matched' => $assessment['topic_matched'],
                'evidence_directness' => $directness['directness'],
            ],
        ];
    }

    private function queryText(KnowledgeQueryPlan $plan): string
    {
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $topicText = is_array($topics) ? implode(' ', $topics) : '';
        $sense = (string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? '');
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        $synonymText = [];
        foreach ($factors as $factor) {
            foreach (AgriculturalEntityCatalog::scientificSynonymsForFactor((string) $factor, $sense !== '' ? $sense : null) as $synonym) {
                $synonymText[] = $synonym;
            }
        }
        $productionSystem = trim((string) ($plan->normalizedQuery->constraints['production_system'] ?? ''));
        $location = $this->askedLocation($plan);

        $parts = array_filter([
            $plan->researchIntent,
            $plan->agriculturalDomain,
            $plan->normalizedQuery->cropId,
            $plan->normalizedQuery->scientificName,
            $topicText,
            $sense,
            $productionSystem !== '' ? $productionSystem : null,
            $location,
            implode(' ', $synonymText),
            // Prefer controlled English terms over raw Arabic question text.
            preg_match('/\p{Arabic}/u', $plan->normalizedQuery->normalizedQuestion) === 1
                ? null
                : $plan->normalizedQuery->normalizedQuestion,
        ]);

        return implode(' ', $parts);
    }

    private function askedLocation(KnowledgeQueryPlan $plan): ?string
    {
        $fromQuery = trim((string) ($plan->normalizedQuery->location ?? ''));
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        $fromConstraints = trim((string) ($plan->normalizedQuery->constraints['location'] ?? ''));

        return $fromConstraints !== '' ? $fromConstraints : null;
    }

    /**
     * Synonym-aware factor support: at least one scientific factor synonym appears in evidence.
     */
    private function synonymFactorSupport(KnowledgeQueryPlan $plan, string $evidenceText): bool
    {
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        if ($factors === []) {
            return false;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $haystack = mb_strtolower($evidenceText);
        $hits = 0;
        foreach ($factors as $factor) {
            foreach (AgriculturalEntityCatalog::scientificSynonymsForFactor((string) $factor, $sense !== '' ? $sense : null) as $synonym) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($synonym)))) {
                    $hits++;
                    break;
                }
            }
        }

        return $hits >= 1;
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    private function withoutUnaskedGeoTerms(array $terms): array
    {
        $blocked = [];
        foreach (AgriculturalEntityCatalog::locationAliases() as $alias => $canonical) {
            $blocked[mb_strtolower($alias)] = true;
            $blocked[mb_strtolower($canonical)] = true;
        }

        return array_values(array_filter(
            $terms,
            static fn (string $term): bool => ! isset($blocked[mb_strtolower($term)]),
        ));
    }

    /** @return list<string> */
    private function terms(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_unique(array_filter(
            $parts,
            static fn (string $part): bool => mb_strlen($part) >= 3,
        )));
    }
}
