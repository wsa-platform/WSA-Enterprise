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

        $claimCount = count($questionClaims);
        $bindings = [];
        foreach ($questionClaims as $claim) {
            foreach ($evidenceItems as $item) {
                if (! $this->evidenceAddressesClaim($claim, $item, $claimCount)) {
                    continue;
                }

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
     * Single-claim: bind all evidence (Unit A). Multi-claim: lexical property address only.
     * Catalog-free — does not call AgriculturalEntityCatalog / FieldCropTaxonomyCatalog.
     */
    private function evidenceAddressesClaim(
        QuestionClaim $claim,
        ScientificEvidenceItem $item,
        int $claimCount,
    ): bool {
        if ($claimCount <= 1) {
            return true;
        }

        $property = mb_strtolower(trim((string) ($claim->property ?? '')));
        if ($property === '') {
            return true;
        }

        $hay = mb_strtolower(trim(implode(' ', array_filter([
            (string) ($item->publicationTitle ?? ''),
            (string) ($item->evidenceText ?? ''),
        ]))));

        foreach ($this->propertyAddressTerms($property) as $term) {
            if ($term !== '' && mb_strpos($hay, $term) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function propertyAddressTerms(string $property): array
    {
        $aliases = [
            'quantity' => ['quantity', 'production', 'produced', 'output', 'tonnage', 'tons', 'tonnes'],
            'yield' => ['yield', 'productivity', 'productive'],
            'area' => ['area', 'harvested', 'hectare', 'hectares', 'ha '],
            'rate' => ['rate', 'dosage', 'seed rate', 'seeding'],
            'irrigation' => ['irrigation', 'water requirement', 'watering'],
            'temperature' => ['temperature', 'celsius', '°c', 'deg c'],
            'production' => ['production', 'quantity', 'produced', 'output'],
        ];

        $terms = $aliases[$property] ?? [];
        $terms[] = $property;

        return array_values(array_unique(array_map(
            static fn (string $term): string => mb_strtolower(trim($term)),
            $terms,
        )));
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
