<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;

/**
 * IC Persistence repository — storage only.
 *
 * Does not mint SAME_AS, Cap/Path/Projection/D-10/Selector/Stage-3 semantics.
 *
 * Isolation contract for unique-conflict recovery (persist, supersede): after a unique violation
 * the failed write is rolled back (to its savepoint when nested) and the idempotency key is looked
 * up once, read-only, so a request that lost a race can be answered with the competitor's committed
 * row. That lookup is intended to run under READ COMMITTED (the PostgreSQL default). Under a caller
 * transaction at REPEATABLE READ or SERIALIZABLE the competitor's row can be invisible to the
 * caller's snapshot; recovery then fails closed with IntegrationClassificationActiveConflict or
 * propagates the original QueryException instead of returning that row.
 * In every case: a replay never returns a record whose replay contract differs from the request;
 * SQL errors other than the recognised unique violations are rethrown unchanged; the isolation
 * level is never changed; and no write is retried.
 */
interface IntegrationClassificationRepository
{
    /**
     * Persist an immutable IC decision snapshot as ACTIVE.
     * Same idempotency_key with a matching decision contract
     * (IntegrationClassificationPersistenceContract::REPLAY_CONTRACT_FIELDS) replays the stored
     * record as-is, including its current lifecycleState; nothing is written or reactivated.
     * Same idempotency_key with a different contract ⇒ IntegrationClassificationIdempotencyConflict.
     * Another ACTIVE for adr_id ⇒ IntegrationClassificationActiveConflict (use supersede);
     * the database enforces at most one ACTIVE per adr_id.
     *
     * @param  list<string>  $accessModalityClaims
     * @param  list<string>|null  $evidenceReferences
     * @param  array<string, mixed>|null  $metadata  Non-authoritative only.
     */
    public function persist(
        AdrMembershipId $adrId,
        ClassificationDecisionIdentity $classificationDecisionIdentity,
        ClassificationStatus $classificationStatus,
        array $accessModalityClaims,
        IntegrationNature $integrationNature,
        IntegrationBoundary $integrationBoundary,
        bool $sourceSpecificRequirement,
        string $evidenceFingerprint,
        string $idempotencyKey,
        string $decisionActor,
        string $verifiedAt,
        string $decisionTimestamp,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?string $identityBindingRef = null,
        ?string $protocolFamily = null,
        ?string $sourceSpecificRationale = null,
        ?string $pathFamilyHint = null,
        ?string $existingAdapterReference = null,
        ?string $externalDependencyReference = null,
        ?array $evidenceReferences = null,
        ?string $licenseReference = null,
        ?string $accessReference = null,
        ?string $reuseReference = null,
        ?string $rationale = null,
        ?array $metadata = null,
    ): IntegrationClassificationRecord;

    public function findById(IntegrationClassificationRecordId $id): ?IntegrationClassificationRecord;

    public function findByIdempotencyKey(string $idempotencyKey): ?IntegrationClassificationRecord;

    /**
     * Current ACTIVE lifecycle row for adr_id, if any.
     * Missing / zero ACTIVE ⇒ null (logical UNCLASSIFIED; no automatic row).
     * Exactly one ACTIVE ⇒ that record.
     * More than one ACTIVE ⇒ IntegrationClassificationInvariantViolation (fail closed).
     */
    public function findCurrentByAdrId(AdrMembershipId $adrId): ?IntegrationClassificationRecord;

    /**
     * Append a new ACTIVE snapshot and mark the prior record SUPERSEDED, atomically and under a
     * row lock on the prior. The prior must exist, share adr_id, and be ACTIVE.
     * Retrying with the same newIdempotencyKey after success returns the stored replacement only
     * when its contract matches and prior.superseded_by points to it; a key belonging to the prior
     * itself, to another adr_id, or to any other record ⇒ IntegrationClassificationIdempotencyConflict.
     *
     * @param  list<string>  $accessModalityClaims
     * @param  list<string>|null  $evidenceReferences
     * @param  array<string, mixed>|null  $metadata
     */
    public function supersede(
        IntegrationClassificationRecordId $priorId,
        AdrMembershipId $adrId,
        ClassificationDecisionIdentity $classificationDecisionIdentity,
        ClassificationStatus $classificationStatus,
        array $accessModalityClaims,
        IntegrationNature $integrationNature,
        IntegrationBoundary $integrationBoundary,
        bool $sourceSpecificRequirement,
        string $evidenceFingerprint,
        string $newIdempotencyKey,
        string $decisionActor,
        string $verifiedAt,
        string $decisionTimestamp,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?string $identityBindingRef = null,
        ?string $protocolFamily = null,
        ?string $sourceSpecificRationale = null,
        ?string $pathFamilyHint = null,
        ?string $existingAdapterReference = null,
        ?string $externalDependencyReference = null,
        ?array $evidenceReferences = null,
        ?string $licenseReference = null,
        ?string $accessReference = null,
        ?string $reuseReference = null,
        ?string $rationale = null,
        ?array $metadata = null,
    ): IntegrationClassificationRecord;

    /**
     * Mark an ACTIVE record INVALIDATED under a row lock (serialized with supersede).
     */
    public function invalidate(IntegrationClassificationRecordId $id): IntegrationClassificationRecord;
}
