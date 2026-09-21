<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Phase-5 Unit A — map validated evidence onto QuestionClaims.
 *
 * Consumes Phase-4 claim_relationship / directness / verification fields.
 * Does not create new QuestionClaims from evidence presence alone.
 */
final class QuestionClaimEvidenceMapper
{
    /**
     * @param  list<QuestionClaim>  $questionClaims
     * @param  list<ScientificEvidenceItem>  $evidenceItems
     * @return list<QuestionClaimEvidenceBinding>
     */
    public function bind(array $questionClaims, array $evidenceItems): array
    {
        if ($questionClaims === []) {
            return [];
        }

        $bindings = [];
        foreach ($questionClaims as $claim) {
            foreach ($evidenceItems as $item) {
                $bindings[] = new QuestionClaimEvidenceBinding(
                    questionClaimId: $claim->claimId,
                    evidenceId: $item->evidenceId,
                    sourceId: $item->sourceId,
                    sourceUrl: $item->url,
                    claimRelationship: $item->claimRelationship,
                    directness: $this->directness($item),
                    validationStatus: $item->validationStatus,
                    hasConflict: $item->hasConflict
                        || $item->claimRelationship === ClaimEvidenceRelationship::CONFLICTING,
                    reasons: is_array($item->validationFailures) ? array_values($item->validationFailures) : [],
                    metadata: [
                        'source_key' => $item->sourceKey,
                        'source_type' => $item->sourceType,
                        'doi' => $item->doi,
                        'publication_title' => $item->publicationTitle,
                        'publication_year' => $item->publicationYear,
                        'faostat_pipeline_outcome' => $item->sourceAttribution['faostat_pipeline_outcome']
                            ?? $item->qualityFactors['faostat_pipeline_outcome']
                            ?? null,
                        'verification_label' => $item->qualityFactors['verification_label']
                            ?? $item->sourceAttribution['verification_label']
                            ?? null,
                    ],
                );
            }
        }

        return $bindings;
    }

    /**
     * @param  list<QuestionClaimEvidenceBinding>  $bindings
     */
    public function aggregateRelationshipForClaim(string $questionClaimId, array $bindings): string
    {
        $relations = [];
        foreach ($bindings as $binding) {
            if ($binding->questionClaimId !== $questionClaimId) {
                continue;
            }
            $relations[] = $binding->claimRelationship;
        }

        if ($relations === []) {
            return ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE;
        }
        if (in_array(ClaimEvidenceRelationship::CONFLICTING, $relations, true)) {
            return ClaimEvidenceRelationship::CONFLICTING;
        }
        if (in_array(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $relations, true)
            && ! in_array(ClaimEvidenceRelationship::SUPPORTED, $relations, true)) {
            return ClaimEvidenceRelationship::PARTIALLY_SUPPORTED;
        }
        if (in_array(ClaimEvidenceRelationship::SUPPORTED, $relations, true)) {
            return ClaimEvidenceRelationship::SUPPORTED;
        }
        if (in_array(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $relations, true)) {
            return ClaimEvidenceRelationship::PARTIALLY_SUPPORTED;
        }

        return ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE;
    }

    private function directness(ScientificEvidenceItem $item): ?string
    {
        $value = $item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? null;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
