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
 */
interface IntegrationClassificationRepository
{
    /**
     * Persist an immutable IC decision snapshot as ACTIVE.
     * Same idempotency_key replays the existing record (no duplicate row).
     * Rejects create when another ACTIVE already exists for adr_id (use supersede).
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
     * Missing ⇒ logical UNCLASSIFIED (no automatic row).
     */
    public function findCurrentByAdrId(AdrMembershipId $adrId): ?IntegrationClassificationRecord;

    /**
     * Append a new ACTIVE snapshot and mark the prior record SUPERSEDED.
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

    public function invalidate(IntegrationClassificationRecordId $id): IntegrationClassificationRecord;
}
