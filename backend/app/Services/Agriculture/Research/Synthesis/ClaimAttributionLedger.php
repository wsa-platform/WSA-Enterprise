<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Builds claim-level L7 attribution rows: Claim → Evidence → Source → Provider Record → Citation.
 *
 * Does not invent support; only maps claims to evidence already selected by ClaimEvidenceMatcher
 * / Composer. successful_sources alone never establishes L7.
 */
final class ClaimAttributionLedger
{
    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ScientificEvidenceItem>  $usableEvidence
     * @param  list<ResearchAnswerCitation>  $citations
     * @return list<array<string, mixed>>
     */
    public function build(array $claims, array $usableEvidence, array $citations): array
    {
        $evidenceById = [];
        foreach ($usableEvidence as $item) {
            $evidenceById[$item->evidenceId] = $item;
        }

        $citationByEvidenceId = [];
        foreach ($citations as $citation) {
            $citationByEvidenceId[$citation->evidenceId] = $citation;
        }

        $rows = [];
        foreach ($claims as $claim) {
            if (! $claim instanceof ResearchAnswerClaim) {
                continue;
            }
            // Always re-bind citations at ledger build time so citation_id resolves
            // to the evidence that actually supports the claim (L7 requirement).
            $attributions = $this->attributionsForClaim($claim, $evidenceById, $citationByEvidenceId);

            $l7Eligible = $claim->claimText !== ''
                && $claim->claimRelationship !== ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE
                && $attributions !== []
                && $this->everyAttributionResolves($attributions);

            $rows[] = [
                'claim_id' => $claim->claimId,
                'claim_text' => $claim->claimText,
                'question_claim_id' => $claim->questionClaimId,
                'validation_status' => $claim->validationStatus,
                'claim_relationship' => $claim->claimRelationship,
                'evidence_ids' => $claim->evidenceIds,
                'source_keys' => $claim->sourceKeys !== []
                    ? $claim->sourceKeys
                    : array_values(array_unique(array_filter(array_map(
                        static fn (array $row): string => (string) ($row['source_key'] ?? ''),
                        $attributions,
                    )))),
                'attributions' => $attributions,
                'l7_final_claim_attribution_proven' => $l7Eligible,
                'conflict_status' => $claim->claimRelationship === ClaimEvidenceRelationship::CONFLICTING
                    ? 'conflicting'
                    : 'none',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, ScientificEvidenceItem>  $evidenceById
     * @param  array<string, ResearchAnswerCitation>  $citationByEvidenceId
     * @return list<array<string, mixed>>
     */
    public function attributionsForClaim(
        ResearchAnswerClaim $claim,
        array $evidenceById,
        array $citationByEvidenceId,
    ): array {
        $rows = [];
        foreach ($claim->evidenceIds as $evidenceId) {
            $item = $evidenceById[$evidenceId] ?? null;
            if ($item === null) {
                continue;
            }
            $citation = $citationByEvidenceId[$evidenceId] ?? null;
            $rows[] = $this->attributionRow($item, $citation, $claim);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributionRow(
        ScientificEvidenceItem $item,
        ?ResearchAnswerCitation $citation,
        ?ResearchAnswerClaim $claim = null,
    ): array {
        $providerRecordId = $this->resolveProviderRecordId($item);

        return [
            'evidence_id' => $item->evidenceId,
            'source_key' => $item->sourceKey,
            'provider_record_id' => $providerRecordId,
            'canonical_record_id' => is_string($item->sourceAttribution['canonical_record_id'] ?? null)
                ? (string) $item->sourceAttribution['canonical_record_id']
                : null,
            'source_id' => $item->sourceId,
            'url' => $item->url,
            'doi' => $item->doi,
            'validation_status' => $item->validationStatus,
            'relevance_result' => $item->qualityFactors['relevance'] ?? ($item->sourceAttribution['relevance'] ?? null),
            'directness_result' => $item->qualityFactors['evidence_directness']
                ?? ($item->sourceAttribution['evidence_directness'] ?? null),
            'claim_relationship' => $item->claimRelationship,
            'scientific_support_assessment' => $claim?->claimRelationship ?? $item->claimRelationship,
            'conflict_status' => $item->hasConflict || $item->claimRelationship === ClaimEvidenceRelationship::CONFLICTING
                ? 'conflicting'
                : 'none',
            'citation_id' => $citation?->citationId,
            'citation_resolves_to_evidence' => $citation !== null && $citation->evidenceId === $item->evidenceId,
            'final_citation_reference' => $citation?->toArray(),
        ];
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<string>
     */
    public function sourceKeysFromEvidenceIds(array $evidenceIds, array $items): array
    {
        $byId = [];
        foreach ($items as $item) {
            $byId[$item->evidenceId] = $item;
        }
        $keys = [];
        foreach ($evidenceIds as $evidenceId) {
            $item = $byId[$evidenceId] ?? null;
            if ($item !== null && $item->sourceKey !== '') {
                $keys[] = $item->sourceKey;
            }
        }

        return array_values(array_unique($keys));
    }

    private function resolveProviderRecordId(ScientificEvidenceItem $item): string
    {
        $attr = $item->sourceAttribution;
        foreach ([
            'provider_record_id',
            'provider_record_identifier',
            'original_identifier',
            'record_id',
            'gbif_id',
            'doi',
        ] as $field) {
            $value = $attr[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if ($item->doi !== null && trim($item->doi) !== '') {
            return trim($item->doi);
        }

        return $item->sourceId !== '' ? $item->sourceId : $item->evidenceId;
    }

    /** @param  list<array<string, mixed>>  $attributions */
    private function everyAttributionResolves(array $attributions): bool
    {
        foreach ($attributions as $row) {
            if (! is_array($row)) {
                return false;
            }
            if (trim((string) ($row['evidence_id'] ?? '')) === '') {
                return false;
            }
            if (trim((string) ($row['source_key'] ?? '')) === '') {
                return false;
            }
            if (trim((string) ($row['provider_record_id'] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
