<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\ScientificSourceRegistry;

/**
 * Deterministic evidence quality scoring — ranking aid, not scientific certainty.
 *
 * Topical relevance + directness + usefulness outweigh bare peer-review authority.
 * Never applies country / geo preference.
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

        // Authority is a secondary signal — peer review alone must not outrank DIRECT topical hits.
        $confidenceLevel = (string) ($qualityAssessment['confidence_level'] ?? ScientificSourceRegistry::LEVEL_UNVERIFIED);
        $authorityScore = match ($confidenceLevel) {
            ScientificSourceRegistry::LEVEL_PEER_REVIEWED,
            ScientificSourceRegistry::LEVEL_OFFICIAL_RESEARCH => 18.0,
            ScientificSourceRegistry::LEVEL_EXTENSION_MANUAL => 14.0,
            ScientificSourceRegistry::LEVEL_SUPPORTING => 8.0,
            default => 0.0,
        };
        $score += $authorityScore;
        $factors['source_authority'] = $authorityScore;

        $metadataFields = is_array($metadataAssessment['fields'] ?? null) ? $metadataAssessment['fields'] : [];
        $present = count(array_filter($metadataFields));
        $total = max(count($metadataFields), 1);
        $completenessScore = ($present / $total) * 12.0;
        $score += $completenessScore;
        $factors['metadata_completeness'] = round($completenessScore, 2);

        if ($publicationYear !== null && $publicationYear >= ((int) date('Y') - 10)) {
            $score += 4.0;
            $factors['recency'] = 4.0;
        } else {
            $factors['recency'] = 0.0;
        }

        $relationship = (string) ($claimMatch['relationship'] ?? ClaimEvidenceRelationship::NOT_VALIDATED);
        $relevanceScore = match ($relationship) {
            ClaimEvidenceRelationship::SUPPORTED => 28.0,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED => 14.0,
            ClaimEvidenceRelationship::CONFLICTING => 5.0,
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE => 1.0,
            default => 0.0,
        };
        $score += $relevanceScore;
        $factors['direct_relevance'] = $relevanceScore;

        $claimFactors = is_array($claimMatch['factors'] ?? null) ? $claimMatch['factors'] : [];
        $directness = (string) ($claimFactors['evidence_directness'] ?? '');
        $directnessScore = match ($directness) {
            ScientificEvidenceDirectnessAssessor::DIRECT => 30.0,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED => 12.0,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED => -18.0,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH => -40.0,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT => -28.0,
            default => 0.0,
        };
        $score += $directnessScore;
        $factors['evidence_directness'] = $directness !== '' ? $directness : null;
        $factors['directness_usefulness'] = $directnessScore;

        // Usefulness: synonym/sense support and match ratio when present.
        $usefulness = 0.0;
        if (($claimFactors['synonym_support'] ?? false) === true) {
            $usefulness += 4.0;
        }
        $matchRatio = (float) ($claimFactors['match_ratio'] ?? 0.0);
        if ($matchRatio >= 0.35) {
            $usefulness += min(8.0, $matchRatio * 10.0);
        }
        $score += $usefulness;
        $factors['topical_usefulness'] = round($usefulness, 2);

        // Germination / growth+temperature: prefer on-intent evidence; demote essential-oil primary.
        $intentAdjust = 0.0;
        if (($claimFactors['germination_evidence_preferred'] ?? false) === true) {
            $intentAdjust += 10.0;
        }
        if (($claimFactors['growth_evidence_preferred'] ?? false) === true) {
            $intentAdjust += 10.0;
        }
        if (($claimFactors['essential_oil_primary_demotion'] ?? false) === true) {
            $intentAdjust -= 22.0;
        }
        $score += $intentAdjust;
        $factors['intent_primary_adjust'] = round($intentAdjust, 2);
        // Backward-compatible alias for existing observability consumers.
        $factors['germination_intent_adjust'] = round($intentAdjust, 2);

        $claimConfidence = (float) ($claimMatch['confidence'] ?? 0.0);
        $score += $claimConfidence * 12.0;
        $factors['claim_match_confidence'] = round($claimConfidence * 12.0, 2);

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
        $factors['no_geo_preference'] = true;

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
            $directnessOrder = [
                ScientificEvidenceDirectnessAssessor::DIRECT => 0,
                ScientificEvidenceDirectnessAssessor::SUPPORTING => 1,
                ScientificEvidenceDirectnessAssessor::SUPPORTED => 1,
                ScientificEvidenceDirectnessAssessor::RELATED => 2,
                ScientificEvidenceDirectnessAssessor::BACKGROUND => 2,
                ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH => 3,
                ScientificEvidenceDirectnessAssessor::IRRELEVANT => 4,
            ];
            $aDir = $directnessOrder[$a->qualityFactors['evidence_directness'] ?? ''] ?? 9;
            $bDir = $directnessOrder[$b->qualityFactors['evidence_directness'] ?? ''] ?? 9;
            if ($aDir !== $bDir) {
                return $aDir <=> $bDir;
            }

            $scoreCompare = $b->qualityScore <=> $a->qualityScore;
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp($a->publicationTitle, $b->publicationTitle);
        });

        return $items;
    }
}
