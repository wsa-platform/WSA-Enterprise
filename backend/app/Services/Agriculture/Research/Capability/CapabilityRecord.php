<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Single Cap cell record (Capability Store Design §8).
 *
 * Owns support facts only. Does not encode Path eligibility/status, Projection
 * fidelity_class, CSQ facets as scientific meaning, or Identity minting.
 */
final readonly class CapabilityRecord
{
    public const UNKNOWN_METADATA = 'UNKNOWN';

    private function __construct(
        public CapabilityRecordId $recordId,
        public CapabilitySubject $subject,
        public CapabilityDimension $dimension,
        public CapabilityState $state,
        public CapabilityEvidenceFreshness $evidenceFreshness,
        public string $snapshotVersion,
        public string $updateMethod,
        public ?string $limitationText,
        public ?string $verifiedAt,
        public ?string $observedAt,
        public bool $seatOverrideApplied,
    ) {}

    public static function create(
        CapabilityRecordId $recordId,
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        CapabilityState $state,
        CapabilityEvidenceFreshness $evidenceFreshness = CapabilityEvidenceFreshness::UNKNOWN,
        ?string $snapshotVersion = null,
        ?string $updateMethod = null,
        ?string $limitationText = null,
        ?string $verifiedAt = null,
        ?string $observedAt = null,
        bool $seatOverrideApplied = false,
    ): self {
        if ($state === CapabilityState::PARTIAL) {
            $limit = self::normalizeOptional($limitationText);
            if ($limit === null) {
                throw new CapabilityInvariantViolation(
                    'PARTIAL capability records require explicit limitation_text.'
                );
            }
            $limitationText = $limit;
        } else {
            $limitationText = self::normalizeOptional($limitationText);
        }

        if ($state === CapabilityState::VERIFIED
            && $evidenceFreshness === CapabilityEvidenceFreshness::STALE
        ) {
            // B4: STALE evidence must not be carried as if it were a fresh VERIFIED claim
            // without explicit freshness metadata — state may remain VERIFIED historically,
            // but freshness stays STALE and must not auto-promote. Allowed as recorded fact
            // with stale flag; consumers must not treat as current verified automation.
        }

        return new self(
            $recordId,
            $subject,
            $dimension,
            $state,
            $evidenceFreshness,
            self::normalizeMetadata($snapshotVersion),
            self::normalizeMetadata($updateMethod),
            $limitationText,
            self::normalizeOptional($verifiedAt),
            self::normalizeOptional($observedAt),
            $seatOverrideApplied,
        );
    }

    public function isStaleEvidence(): bool
    {
        return $this->evidenceFreshness === CapabilityEvidenceFreshness::STALE;
    }

    public function hasUnknownSnapshotVersion(): bool
    {
        return $this->snapshotVersion === self::UNKNOWN_METADATA;
    }

    public function hasUnknownUpdateMethod(): bool
    {
        return $this->updateMethod === self::UNKNOWN_METADATA;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'capability_record_id' => $this->recordId->value,
            'subject' => $this->subject->toArray(),
            'capability_dimension' => [
                'family' => $this->dimension->family->value,
                'code' => $this->dimension->code,
            ],
            'capability_state' => $this->state->value,
            'evidence_freshness_class' => $this->evidenceFreshness->value,
            'snapshot_version' => $this->snapshotVersion,
            'update_method' => $this->updateMethod,
            'limitation_text' => $this->limitationText,
            'verified_at' => $this->verifiedAt,
            'observed_at' => $this->observedAt,
            'seat_override_applied' => $this->seatOverrideApplied,
        ];
    }

    private static function normalizeMetadata(?string $value): string
    {
        if ($value === null) {
            return self::UNKNOWN_METADATA;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? self::UNKNOWN_METADATA : $trimmed;
    }

    private static function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
