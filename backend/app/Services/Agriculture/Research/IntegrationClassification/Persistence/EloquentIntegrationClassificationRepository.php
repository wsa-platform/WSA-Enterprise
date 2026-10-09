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

        $existing = $this->findByIdempotencyKey($attributes['idempotency_key']);
        if ($existing !== null) {
            return $this->assertReplayMatches($existing, $attributes);
        }

        // The violation is resolved only after DB::transaction has rolled back (or rolled back to
        // its savepoint): PostgreSQL rejects every statement inside an aborted transaction.
        try {
            $row = DB::transaction(function () use ($adrId, $attributes): CghiaIntegrationClassification {
                if ($this->findCurrentByAdrId($adrId) !== null) {
                    throw new IntegrationClassificationActiveConflict(
                        'Cannot persist a second ACTIVE IC decision for adr_id; use supersede().'
                    );
                }

                return CghiaIntegrationClassification::query()->create($attributes);
            });
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $replay = $this->findByIdempotencyKey($attributes['idempotency_key']);
            if ($replay !== null) {
                return $this->assertReplayMatches($replay, $attributes);
            }

            if ($this->isSingleActiveViolation($e)) {
                throw new IntegrationClassificationActiveConflict(
                    'Cannot persist a second ACTIVE IC decision for adr_id; use supersede().',
                    0,
                    $e,
                );
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
        $rows = CghiaIntegrationClassification::query()
            ->where('adr_id', $adrId->value)
            ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        if ($rows->count() > 1) {
            throw new IntegrationClassificationInvariantViolation(
                'Multiple ACTIVE IC decisions for adr_id; current-head is corrupt and must fail closed.'
            );
        }

        $row = $rows->first();

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
        $attributes = IntegrationClassificationRecord::draftAttributes(
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

        try {
            return DB::transaction(function () use ($priorId, $adrId, $attributes): IntegrationClassificationRecord {
                $prior = CghiaIntegrationClassification::query()->lockForUpdate()->find($priorId->toInt());
                $this->assertSupersedablePriorIdentity($prior, $adrId);

                $existing = $this->findByIdempotencyKey($attributes['idempotency_key']);
                if ($existing !== null) {
                    return $this->assertSupersedeReplayMatches($prior, $existing, $attributes);
                }

                if ((string) $prior->lifecycle_state !== IntegrationClassificationLifecycleState::ACTIVE->value) {
                    throw new IntegrationClassificationInvariantViolation(
                        'Cannot supersede: prior record is not ACTIVE.'
                    );
                }

                // The partial unique index cannot be deferred, so the prior leaves ACTIVE first.
                $prior->lifecycle_state = IntegrationClassificationLifecycleState::SUPERSEDED->value;
                $prior->save();

                if ($this->findCurrentByAdrId($adrId) !== null) {
                    throw new IntegrationClassificationActiveConflict(
                        'Cannot supersede: another ACTIVE IC decision exists for adr_id.'
                    );
                }

                $row = CghiaIntegrationClassification::query()->create($attributes);

                $prior->superseded_by = (int) $row->id;
                $prior->save();

                return $this->mapRow($row);
            });
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $existing = $this->findByIdempotencyKey($attributes['idempotency_key']);
            if ($existing !== null) {
                $prior = CghiaIntegrationClassification::query()->find($priorId->toInt());
                $this->assertSupersedablePriorIdentity($prior, $adrId);

                return $this->assertSupersedeReplayMatches($prior, $existing, $attributes);
            }

            if ($this->isSingleActiveViolation($e)) {
                throw new IntegrationClassificationActiveConflict(
                    'Cannot supersede: another ACTIVE IC decision exists for adr_id.',
                    0,
                    $e,
                );
            }

            throw $e;
        }
    }

    public function invalidate(IntegrationClassificationRecordId $id): IntegrationClassificationRecord
    {
        return DB::transaction(function () use ($id): IntegrationClassificationRecord {
            $row = CghiaIntegrationClassification::query()->lockForUpdate()->find($id->toInt());
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
        });
    }

    private function assertSupersedablePriorIdentity(?CghiaIntegrationClassification $prior, AdrMembershipId $adrId): void
    {
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
    }

    /**
     * A replayed supersede returns the replacement as stored (its lifecycle may have moved on);
     * it never re-applies the transition and never links a key that does not belong to this prior.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function assertSupersedeReplayMatches(
        CghiaIntegrationClassification $prior,
        IntegrationClassificationRecord $existing,
        array $attributes,
    ): IntegrationClassificationRecord {
        if ($existing->persistenceRecordId->toInt() === (int) $prior->id) {
            throw new IntegrationClassificationIdempotencyConflict(
                'Cannot supersede: newIdempotencyKey belongs to the prior record itself.'
            );
        }

        $this->assertReplayMatches($existing, $attributes);

        if ($prior->superseded_by === null || (int) $prior->superseded_by !== $existing->persistenceRecordId->toInt()) {
            throw new IntegrationClassificationIdempotencyConflict(
                'Cannot supersede: newIdempotencyKey belongs to a record that is not the replacement of this prior.'
            );
        }

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $attributes  Normalized draft attributes of the request.
     */
    private function assertReplayMatches(IntegrationClassificationRecord $existing, array $attributes): IntegrationClassificationRecord
    {
        $stored = $existing->toArray();
        $mismatched = [];
        foreach (IntegrationClassificationPersistenceContract::REPLAY_CONTRACT_FIELDS as $field) {
            if ($this->replayComparable($field, $stored[$field] ?? null) !== $this->replayComparable($field, $attributes[$field] ?? null)) {
                $mismatched[] = $field;
            }
        }

        if ($mismatched !== []) {
            throw new IntegrationClassificationIdempotencyConflict(
                'idempotency_key is already bound to a different IC decision contract; mismatched fields: ['
                .implode(', ', $mismatched).'].'
            );
        }

        return $existing;
    }

    /**
     * evidence_references is an unordered evidence set (ADR-023 §8.13.11): order is ignored, duplicates
     * still count, and the stored order is never rewritten.
     */
    private function replayComparable(string $field, mixed $value): mixed
    {
        if ($field === 'evidence_references' && is_array($value)) {
            sort($value, SORT_STRING);
        }

        return $value;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $sqlState === '23505'
            || ($sqlState === '23000' && str_contains(strtolower($this->driverMessage($e)), 'unique constraint failed'));
    }

    private function isSingleActiveViolation(QueryException $e): bool
    {
        $message = $this->driverMessage($e);

        return str_contains($message, '"'.IntegrationClassificationPersistenceContract::SINGLE_ACTIVE_INDEX.'"')
            || str_contains($message, IntegrationClassificationPersistenceContract::TABLE.'.adr_id');
    }

    /**
     * Driver message only: QueryException::getMessage() also embeds the SQL, which names every column.
     */
    private function driverMessage(QueryException $e): string
    {
        $message = $e->errorInfo[2] ?? null;
        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $e->getPrevious()?->getMessage() ?? '';
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
}
