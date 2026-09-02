<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Generic claim-to-evidence matching without inventing support.
 */
class ClaimEvidenceMatcher
{
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

        $queryTerms = $this->terms($this->queryText($plan));
        $evidenceTerms = $this->terms($evidenceText);

        if ($queryTerms === []) {
            return [
                'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                'confidence' => 0.4,
                'factors' => ['matched_terms' => 0, 'query_terms' => 0],
            ];
        }

        $matched = array_values(array_intersect($queryTerms, $evidenceTerms));
        $matchRatio = count($matched) / max(count($queryTerms), 1);

        if ($matchRatio >= 0.6) {
            return [
                'relationship' => ClaimEvidenceRelationship::SUPPORTED,
                'confidence' => min(0.95, 0.5 + ($matchRatio * 0.45)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
                ],
            ];
        }

        if ($matchRatio >= 0.25) {
            return [
                'relationship' => ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                'confidence' => min(0.7, 0.25 + ($matchRatio * 0.45)),
                'factors' => [
                    'matched_terms' => count($matched),
                    'query_terms' => count($queryTerms),
                    'match_ratio' => round($matchRatio, 3),
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
            ],
        ];
    }

    private function queryText(KnowledgeQueryPlan $plan): string
    {
        $parts = array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
            $plan->researchIntent,
            $plan->agriculturalDomain,
            $plan->normalizedQuery->cropId,
            $plan->normalizedQuery->scientificName,
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
