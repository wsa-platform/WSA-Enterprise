<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

use App\Models\DurableCorrelation;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent adapter for IU-07 Durable Correlation persistence.
 */
final class EloquentDurableCorrelationRepository implements DurableCorrelationRepository
{
    public function persist(
        CorrelationChain $chain,
        string $idempotencyKey,
        ?array $metadata = null,
    ): DurableCorrelationRecord {
        if ($metadata !== null) {
            DurableCorrelationPersistenceContract::assertMetadataNonAuthoritative($metadata);
        }

        $existing = $this->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        $attributes = DurableCorrelationRecord::draftFromChain($chain, $idempotencyKey, $metadata);

        try {
            $row = DurableCorrelation::query()->create($attributes);
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

    public function findById(DurableCorrelationRecordId $id): ?DurableCorrelationRecord
    {
        $row = DurableCorrelation::query()->find($id->toInt());

        return $row === null ? null : $this->mapRow($row);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?DurableCorrelationRecord
    {
        $key = trim($idempotencyKey);
        if ($key === '') {
            return null;
        }

        $row = DurableCorrelation::query()->where('idempotency_key', $key)->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function supersede(
        DurableCorrelationRecordId $priorId,
        CorrelationChain $newChain,
        string $newIdempotencyKey,
        ?array $metadata = null,
    ): DurableCorrelationRecord {
        return DB::transaction(function () use ($priorId, $newChain, $newIdempotencyKey, $metadata): DurableCorrelationRecord {
            $prior = DurableCorrelation::query()->lockForUpdate()->find($priorId->toInt());
            if ($prior === null) {
                throw new DurableCorrelationInvariantViolation(
                    'Cannot supersede: prior persistence_record_id not found.'
                );
            }

            $newRecord = $this->persist($newChain, $newIdempotencyKey, $metadata);

            // Lifecycle control only — B7 columns on prior row remain immutable.
            $prior->lifecycle_state = DurableCorrelationLifecycleState::SUPERSEDED->value;
            $prior->superseded_by = $newRecord->persistenceRecordId->toInt();
            $prior->save();

            return $newRecord;
        });
    }

    public function invalidate(DurableCorrelationRecordId $id): DurableCorrelationRecord
    {
        $row = DurableCorrelation::query()->find($id->toInt());
        if ($row === null) {
            throw new DurableCorrelationInvariantViolation(
                'Cannot invalidate: persistence_record_id not found.'
            );
        }

        $row->lifecycle_state = DurableCorrelationLifecycleState::INVALIDATED->value;
        $row->save();

        return $this->mapRow($row->fresh());
    }

    private function mapRow(DurableCorrelation $row): DurableCorrelationRecord
    {
        $supersededBy = $row->superseded_by === null
            ? null
            : DurableCorrelationRecordId::fromInt((int) $row->superseded_by);

        return DurableCorrelationRecord::fromPersistedRow(
            DurableCorrelationRecordId::fromInt((int) $row->id),
            (string) $row->question_identity,
            (string) $row->csq_identity,
            $row->variant_id !== null ? (string) $row->variant_id : null,
            (string) $row->adr_id,
            $row->canonical_identity_id !== null ? (string) $row->canonical_identity_id : null,
            (string) $row->capability_decision_identity,
            (string) $row->path_id,
            (string) $row->path_decision_identity,
            (string) $row->projection_identity,
            $row->retrieval_timestamp !== null ? (string) $row->retrieval_timestamp : null,
            $row->original_source_identifier !== null ? (string) $row->original_source_identifier : null,
            $row->aggregator_record_identifier !== null ? (string) $row->aggregator_record_identifier : null,
            DurableCorrelationLifecycleState::from((string) $row->lifecycle_state),
            (int) $row->schema_version,
            (string) $row->idempotency_key,
            $supersededBy,
            is_array($row->metadata) ? $row->metadata : null,
        );
    }

    private function isUniqueIdempotencyViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        // PostgreSQL unique_violation = 23505; MySQL = 1062
        if ($sqlState === '23505' || $driverCode === 1062) {
            return str_contains($message, 'idempotency_key')
                || str_contains($message, 'durable_correlation_records');
        }

        return str_contains($message, 'unique') && str_contains($message, 'idempotency');
    }
}
