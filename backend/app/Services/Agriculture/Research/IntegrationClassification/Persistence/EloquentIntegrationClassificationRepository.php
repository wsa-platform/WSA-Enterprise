<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent adapter for IC Persistence.
 */
final class EloquentIntegrationClassificationRepository implements IntegrationClassificationRepository
{
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
    ): IntegrationClassificationRecord {
        $existing = $this->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $current = $this->findCurrentByAdrId($adrId);
        if ($current !== null) {
            throw new IntegrationClassificationInvariantViolation(
                'Cannot persist a second ACTIVE IC decision for adr_id; use supersede().'
            );
        }

        $attributes = IntegrationClassificationRecord::draftAttributes(
            $adrId,
            $classificationDecisionIdentity,
            $classificationStatus,
            $accessModalityClaims,
            $integrationNature,
            $integrationBoundary,
            $sourceSpecificRequirement,
            $evidenceFingerprint,
            $idempotencyKey,
            $decisionActor,
            $verifiedAt,
            $decisionTimestamp,
            $canonicalIdentityId,
            $identityBindingRef,
            $protocolFamily,
            $sourceSpecificRationale,
            $pathFamilyHint,
            $existingAdapterReference,
            $externalDependencyReference,
            $evidenceReferences,
            $licenseReference,
            $accessReference,
            $reuseReference,
            $rationale,
            $metadata,
        );

        try {
            $row = CghiaIntegrationClassification::query()->create($attributes);
        } catch (QueryException $e) {
            if ($this->isUniqueIdempotencyViolation($e)) {
                $replay = $this->findByIdempotencyKey($idempotencyKey);
                if ($replay !== null) {
                    return $replay;
                }
            }

            throw $e;
        }

        return $this->mapRow($row);
    }

    public function findById(IntegrationClassificationRecordId $id): ?IntegrationClassificationRecord
    {
        $row = CghiaIntegrationClassification::query()->find($id->toInt());

        return $row === null ? null : $this->mapRow($row);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?IntegrationClassificationRecord
    {
        $key = trim($idempotencyKey);
        if ($key === '') {
            return null;
        }

        $row = CghiaIntegrationClassification::query()->where('idempotency_key', $key)->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function findCurrentByAdrId(AdrMembershipId $adrId): ?IntegrationClassificationRecord
    {
        $row = CghiaIntegrationClassification::query()
            ->where('adr_id', $adrId->value)
            ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
            ->orderByDesc('id')
            ->first();

        return $row === null ? null : $this->mapRow($row);
    }

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
    ): IntegrationClassificationRecord {
        return DB::transaction(function () use (
            $priorId,
            $adrId,
            $classificationDecisionIdentity,
            $classificationStatus,
            $accessModalityClaims,
            $integrationNature,
            $integrationBoundary,
            $sourceSpecificRequirement,
            $evidenceFingerprint,
            $newIdempotencyKey,
            $decisionActor,
            $verifiedAt,
            $decisionTimestamp,
            $canonicalIdentityId,
            $identityBindingRef,
            $protocolFamily,
            $sourceSpecificRationale,
            $pathFamilyHint,
            $existingAdapterReference,
            $externalDependencyReference,
            $evidenceReferences,
            $licenseReference,
            $accessReference,
            $reuseReference,
            $rationale,
            $metadata,
        ): IntegrationClassificationRecord {
            $prior = CghiaIntegrationClassification::query()->lockForUpdate()->find($priorId->toInt());
            if ($prior === null) {
                throw new IntegrationClassificationInvariantViolation(
                    'Cannot supersede: prior persistence_record_id not found.'
                );
            }

            if ((string) $prior->adr_id !== $adrId->value) {
                throw new IntegrationClassificationInvariantViolation(
                    'Cannot supersede: adr_id mismatch between prior record and new decision.'
                );
            }

            if ((string) $prior->lifecycle_state !== IntegrationClassificationLifecycleState::ACTIVE->value) {
                throw new IntegrationClassificationInvariantViolation(
                    'Cannot supersede: prior record is not ACTIVE.'
                );
            }

            // Temporarily clear ACTIVE so persist() single-ACTIVE invariant allows the new row.
            $prior->lifecycle_state = IntegrationClassificationLifecycleState::SUPERSEDED->value;
            $prior->save();

            try {
                $newRecord = $this->persist(
                    $adrId,
                    $classificationDecisionIdentity,
                    $classificationStatus,
                    $accessModalityClaims,
                    $integrationNature,
                    $integrationBoundary,
                    $sourceSpecificRequirement,
                    $evidenceFingerprint,
                    $newIdempotencyKey,
                    $decisionActor,
                    $verifiedAt,
                    $decisionTimestamp,
                    $canonicalIdentityId,
                    $identityBindingRef,
                    $protocolFamily,
                    $sourceSpecificRationale,
                    $pathFamilyHint,
                    $existingAdapterReference,
                    $externalDependencyReference,
                    $evidenceReferences,
                    $licenseReference,
                    $accessReference,
                    $reuseReference,
                    $rationale,
                    $metadata,
                );
            } catch (\Throwable $e) {
                $prior->lifecycle_state = IntegrationClassificationLifecycleState::ACTIVE->value;
                $prior->save();
                throw $e;
            }

            $prior->superseded_by = $newRecord->persistenceRecordId->toInt();
            $prior->save();

            return $newRecord;
        });
    }

    public function invalidate(IntegrationClassificationRecordId $id): IntegrationClassificationRecord
    {
        $row = CghiaIntegrationClassification::query()->find($id->toInt());
        if ($row === null) {
            throw new IntegrationClassificationInvariantViolation(
                'Cannot invalidate: persistence_record_id not found.'
            );
        }

        if ((string) $row->lifecycle_state !== IntegrationClassificationLifecycleState::ACTIVE->value) {
            throw new IntegrationClassificationInvariantViolation(
                'Cannot invalidate: only ACTIVE records may be invalidated.'
            );
        }

        // Lifecycle control only — domain columns on row remain immutable.
        $row->lifecycle_state = IntegrationClassificationLifecycleState::INVALIDATED->value;
        $row->save();

        return $this->mapRow($row->fresh());
    }

    private function mapRow(CghiaIntegrationClassification $row): IntegrationClassificationRecord
    {
        $supersededBy = $row->superseded_by === null
            ? null
            : IntegrationClassificationRecordId::fromInt((int) $row->superseded_by);

        return IntegrationClassificationRecord::fromPersistedRow(
            IntegrationClassificationRecordId::fromInt((int) $row->id),
            (string) $row->adr_id,
            $row->canonical_identity_id !== null ? (string) $row->canonical_identity_id : null,
            $row->identity_binding_ref !== null ? (string) $row->identity_binding_ref : null,
            (string) $row->classification_decision_identity,
            (string) $row->classification_status,
            $row->access_modality_claims,
            (string) $row->integration_nature,
            (string) $row->integration_boundary,
            $row->protocol_family !== null ? (string) $row->protocol_family : null,
            (bool) $row->source_specific_requirement,
            $row->source_specific_rationale !== null ? (string) $row->source_specific_rationale : null,
            $row->path_family_hint !== null ? (string) $row->path_family_hint : null,
            $row->existing_adapter_reference !== null ? (string) $row->existing_adapter_reference : null,
            $row->external_dependency_reference !== null ? (string) $row->external_dependency_reference : null,
            $row->evidence_references,
            (string) $row->evidence_fingerprint,
            $row->license_reference !== null ? (string) $row->license_reference : null,
            $row->access_reference !== null ? (string) $row->access_reference : null,
            $row->reuse_reference !== null ? (string) $row->reuse_reference : null,
            $row->rationale !== null ? (string) $row->rationale : null,
            (string) $row->decision_actor,
            (string) $row->verified_at,
            (string) $row->decision_timestamp,
            IntegrationClassificationLifecycleState::from((string) $row->lifecycle_state),
            (int) $row->schema_version,
            (string) $row->idempotency_key,
            $supersededBy,
            is_array($row->metadata) ? $row->metadata : null,
        );
    }

    private function isUniqueIdempotencyViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'idempotency_key')
            || (str_contains($message, 'unique') && str_contains($message, 'idempotency'));
    }
}
