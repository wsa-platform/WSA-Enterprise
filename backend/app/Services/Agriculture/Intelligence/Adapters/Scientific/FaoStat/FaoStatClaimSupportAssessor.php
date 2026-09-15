<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;

/**
 * FAOSTAT may support a quantitative claim. It must not support mechanism/causal/disease claims.
 */
final class FaoStatClaimSupportAssessor
{
    public function __construct(
        private FaoStatObservationRelevanceGate $relevanceGate,
    ) {}

    /**
     * @return array{relationship: string, confidence: float, factors: array<string, mixed>}
     */
    public function assess(KnowledgeQueryPlan $plan, ScientificSearchResult $result): array
    {
        $gate = $this->relevanceGate->assess($plan, $result);
        $observation = is_array($result->rawMetadata['faostat'] ?? null) ? $result->rawMetadata['faostat'] : [];

        $factors = [
            'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
            'not_literature' => true,
            'faostat_relevance' => $gate['decision'],
            'faostat_relevance_reason' => $gate['reason'],
            'query_element_code' => $observation['query_element_code'] ?? ($result->relevanceMetadata['query_element_code'] ?? null),
            'response_element_code' => $observation['response_element_code'] ?? ($result->relevanceMetadata['response_element_code'] ?? null),
            'evidence_directness' => 'direct_statistical',
        ];

        if (! $gate['relevant']) {
            return [
                'relationship' => ClaimEvidenceRelationship::NOT_VALIDATED,
                'confidence' => 0.0,
                'factors' => $factors,
            ];
        }

        return [
            'relationship' => ClaimEvidenceRelationship::SUPPORTED,
            'confidence' => 0.82,
            'factors' => $factors,
        ];
    }
}
