<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * One record version in the local store, identified by sourceKey and the provider's identifier.
 *
 * Only an ACTIVE record has content. A tombstone keeps the last known provenance so a
 * deletion or withdrawal stays explainable.
 */
final readonly class StoredScientificRecord
{
    /**
     * @param  array<string, mixed>  $provenance  last known provenance record (ScientificSourceRecordProvenance::toArray)
     */
    public function __construct(
        public string $sourceKey,
        public string $originalIdentifier,
        public LocalRecordState $state,
        public ?ScientificSearchResult $result,
        public ?string $contentHash,
        public ?string $datestamp,
        public int $generation,
        public string $updatedAt,
        public ?string $tombstoneReason = null,
        public array $provenance = [],
    ) {
        if (trim($sourceKey) === '' || trim($originalIdentifier) === '') {
            throw new LocalIndexInvariantViolation('A stored record needs a sourceKey and an original identifier.');
        }
        if (($state === LocalRecordState::ACTIVE) !== ($result !== null)) {
            throw new LocalIndexInvariantViolation('Only an ACTIVE record carries content, and an ACTIVE record must.');
        }
        if ($state === LocalRecordState::ACTIVE && ($contentHash === null || $tombstoneReason !== null)) {
            throw new LocalIndexInvariantViolation('An ACTIVE record needs a content hash and no tombstone reason.');
        }
        if ($state !== LocalRecordState::ACTIVE && $tombstoneReason === null) {
            throw new LocalIndexInvariantViolation('A tombstone needs a reason.');
        }
        if ($result !== null && $result->sourceKey !== $sourceKey) {
            throw new LocalIndexInvariantViolation("Record of '{$result->sourceKey}' cannot be stored under '{$sourceKey}'.");
        }
    }

    public static function key(string $sourceKey, string $originalIdentifier): string
    {
        return $sourceKey.'|'.$originalIdentifier;
    }

    public function recordKey(): string
    {
        return self::key($this->sourceKey, $this->originalIdentifier);
    }

    public function isActive(): bool
    {
        return $this->state === LocalRecordState::ACTIVE;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'original_identifier' => $this->originalIdentifier,
            'state' => $this->state->value,
            'content_hash' => $this->contentHash,
            'datestamp' => $this->datestamp,
            'generation' => $this->generation,
            'updated_at' => $this->updatedAt,
            'tombstone_reason' => $this->tombstoneReason,
            'provenance' => $this->provenance,
        ];
    }
}
