<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Stage 4 scientific evidence DTO with validation metadata.
 */
final class ScientificEvidenceItem
{
    /**
     * @param  list<string>  $authors
     * @param  list<string>  $validationFailures
     * @param  array<string, mixed>  $qualityFactors
     * @param  array<string, mixed>  $sourceAttribution
     * @param  array<string, mixed>|null  $conditions
     */
    public function __construct(
        public readonly string $evidenceId,
        public readonly string $sourceId,
        public readonly string $sourceKey,
        public readonly ?string $sourceType,
        public readonly string $publicationTitle,
        public readonly array $authors,
        public readonly ?string $institution,
        public readonly ?string $journal,
        public readonly ?string $doi,
        public readonly ?string $url,
        public readonly ?int $publicationYear,
        public readonly string $retrievedAt,
        public readonly ?string $agriculturalDomain,
        public readonly ?string $claimTopic,
        public readonly ?string $evidenceText,
        public readonly string $validationStatus,
        public readonly array $validationFailures,
        public readonly string $claimRelationship,
        public readonly float $confidence,
        public readonly float $qualityScore,
        public readonly array $qualityFactors,
        public readonly array $sourceAttribution,
        public readonly bool $hasConflict = false,
        public readonly ?array $conditions = null,
        public readonly ?string $cropOrEntity = null,
    ) {}

    public function isUsable(): bool
    {
        return $this->validationStatus === EvidenceValidationStatus::EVIDENCE_USABLE;
    }

    public function isRejected(): bool
    {
        return $this->validationStatus === EvidenceValidationStatus::REJECTED;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'evidence_id' => $this->evidenceId,
            'source_id' => $this->sourceId,
            'source_key' => $this->sourceKey,
            'source_type' => $this->sourceType,
            'publication_title' => $this->publicationTitle,
            'authors' => $this->authors,
            'institution' => $this->institution,
            'journal' => $this->journal,
            'doi' => $this->doi,
            'url' => $this->url,
            'publication_year' => $this->publicationYear,
            'retrieved_at' => $this->retrievedAt,
            'agricultural_domain' => $this->agriculturalDomain,
            'claim_topic' => $this->claimTopic,
            'evidence_text' => $this->evidenceText,
            'validation_status' => $this->validationStatus,
            'validation_failures' => $this->validationFailures,
            'claim_relationship' => $this->claimRelationship,
            'confidence' => $this->confidence,
            'quality_score' => $this->qualityScore,
            'quality_factors' => $this->qualityFactors,
            'source_attribution' => $this->sourceAttribution,
            'has_conflict' => $this->hasConflict,
            'conditions' => $this->conditions,
            'crop_or_entity' => $this->cropOrEntity,
        ];
    }
}
