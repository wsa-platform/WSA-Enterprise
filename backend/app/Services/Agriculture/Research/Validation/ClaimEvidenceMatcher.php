<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;

/**
 * Generic claim-to-evidence matching without inventing support (Phase 4 / R6 claim_relation).
 *
 * Ownership:
 * - Emits claim_relation via `relationship` (→ claim_relationship downstream).
 * - May consume Gate/Directness/EVL outputs; does NOT own disposition, sufficiency, or ranking.
 * - Incorporates evidence directness so background/irrelevant hits cannot become SUPPORTED.
 */
class ClaimEvidenceMatcher
{
    public function __construct(
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
        private EvidenceVerificationLayer $verificationLayer,
        private AnswerExpressionAccuracyGate $expressionAccuracyGate,
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
        $directness = $this->verificationLayer->assess(
            $plan,
            $result,
            $evidenceText,
            $result->doi,
        );

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $result->title,
            $evidenceText,
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        // Land classification ≠ greenhouse/cultivation/ornamental unless real land claims present.
        if ($this->isLandClassificationQuestion($plan) && $this->isLandOfftopicEvidence($haystack)) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.05,
                'factors' => [
                    'reason' => 'land_classification_offtopic_environment_or_crop',
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::IRRELEVANT,
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => false,
                ],
            ];
        }

        if (! $assessment['relevant']
            || $directness['directness'] === ScientificEvidenceDirectnessAssessor::IRRELEVANT
            || $directness['directness'] === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.05,
                'factors' => [
                    'reason' => $directness['directness'] === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH
                        ? 'geographic_mismatch'
                        : 'relevance_gate_rejected',
                    'rejection_reasons' => $assessment['rejection_reasons'],
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'evidence_directness' => $directness['directness'],
                    'doi_alone_insufficient' => true,
                ],
            ];
        }

        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        $propertySurface = trim((string) ($plan->normalizedQuery->constraints['requested_property_surface'] ?? ''));
        $propertyKey = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        $canonicalProperty = $this->canonicalRequestedPropertyIdentity($plan, $propertyKey, $propertySurface);
        $requiresSpecificProperty = $canonicalProperty !== ''
            || $propertySurface !== ''
            || in_array(mb_strtolower($propertyKey), [
                'temperature', 'concentration', 'classification', 'irrigation',
                'quantity', 'yield', 'rate', 'production',
            ], true);
        $propertyAddressed = $requiresSpecificProperty
            && $this->haystackAddressesCanonicalProperty($plan, $haystack, $canonicalProperty, $propertyTerms);
        if ($requiresSpecificProperty && ! $propertyAddressed) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.08,
                'factors' => [
                    'reason' => 'missing_requested_property',
                    'requested_property' => $canonicalProperty !== '' ? $canonicalProperty : ($propertyKey !== '' ? $propertyKey : $propertySurface),
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        // Genus-level relevance is not exact species support for binomial crop claims.
        if ($this->rejectsSpeciesIdentityMismatch($plan, $assessment)) {
            return [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.08,
                'factors' => [
                    'reason' => 'species_identity_mismatch',
                    'entity_matched' => $assessment['entity_matched'],
                    'topic_matched' => $assessment['topic_matched'],
                    'exact_species_matched' => (bool) ($assessment['exact_species_matched'] ?? false),
                    'species_relation' => $assessment['species_relation'] ?? null,
                    'evidence_directness' => $directness['directness'],
                ],
            ];
        }

        if (in_array($directness['directness'], [
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
        ], true)) {
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

    /**
     * @param  array<string, mixed>  $assessment
     */
    private function rejectsSpeciesIdentityMismatch(KnowledgeQueryPlan $plan, array $assessment): bool
    {
        if (! ($assessment['requires_entity'] ?? false)) {
            return false;
        }

        $relation = (string) ($assessment['species_relation'] ?? '');

        return in_array($relation, ['genus_only', 'different_species', 'entity_less'], true);
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

        // Overlap uses canonical roles / controlled terms only — never the raw
        // user-language question surface (AR/FR/TR/EN wording is not evidence).
        $parts = array_filter([
            $plan->researchIntent,
            $plan->agriculturalDomain,
            $plan->normalizedQuery->cropId,
            $plan->normalizedQuery->scientificName,
            is_array($plan->subjectEntity) ? ($plan->subjectEntity['value'] ?? null) : null,
            is_array($plan->subjectEntity) ? ($plan->subjectEntity['label'] ?? null) : null,
            $topicText,
            $sense,
            $productionSystem !== '' ? $productionSystem : null,
            $location,
            implode(' ', $synonymText),
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
     * Canonical scientific property family already frozen on CSQ / RetrievalSpecification.
     * User-language surface phrases are not identity.
     */
    private function canonicalRequestedPropertyIdentity(
        KnowledgeQueryPlan $plan,
        string $propertyKey,
        string $propertySurface,
    ): string {
        $query = $plan->normalizedQuery;
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        $csq = $query->canonicalQuestion;
        if ($csq !== null) {
            $csqSense = trim((string) $csq->context->scientificSense);
            $family = RetrievalSpecification::canonicalFamilyKey(
                $csq->property->key,
                $csq->property->surface,
                $csqSense !== '' ? $csqSense : $sense,
            );
            if ($this->isCanonicalPropertyFamily($family)) {
                return $family;
            }
        }

        $spec = $this->retrievalSpecificationFromPlan($plan);
        if ($spec !== null) {
            $fromRole = match ($spec->scholarlyPropertyRole()) {
                RetrievalSemanticContract::ROLE_TEMPERATURE => 'temperature',
                RetrievalSemanticContract::ROLE_IRRIGATION_WATER => 'irrigation',
                RetrievalSemanticContract::ROLE_CLASSIFICATION => 'classification',
                RetrievalSemanticContract::ROLE_QUANTITY, RetrievalSemanticContract::ROLE_PRODUCTIVITY => 'quantity',
                default => '',
            };
            if ($fromRole !== '') {
                return $fromRole;
            }
        }

        $family = RetrievalSpecification::canonicalFamilyKey($propertyKey, $propertySurface, $sense);
        if ($this->isCanonicalPropertyFamily($family)) {
            return $family;
        }

        $questionType = trim((string) ($query->constraints['question_type'] ?? ''));
        $family = RetrievalSpecification::canonicalFamilyKey($questionType, $propertySurface, $sense);

        return $this->isCanonicalPropertyFamily($family) ? $family : '';
    }

    private function isCanonicalPropertyFamily(?string $family): bool
    {
        $family = mb_strtolower(trim((string) $family));
        if ($family === '') {
            return false;
        }

        return in_array($family, [
            ...RetrievalSpecification::FAMILY_KEYS,
            'concentration', 'yield', 'rate', 'production',
        ], true);
    }

    /**
     * @param  mixed  $surfaceTerms
     */
    private function haystackAddressesCanonicalProperty(
        KnowledgeQueryPlan $plan,
        string $haystack,
        string $canonicalProperty,
        mixed $surfaceTerms,
    ): bool {
        $identityTerms = $this->canonicalPropertyIdentityTerms($plan, $canonicalProperty);
        if ($identityTerms !== []
            && AgriculturalEntityCatalog::haystackAddressesRequestedProperty($haystack, $identityTerms)) {
            return true;
        }

        if ($canonicalProperty !== ''
            && $this->expressionAccuracyGate->haystackAddressesPropertyTerms(
                $haystack,
                $this->expressionAccuracyGate->propertyAddressTerms($canonicalProperty),
            )) {
            return true;
        }

        if ($canonicalProperty === '' && is_array($surfaceTerms) && $surfaceTerms !== []
            && AgriculturalEntityCatalog::haystackAddressesRequestedProperty($haystack, $surfaceTerms)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function canonicalPropertyIdentityTerms(KnowledgeQueryPlan $plan, string $canonicalProperty): array
    {
        $terms = [];
        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        if ($canonicalProperty !== '') {
            $terms = AgriculturalEntityCatalog::requestedPropertyQueryTerms([
                'property_key' => $canonicalProperty,
                'property_surface' => '',
            ], $questionType);
        }

        $spec = $this->retrievalSpecificationFromPlan($plan);
        if ($spec !== null) {
            foreach ($spec->orderedScholarlyPropertyTerms() as $term) {
                $folded = mb_strtolower(trim($term));
                if ($folded === '' || in_array($folded, $terms, true)) {
                    continue;
                }
                if ($canonicalProperty !== ''
                    && RetrievalSpecification::canonicalFamilyKey($folded, $folded) !== $canonicalProperty
                    && $folded !== $canonicalProperty) {
                    continue;
                }
                $terms[] = $term;
            }
        }

        return array_values($terms);
    }

    private function retrievalSpecificationFromPlan(KnowledgeQueryPlan $plan): ?RetrievalSpecification
    {
        $csq = $plan->normalizedQuery->canonicalQuestion;
        if ($csq !== null) {
            return RetrievalSpecification::fromCanonical($csq);
        }
        $raw = $plan->normalizedQuery->constraints['retrieval_specification'] ?? null;
        if (! is_array($raw) || $raw === []) {
            return null;
        }

        return RetrievalSpecification::fromArray($raw);
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

    private function isLandClassificationQuestion(KnowledgeQueryPlan $plan): bool
    {
        $hay = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));
        if (CatalogSafeLandSignals::isLandOrSoilClassificationMethodQuestion($hay)) {
            return false;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'land_classification') {
            return true;
        }

        return preg_match(
            '/land\s*types?|soil\s*classification|land\s*classification|أنواع\s*(?:ال)?أراضي|انواع\s*(?:ال)?اراضي/u',
            $hay,
        ) === 1;
    }

    private function isLandOfftopicEvidence(string $haystack): bool
    {
        if (CatalogSafeLandSignals::hasInventoryContent($haystack)
            && ! CatalogSafeLandSignals::isMethodologyOnlyWithoutInventory($haystack)) {
            return false;
        }

        // Method/ML/GIS without inventory is RELATED (handled by directness), not hard offtopic.
        if (CatalogSafeLandSignals::isMethodologyOnlyWithoutInventory($haystack)) {
            return false;
        }

        foreach (CatalogSafeLandSignals::offtopicMarkers() as $marker) {
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
