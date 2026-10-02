<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

use App\Models\CghiaIdentityBinding;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\IdentityBindingStatus;
use App\Services\Agriculture\Research\Identity\IdentityDecisionIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent adapter for FS-01-ID Identity Binding persistence.
 */
final class EloquentIdentityBindingRepository implements IdentityBindingRepository
{
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
    ): IdentityBindingRecord {
        $existing = $this->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $attributes = IdentityBindingRecord::draftAttributes(
            $adrId,
            $identityStatus,
            $identityDecisionIdentity,
            $identityEvidenceFingerprint,
            $idempotencyKey,
            $decisionActor,
            $decisionTimestamp,
            $canonicalIdentityId,
            $identityEvidenceRefs,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
            $metadata,
        );

        try {
            $row = CghiaIdentityBinding::query()->create($attributes);
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

    public function findById(IdentityBindingRecordId $id): ?IdentityBindingRecord
    {
        $row = CghiaIdentityBinding::query()->find($id->toInt());

        return $row === null ? null : $this->mapRow($row);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?IdentityBindingRecord
    {
        $key = trim($idempotencyKey);
        if ($key === '') {
            return null;
        }

        $row = CghiaIdentityBinding::query()->where('idempotency_key', $key)->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function findCurrentByAdrId(AdrMembershipId $adrId): ?IdentityBindingRecord
    {
        $row = CghiaIdentityBinding::query()
            ->where('adr_id', $adrId->value)
            ->where('lifecycle_state', IdentityBindingLifecycleState::ACTIVE->value)
            ->orderByDesc('id')
            ->first();

        return $row === null ? null : $this->mapRow($row);
    }

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
    ): IdentityBindingRecord {
        return DB::transaction(function () use (
            $priorId,
            $adrId,
            $identityStatus,
            $identityDecisionIdentity,
            $identityEvidenceFingerprint,
            $newIdempotencyKey,
            $decisionActor,
            $decisionTimestamp,
            $canonicalIdentityId,
            $identityEvidenceRefs,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
            $metadata,
        ): IdentityBindingRecord {
            $prior = CghiaIdentityBinding::query()->lockForUpdate()->find($priorId->toInt());
            if ($prior === null) {
                throw new IdentityBindingInvariantViolation(
                    'Cannot supersede: prior persistence_record_id not found.'
                );
            }

            if ((string) $prior->adr_id !== $adrId->value) {
                throw new IdentityBindingInvariantViolation(
                    'Cannot supersede: adr_id mismatch between prior record and new decision.'
                );
            }

            $newRecord = $this->persist(
                $adrId,
                $identityStatus,
                $identityDecisionIdentity,
                $identityEvidenceFingerprint,
                $newIdempotencyKey,
                $decisionActor,
                $decisionTimestamp,
                $canonicalIdentityId,
                $identityEvidenceRefs,
                $originalSourceIdentifier,
                $aggregatorRecordIdentifier,
                $metadata,
            );

            // Lifecycle control only — domain columns on prior row remain immutable.
            $prior->lifecycle_state = IdentityBindingLifecycleState::SUPERSEDED->value;
            $prior->superseded_by = $newRecord->persistenceRecordId->toInt();
            $prior->save();

            return $newRecord;
        });
    }

    public function invalidate(IdentityBindingRecordId $id): IdentityBindingRecord
    {
        $row = CghiaIdentityBinding::query()->find($id->toInt());
        if ($row === null) {
            throw new IdentityBindingInvariantViolation(
                'Cannot invalidate: persistence_record_id not found.'
            );
        }

        $row->lifecycle_state = IdentityBindingLifecycleState::INVALIDATED->value;
        $row->save();

        return $this->mapRow($row->fresh());
    }

    private function mapRow(CghiaIdentityBinding $row): IdentityBindingRecord
    {
        $supersededBy = $row->superseded_by === null
            ? null
            : IdentityBindingRecordId::fromInt((int) $row->superseded_by);

        return IdentityBindingRecord::fromPersistedRow(
            IdentityBindingRecordId::fromInt((int) $row->id),
            (string) $row->adr_id,
            $row->canonical_identity_id !== null ? (string) $row->canonical_identity_id : null,
            (string) $row->identity_status,
            (string) $row->identity_decision_identity,
            (string) $row->identity_evidence_fingerprint,
            $row->identity_evidence_refs,
            $row->original_source_identifier !== null ? (string) $row->original_source_identifier : null,
            $row->aggregator_record_identifier !== null ? (string) $row->aggregator_record_identifier : null,
            IdentityBindingLifecycleState::from((string) $row->lifecycle_state),
            (int) $row->schema_version,
            (string) $row->idempotency_key,
            $supersededBy,
            (string) $row->decision_actor,
            (string) $row->decision_timestamp,
            is_array($row->metadata) ? $row->metadata : null,
        );
    }

    private function isUniqueIdempotencyViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if ($sqlState === '23505' || $driverCode === 1062) {
            return str_contains($message, 'idempotency_key')
                || str_contains($message, 'cghia_identity_binding_records');
        }

        return str_contains($message, 'unique') && str_contains($message, 'idempotency');
    }
}
