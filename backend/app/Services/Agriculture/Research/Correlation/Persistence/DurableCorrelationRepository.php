<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

use App\Services\Agriculture\Research\Correlation\CorrelationChain;

/**
 * IU-07 Durable Correlation repository — storage only.
 *
 * Does not own Cap/Path/Projection/CSQ/Composer semantics.
 */
interface DurableCorrelationRepository
{
    /**
     * Persist an immutable B7 linkage snapshot.
     * Same idempotency_key replays the existing record (no duplicate row).
     *
     * @param  array<string, mixed>|null  $metadata  Non-authoritative only.
     */
    public function persist(
        CorrelationChain $chain,
        string $idempotencyKey,
        ?array $metadata = null,
    ): DurableCorrelationRecord;

    public function findById(DurableCorrelationRecordId $id): ?DurableCorrelationRecord;

    public function findByIdempotencyKey(string $idempotencyKey): ?DurableCorrelationRecord;

    /**
     * Append a new ACTIVE snapshot and mark the prior record SUPERSEDED.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function supersede(
        DurableCorrelationRecordId $priorId,
        CorrelationChain $newChain,
        string $newIdempotencyKey,
        ?array $metadata = null,
    ): DurableCorrelationRecord;

    public function invalidate(DurableCorrelationRecordId $id): DurableCorrelationRecord;
}
