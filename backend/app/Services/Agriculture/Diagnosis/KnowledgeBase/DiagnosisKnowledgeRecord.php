<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Verified or raw diagnosis knowledge record (Stage 7 domain model).
 * Generic for all crops — crop lists are associations, not architecture locks.
 */
final class DiagnosisKnowledgeRecord
{
    /**
     * @param  list<string>  $cropKeys
     * @param  list<string>  $commonNames
     * @param  list<string>  $aliases
     * @param  list<string>  $symptoms
     * @param  list<string>  $plantParts
     * @param  list<DiagnosisObservationPattern>  $observationPatterns
     * @param  list<DiagnosisDifferentialEntry>  $differentials
     * @param  list<DiagnosisKnowledgeSource>  $sources
     * @param  list<string>  $supportingEvidenceNotes
     * @param  list<string>  $contradictingEvidenceNotes
     * @param  list<string>  $recommendedAdditionalObservations
     * @param  list<string>  $safetyNotices
     * @param  list<DiagnosisManagementReference>  $managementReferences
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $commonName,
        public readonly string $category,
        public readonly string $causalClass,
        public readonly string $verificationStatus,
        public readonly ?string $scientificName = null,
        public readonly bool $scientificNameVerified = false,
        public readonly array $cropKeys = [],
        public readonly array $commonNames = [],
        public readonly array $aliases = [],
        public readonly array $symptoms = [],
        public readonly array $plantParts = [],
        public readonly array $observationPatterns = [],
        public readonly array $differentials = [],
        public readonly array $sources = [],
        public readonly array $supportingEvidenceNotes = [],
        public readonly array $contradictingEvidenceNotes = [],
        public readonly array $recommendedAdditionalObservations = [],
        public readonly array $safetyNotices = [],
        public readonly array $managementReferences = [],
        public readonly ?string $pathogenType = null,
        public readonly ?string $version = '1.0',
        public readonly ?string $freshnessLabel = null,
        public readonly array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'common_name' => $this->commonName,
            'scientific_name' => $this->scientificName,
            'scientific_name_verified' => $this->scientificNameVerified,
            'category' => $this->category,
            'causal_class' => $this->causalClass,
            'pathogen_type' => $this->pathogenType,
            'verification_status' => $this->verificationStatus,
            'crop_keys' => $this->cropKeys,
            'common_names' => $this->commonNames,
            'aliases' => $this->aliases,
            'symptoms' => $this->symptoms,
            'plant_parts' => $this->plantParts,
            'observation_patterns' => array_map(
                static fn (DiagnosisObservationPattern $p): array => $p->toArray(),
                $this->observationPatterns,
            ),
            'differentials' => array_map(
                static fn (DiagnosisDifferentialEntry $d): array => $d->toArray(),
                $this->differentials,
            ),
            'sources' => array_map(
                static fn (DiagnosisKnowledgeSource $s): array => $s->toArray(),
                $this->sources,
            ),
            'supporting_evidence_notes' => $this->supportingEvidenceNotes,
            'contradicting_evidence_notes' => $this->contradictingEvidenceNotes,
            'recommended_additional_observations' => $this->recommendedAdditionalObservations,
            'safety_notices' => $this->safetyNotices,
            'management_references' => array_map(
                static fn (DiagnosisManagementReference $m): array => $m->toArray(),
                $this->managementReferences,
            ),
            'version' => $this->version,
            'freshness_label' => $this->freshnessLabel,
            'metadata' => $this->metadata,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $patterns = [];
        foreach ($data['observation_patterns'] ?? [] as $pattern) {
            if (is_array($pattern)) {
                $patterns[] = DiagnosisObservationPattern::fromArray($pattern);
            }
        }

        $differentials = [];
        foreach ($data['differentials'] ?? [] as $diff) {
            if (is_array($diff)) {
                $differentials[] = DiagnosisDifferentialEntry::fromArray($diff);
            }
        }

        $sources = [];
        foreach ($data['sources'] ?? [] as $source) {
            if (is_array($source)) {
                $sources[] = DiagnosisKnowledgeSource::fromArray($source);
            }
        }

        $management = [];
        foreach ($data['management_references'] ?? [] as $ref) {
            if (is_array($ref)) {
                $management[] = DiagnosisManagementReference::fromArray($ref);
            }
        }

        return new self(
            id: (string) ($data['id'] ?? ''),
            commonName: (string) ($data['common_name'] ?? ''),
            category: (string) ($data['category'] ?? 'unspecified'),
            causalClass: (string) ($data['causal_class'] ?? 'unspecified'),
            verificationStatus: (string) ($data['verification_status'] ?? DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED),
            scientificName: isset($data['scientific_name']) && is_string($data['scientific_name'])
                ? $data['scientific_name']
                : null,
            scientificNameVerified: (bool) ($data['scientific_name_verified'] ?? false),
            cropKeys: self::stringList($data['crop_keys'] ?? []),
            commonNames: self::stringList($data['common_names'] ?? []),
            aliases: self::stringList($data['aliases'] ?? []),
            symptoms: self::stringList($data['symptoms'] ?? []),
            plantParts: self::stringList($data['plant_parts'] ?? []),
            observationPatterns: $patterns,
            differentials: $differentials,
            sources: $sources,
            supportingEvidenceNotes: self::stringList($data['supporting_evidence_notes'] ?? []),
            contradictingEvidenceNotes: self::stringList($data['contradicting_evidence_notes'] ?? []),
            recommendedAdditionalObservations: self::stringList($data['recommended_additional_observations'] ?? []),
            safetyNotices: self::stringList($data['safety_notices'] ?? []),
            managementReferences: $management,
            pathogenType: isset($data['pathogen_type']) && is_string($data['pathogen_type'])
                ? $data['pathogen_type']
                : null,
            version: isset($data['version']) && is_string($data['version']) ? $data['version'] : '1.0',
            freshnessLabel: isset($data['freshness_label']) && is_string($data['freshness_label'])
                ? $data['freshness_label']
                : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values($out);
    }
}
