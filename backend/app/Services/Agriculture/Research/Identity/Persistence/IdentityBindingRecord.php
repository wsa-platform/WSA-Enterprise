<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\IdentityBindingStatus;
use App\Services\Agriculture\Research\Identity\IdentityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\SourceIdentityDomainContract;

/**
 * Immutable identity binding snapshot (FS-01-ID storage DTO).
 *
 * Persistence only — does not mint SAME_AS, canonical identities, Cap/Path/D-10 authority.
 */
final readonly class IdentityBindingRecord
{
    public const CURRENT_SCHEMA_VERSION = 1;

    /**
     * @param  list<string>  $identityEvidenceRefs
     * @param  array<string, mixed>|null  $metadata
     */
    private function __construct(
        public IdentityBindingRecordId $persistenceRecordId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public IdentityBindingStatus $identityStatus,
        public IdentityDecisionIdentity $identityDecisionIdentity,
        public string $identityEvidenceFingerprint,
        public array $identityEvidenceRefs,
        public ?string $originalSourceIdentifier,
        public ?string $aggregatorRecordIdentifier,
        public IdentityBindingLifecycleState $lifecycleState,
        public int $schemaVersion,
        public string $idempotencyKey,
        public ?IdentityBindingRecordId $supersededBy,
        public string $decisionActor,
        public string $decisionTimestamp,
        public ?array $metadata,
    ) {}

    /**
     * @param  list<string>|null  $identityEvidenceRefs
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>
     */
    public static function draftAttributes(
        AdrMembershipId $adrId,
        IdentityBindingStatus $identityStatus,
        IdentityDecisionIdentity $identityDecisionIdentity,
        string $identityEvidenceFingerprint,
        string $idempotencyKey,
        string $decisionActor,
        string $decisionTimestamp,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?array $identityEvidenceRefs = null,
        ?string $originalSourceIdentifier = null,
        ?string $aggregatorRecordIdentifier = null,
        ?array $metadata = null,
    ): array {
        $fingerprint = trim($identityEvidenceFingerprint);
        if ($fingerprint === '') {
            throw new IdentityBindingInvariantViolation(
                'identity_evidence_fingerprint must be a non-empty string.'
            );
        }

        $key = trim($idempotencyKey);
        if ($key === '') {
            throw new IdentityBindingInvariantViolation('idempotency_key must be a non-empty string.');
        }

        $actor = trim($decisionActor);
        if ($actor === '') {
            throw new IdentityBindingInvariantViolation('decision_actor must be a non-empty string.');
        }

        $timestamp = trim($decisionTimestamp);
        if ($timestamp === '') {
            throw new IdentityBindingInvariantViolation('decision_timestamp must be a non-empty string.');
        }

        SourceIdentityDomainContract::assertDistinctNamespaces(
            $adrId,
            $canonicalIdentityId,
            null,
            null,
        );

        if ($canonicalIdentityId !== null && $adrId->value === $canonicalIdentityId->value) {
            throw new IdentityBindingInvariantViolation(
                'adr_id must not equal canonical_identity_id; seats and canonicals are distinct namespaces.'
            );
        }

        $refs = self::normalizeEvidenceRefs($identityEvidenceRefs);
        $original = self::normalizeOptionalOpaque($originalSourceIdentifier);
        $aggregator = self::normalizeOptionalOpaque($aggregatorRecordIdentifier);

        if ($original !== null && $aggregator !== null && $original === $aggregator) {
            throw new IdentityBindingInvariantViolation(
                'original_source_identifier must not equal aggregator_record_identifier.'
            );
        }

        if ($metadata !== null) {
            IdentityBindingPersistenceContract::assertMetadataNonAuthoritative($metadata);
            SourceIdentityDomainContract::assertIdentityPayloadClean($metadata);
        }

        return [
            'adr_id' => $adrId->value,
            'canonical_identity_id' => $canonicalIdentityId?->value,
            'identity_status' => $identityStatus->value,
            'identity_decision_identity' => $identityDecisionIdentity->value,
            'identity_evidence_fingerprint' => $fingerprint,
            'identity_evidence_refs' => $refs,
            'original_source_identifier' => $original,
            'aggregator_record_identifier' => $aggregator,
            'lifecycle_state' => IdentityBindingLifecycleState::ACTIVE->value,
            'schema_version' => self::CURRENT_SCHEMA_VERSION,
            'idempotency_key' => $key,
            'superseded_by' => null,
            'decision_actor' => $actor,
            'decision_timestamp' => $timestamp,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  list<string>|null  $identityEvidenceRefs
     * @param  array<string, mixed>|null  $metadata
     */
    public static function fromPersistedRow(
        IdentityBindingRecordId $persistenceRecordId,
        string $adrId,
        ?string $canonicalIdentityId,
        string $identityStatus,
        string $identityDecisionIdentity,
        string $identityEvidenceFingerprint,
        mixed $identityEvidenceRefs,
        ?string $originalSourceIdentifier,
        ?string $aggregatorRecordIdentifier,
        IdentityBindingLifecycleState $lifecycleState,
        int $schemaVersion,
        string $idempotencyKey,
        ?IdentityBindingRecordId $supersededBy,
        string $decisionActor,
        string $decisionTimestamp,
        ?array $metadata,
    ): self {
        $adr = AdrMembershipId::fromString($adrId);
        $canonical = $canonicalIdentityId === null || trim($canonicalIdentityId) === ''
            ? null
            : CanonicalSourceIdentityId::fromString($canonicalIdentityId);

        SourceIdentityDomainContract::assertDistinctNamespaces($adr, $canonical, null, null);

        if ($canonical !== null && $adr->value === $canonical->value) {
            throw new IdentityBindingInvariantViolation(
                'adr_id must not equal canonical_identity_id; seats and canonicals are distinct namespaces.'
            );
        }

        $refs = self::normalizeEvidenceRefs(
            is_array($identityEvidenceRefs) ? $identityEvidenceRefs : null
        );

        $original = self::normalizeOptionalOpaque($originalSourceIdentifier);
        $aggregator = self::normalizeOptionalOpaque($aggregatorRecordIdentifier);
        if ($original !== null && $aggregator !== null && $original === $aggregator) {
            throw new IdentityBindingInvariantViolation(
                'original_source_identifier must not equal aggregator_record_identifier.'
            );
        }

        if ($metadata !== null) {
            IdentityBindingPersistenceContract::assertMetadataNonAuthoritative($metadata);
        }

        self::assertPersistenceIdDistinctFromDomain(
            $persistenceRecordId,
            $adr->value,
            $canonical?->value,
            $identityDecisionIdentity,
            $identityEvidenceFingerprint,
            $idempotencyKey,
        );

        return new self(
            $persistenceRecordId,
            $adr,
            $canonical,
            IdentityBindingStatus::from($identityStatus),
            IdentityDecisionIdentity::fromString($identityDecisionIdentity),
            trim($identityEvidenceFingerprint),
            $refs,
            $original,
            $aggregator,
            $lifecycleState,
            $schemaVersion,
            trim($idempotencyKey),
            $supersededBy,
            trim($decisionActor),
            trim($decisionTimestamp),
            $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'persistence_record_id' => $this->persistenceRecordId->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'identity_status' => $this->identityStatus->value,
            'identity_decision_identity' => $this->identityDecisionIdentity->value,
            'identity_evidence_fingerprint' => $this->identityEvidenceFingerprint,
            'identity_evidence_refs' => $this->identityEvidenceRefs,
            'original_source_identifier' => $this->originalSourceIdentifier,
            'aggregator_record_identifier' => $this->aggregatorRecordIdentifier,
            'lifecycle_state' => $this->lifecycleState->value,
            'schema_version' => $this->schemaVersion,
            'idempotency_key' => $this->idempotencyKey,
            'superseded_by' => $this->supersededBy?->value,
            'decision_actor' => $this->decisionActor,
            'decision_timestamp' => $this->decisionTimestamp,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param  list<mixed>|null  $refs
     * @return list<string>
     */
    private static function normalizeEvidenceRefs(?array $refs): array
    {
        if ($refs === null) {
            return [];
        }

        $out = [];
        foreach ($refs as $ref) {
            if (! is_string($ref)) {
                throw new IdentityBindingInvariantViolation(
                    'identity_evidence_refs must be a list of opaque strings.'
                );
            }
            $trimmed = trim($ref);
            if ($trimmed === '') {
                throw new IdentityBindingInvariantViolation(
                    'identity_evidence_refs entries must be non-empty strings.'
                );
            }
            $out[] = $trimmed;
        }

        return $out;
    }

    private static function normalizeOptionalOpaque(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function assertPersistenceIdDistinctFromDomain(
        IdentityBindingRecordId $persistenceRecordId,
        string $adrId,
        ?string $canonicalIdentityId,
        string $identityDecisionIdentity,
        string $identityEvidenceFingerprint,
        string $idempotencyKey,
    ): void {
        $pid = $persistenceRecordId->value;
        foreach ([
            $adrId,
            $canonicalIdentityId,
            $identityDecisionIdentity,
            $identityEvidenceFingerprint,
            $idempotencyKey,
        ] as $domain) {
            if ($domain !== null && $domain !== '' && $domain === $pid) {
                throw new IdentityBindingInvariantViolation(
                    'persistence_record_id must not equal a domain identity string.'
                );
            }
        }
    }
}
