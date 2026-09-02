<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\ScientificSourceRegistry;

/**
 * Deterministic evidence quality scoring — ranking aid, not scientific certainty.
 */
class EvidenceQualityRanker
{
    /**
     * @param  array<string, mixed>  $metadataAssessment
     * @param  array<string, mixed>  $qualityAssessment
     * @param  array<string, mixed>  $claimMatch
     * @return array{score: float, factors: array<string, mixed>}
     */
    public function score(
        array $metadataAssessment,
        array $qualityAssessment,
        array $claimMatch,
        ?int $publicationYear,
        bool $isDuplicate = false,
        bool $hasConflict = false,
    ): array {
        $factors = [];
        $score = 0.0;

        $confidenceLevel = (string) ($qualityAssessment['confidence_level'] ?? ScientificSourceRegistry::LEVEL_UNVERIFIED);
        $authorityScore = match ($confidenceLevel) {
            ScientificSourceRegistry::LEVEL_PEER_REVIEWED,
            ScientificSourceRegistry::LEVEL_OFFICIAL_RESEARCH => 30.0,
            ScientificSourceRegistry::LEVEL_EXTENSION_MANUAL => 22.0,
            ScientificSourceRegistry::LEVEL_SUPPORTING => 12.0,
            default => 0.0,
        };
        $score += $authorityScore;
        $factors['source_authority'] = $authorityScore;

        $metadataFields = is_array($metadataAssessment['fields'] ?? null) ? $metadataAssessment['fields'] : [];
        $present = count(array_filter($metadataFields));
        $total = max(count($metadataFields), 1);
        $completenessScore = ($present / $total) * 20.0;
        $score += $completenessScore;
        $factors['metadata_completeness'] = round($completenessScore, 2);

        if ($publicationYear !== null && $publicationYear >= ((int) date('Y') - 10)) {
            $score += 5.0;
            $factors['recency'] = 5.0;
        } else {
            $factors['recency'] = 0.0;
        }

        $relationship = (string) ($claimMatch['relationship'] ?? ClaimEvidenceRelationship::NOT_VALIDATED);
        $relevanceScore = match ($relationship) {
            ClaimEvidenceRelationship::SUPPORTED => 25.0,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED => 15.0,
            ClaimEvidenceRelationship::CONFLICTING => 5.0,
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE => 2.0,
            default => 0.0,
        };
        $score += $relevanceScore;
        $factors['direct_relevance'] = $relevanceScore;

        $claimConfidence = (float) ($claimMatch['confidence'] ?? 0.0);
        $score += $claimConfidence * 10.0;
        $factors['claim_match_confidence'] = round($claimConfidence * 10.0, 2);

        if ($isDuplicate) {
            $score -= 5.0;
            $factors['duplicate_penalty'] = -5.0;
        }

        if ($hasConflict) {
            $score -= 10.0;
            $factors['conflict_penalty'] = -10.0;
        }

        $score = max(0.0, min(100.0, $score));
        $factors['not_scientific_certainty'] = true;

        return [
            'score' => round($score, 2),
            'factors' => $factors,
        ];
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ScientificEvidenceItem>
     */
    public function rank(array $items): array
    {
        usort($items, function (ScientificEvidenceItem $a, ScientificEvidenceItem $b): int {
            $scoreCompare = $b->qualityScore <=> $a->qualityScore;
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp($a->publicationTitle, $b->publicationTitle);
        });

        return $items;
    }
}
