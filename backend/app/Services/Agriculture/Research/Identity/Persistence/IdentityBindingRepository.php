<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\IdentityBindingStatus;
use App\Services\Agriculture\Research\Identity\IdentityDecisionIdentity;

/**
 * FS-01-ID Identity Binding repository — storage only.
 *
 * Does not mint SAME_AS, Cap/Path/Projection/D-10/Selector semantics.
 */
interface IdentityBindingRepository
{
    /**
     * Persist an immutable identity binding snapshot.
     * Same idempotency_key replays the existing record (no duplicate row).
     *
     * @param  list<string>|null  $identityEvidenceRefs
     * @param  array<string, mixed>|null  $metadata  Non-authoritative only.
     */
    public function persist(
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
    ): IdentityBindingRecord;

    public function findById(IdentityBindingRecordId $id): ?IdentityBindingRecord;

    public function findByIdempotencyKey(string $idempotencyKey): ?IdentityBindingRecord;

    /**
     * Current ACTIVE lifecycle row for adr_id, if any.
     */
    public function findCurrentByAdrId(AdrMembershipId $adrId): ?IdentityBindingRecord;

    /**
     * Append a new ACTIVE snapshot and mark the prior record SUPERSEDED.
     *
     * @param  list<string>|null  $identityEvidenceRefs
     * @param  array<string, mixed>|null  $metadata
     */
    public function supersede(
        IdentityBindingRecordId $priorId,
        AdrMembershipId $adrId,
        IdentityBindingStatus $identityStatus,
        IdentityDecisionIdentity $identityDecisionIdentity,
        string $identityEvidenceFingerprint,
        string $newIdempotencyKey,
        string $decisionActor,
        string $decisionTimestamp,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?array $identityEvidenceRefs = null,
        ?string $originalSourceIdentifier = null,
        ?string $aggregatorRecordIdentifier = null,
        ?array $metadata = null,
    ): IdentityBindingRecord;

    public function invalidate(IdentityBindingRecordId $id): IdentityBindingRecord;
}
