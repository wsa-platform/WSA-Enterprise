<?php

namespace App\Services\Agriculture\Research\Validation;

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

        $contextAdequate = (bool) ($assessment['context_adequate'] ?? false);
        $senseMatched = (bool) ($assessment['sense_matched'] ?? false);
        $contextMatched = (bool) ($assessment['context_matched'] ?? false);
        $isDirect = $directness['directness'] === ScientificEvidenceDirectnessAssessor::DIRECT;
        $strictCropTopic = $assessment['requires_entity'] && $assessment['requires_topic'];

        // Entity ∩ weak topic alone is not enough for SUPPORTED on crop+topic questions.
        if ($strictCropTopic && $assessment['entity_matched']
            && $assessment['topic_matched']
            && $contextAdequate
            && $isDirect
            && ($senseMatched || $contextMatched || $matchRatio >= 0.35)) {
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

        if ($matchRatio < 0.15 && ! $assessment['entity_matched'] && ! $assessment['topic_matched']) {
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

        $parts = array_filter([
            $plan->researchIntent,
            $plan->agriculturalDomain,
            $plan->normalizedQuery->cropId,
            $plan->normalizedQuery->scientificName,
            $topicText,
            $sense,
            // Prefer controlled English terms over raw Arabic question text.
            preg_match('/\p{Arabic}/u', $plan->normalizedQuery->normalizedQuestion) === 1
                ? null
                : $plan->normalizedQuery->normalizedQuestion,
        ]);

        return implode(' ', $parts);
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
