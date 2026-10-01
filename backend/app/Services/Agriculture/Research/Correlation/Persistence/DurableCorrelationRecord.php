<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

use App\Services\Agriculture\Research\Correlation\AggregatorRecordReference;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use App\Services\Agriculture\Research\Correlation\QuestionIdentityReference;
use App\Services\Agriculture\Research\Correlation\RetrievalEventReference;
use App\Services\Agriculture\Research\Correlation\SourceRecordReference;
use App\Services\Agriculture\Research\Correlation\VariantIdentityReference;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;

/**
 * Immutable durable B7 linkage snapshot (IU-07 storage DTO).
 *
 * Owns storage fields only. Does not mint domain identities, Cap/Path/Projection/CSQ authority,
 * or persist Projection/C9 payloads.
 */
final readonly class DurableCorrelationRecord
{
    public const CURRENT_SCHEMA_VERSION = 1;

    /**
     * @param  array<string, mixed>|null  $metadata  Non-authoritative diagnostic context only.
     */
    private function __construct(
        public DurableCorrelationRecordId $persistenceRecordId,
        public QuestionIdentityReference $questionIdentity,
        public CanonicalQueryId $csqIdentity,
        public ?VariantIdentityReference $variantId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public string $capabilityDecisionIdentity,
        public PathId $pathId,
        public PathDecisionId $pathDecisionId,
        public ProjectionIdentity $projectionIdentity,
        public ?RetrievalEventReference $retrievalEvent,
        public ?SourceRecordReference $originalSourceIdentifier,
        public ?AggregatorRecordReference $aggregatorRecordIdentifier,
        public DurableCorrelationLifecycleState $lifecycleState,
        public int $schemaVersion,
        public string $idempotencyKey,
        public ?DurableCorrelationRecordId $supersededBy,
        public ?array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function fromPersistedRow(
        DurableCorrelationRecordId $persistenceRecordId,
        string $questionIdentity,
        string $csqIdentity,
        ?string $variantId,
        string $adrId,
        ?string $canonicalIdentityId,
        string $capabilityDecisionIdentity,
        string $pathId,
        string $pathDecisionIdentity,
        string $projectionIdentity,
        ?string $retrievalTimestamp,
        ?string $originalSourceIdentifier,
        ?string $aggregatorRecordIdentifier,
        DurableCorrelationLifecycleState $lifecycleState,
        int $schemaVersion,
        string $idempotencyKey,
        ?DurableCorrelationRecordId $supersededBy,
        ?array $metadata,
    ): self {
        self::assertPersistenceIdDistinctFromDomain(
            $persistenceRecordId,
            $questionIdentity,
            $csqIdentity,
            $variantId,
            $adrId,
            $canonicalIdentityId,
            $capabilityDecisionIdentity,
            $pathId,
            $pathDecisionIdentity,
            $projectionIdentity,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
        );

        return new self(
            $persistenceRecordId,
            QuestionIdentityReference::fromString($questionIdentity),
            CanonicalQueryId::fromString($csqIdentity),
            $variantId === null ? null : VariantIdentityReference::fromString($variantId),
            AdrMembershipId::fromString($adrId),
            $canonicalIdentityId === null ? null : CanonicalSourceIdentityId::fromString($canonicalIdentityId),
            trim($capabilityDecisionIdentity),
            PathId::fromString($pathId),
            PathDecisionId::fromString($pathDecisionIdentity),
            ProjectionIdentity::fromString($projectionIdentity),
            $retrievalTimestamp === null ? null : RetrievalEventReference::at($retrievalTimestamp),
            $originalSourceIdentifier === null ? null : SourceRecordReference::fromString($originalSourceIdentifier),
            $aggregatorRecordIdentifier === null ? null : AggregatorRecordReference::fromString($aggregatorRecordIdentifier),
            $lifecycleState,
            $schemaVersion,
            trim($idempotencyKey),
            $supersededBy,
            $metadata,
        );
    }

    /**
     * Snapshot B7 refs from an IU-06 CorrelationChain for a new ACTIVE row (id assigned after insert).
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function draftFromChain(
        CorrelationChain $chain,
        string $idempotencyKey,
        ?array $metadata = null,
    ): array {
        $key = trim($idempotencyKey);
        if ($key === '') {
            throw new DurableCorrelationInvariantViolation('idempotency_key must be a non-empty opaque string.');
        }

        $payload = $chain->toArray();

        return [
            'question_identity' => $payload['question_identity'],
            'csq_identity' => $payload['csq_identity'],
            'variant_id' => $payload['variant_id'],
            'adr_id' => $payload['adr_id'],
            'canonical_identity_id' => $payload['canonical_identity_id'],
            'capability_decision_identity' => $payload['capability_decision_identity'],
            'path_id' => $payload['path_id'],
            'path_decision_identity' => $payload['path_decision_identity'],
            'projection_identity' => $payload['projection_identity'],
            'retrieval_timestamp' => $payload['retrieval_timestamp'],
            'original_source_identifier' => $payload['original_source_identifier'],
            'aggregator_record_identifier' => $payload['aggregator_record_identifier'],
            'composite_is_canonical_source_identity' => false,
            'lifecycle_state' => DurableCorrelationLifecycleState::ACTIVE->value,
            'schema_version' => self::CURRENT_SCHEMA_VERSION,
            'idempotency_key' => $key,
            'superseded_by' => null,
            'metadata' => $metadata,
        ];
    }

    public function isCanonicalSourceIdentity(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'persistence_record_id' => $this->persistenceRecordId->value,
            'question_identity' => $this->questionIdentity->value,
            'csq_identity' => $this->csqIdentity->value,
            'variant_id' => $this->variantId?->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'capability_decision_identity' => $this->capabilityDecisionIdentity,
            'path_id' => $this->pathId->value,
            'path_decision_identity' => $this->pathDecisionId->value,
            'projection_identity' => $this->projectionIdentity->value,
            'retrieval_timestamp' => $this->retrievalEvent?->retrievedAt,
            'original_source_identifier' => $this->originalSourceIdentifier?->value,
            'aggregator_record_identifier' => $this->aggregatorRecordIdentifier?->value,
            'composite_is_canonical_source_identity' => false,
            'lifecycle_state' => $this->lifecycleState->value,
            'schema_version' => $this->schemaVersion,
            'idempotency_key' => $this->idempotencyKey,
            'superseded_by' => $this->supersededBy?->value,
            'metadata' => $this->metadata,
        ];
    }

    private static function assertPersistenceIdDistinctFromDomain(
        DurableCorrelationRecordId $persistenceRecordId,
        string $questionIdentity,
        string $csqIdentity,
        ?string $variantId,
        string $adrId,
        ?string $canonicalIdentityId,
        string $capabilityDecisionIdentity,
        string $pathId,
        string $pathDecisionIdentity,
        string $projectionIdentity,
        ?string $originalSourceIdentifier,
        ?string $aggregatorRecordIdentifier,
    ): void {
        $pid = $persistenceRecordId->value;
        $domain = [
            $questionIdentity,
            $csqIdentity,
            $variantId,
            $adrId,
            $canonicalIdentityId,
            $capabilityDecisionIdentity,
            $pathId,
            $pathDecisionIdentity,
            $projectionIdentity,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
        ];
        foreach ($domain as $value) {
            if ($value !== null && $value !== '' && $value === $pid) {
                throw new DurableCorrelationInvariantViolation(
                    'persistence_record_id must not equal any B7 domain or external identity reference.'
                );
            }
        }
    }
}
