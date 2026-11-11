<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * The staged, not yet committed changes of one generation of one source.
 *
 * Held by the store that opened it; nothing is visible to readers until the store commits it.
 * baseGeneration is the committed generation the changes were staged against, so a commit can
 * refuse when another writer committed in between.
 */
final class OpenScientificRecordGeneration
{
    public const MAX_IDENTIFIER_LENGTH = 512;

    /** @var array<string, array{kind: string, record?: ScientificSearchResult, state?: LocalRecordState, reason?: string, datestamp: ?string}> */
    private array $ops = [];

    public function __construct(
        public readonly string $id,
        public readonly string $sourceKey,
        public readonly string $mode,
        public readonly int $baseGeneration,
    ) {
        if (! in_array($mode, [ScientificRecordStore::MODE_INCREMENTAL, ScientificRecordStore::MODE_SNAPSHOT], true)) {
            throw new LocalIndexInvariantViolation("Unknown store mode '{$mode}'.");
        }
    }

    public function stageUpsert(ScientificSearchResult $record, ?string $datestamp): void
    {
        $identifier = $record->primaryProvenance()?->originalIdentifier;
        if ($record->sourceKey !== $this->sourceKey || $identifier === null) {
            throw new LocalIndexInvariantViolation('A staged record must belong to the generation source and carry its original identifier.');
        }
        $this->stage($identifier, ['kind' => 'upsert', 'record' => $record, 'datestamp' => $datestamp]);
    }

    public function stageTombstone(string $originalIdentifier, LocalRecordState $state, string $reason, ?string $datestamp): void
    {
        if ($state === LocalRecordState::ACTIVE || trim($originalIdentifier) === '' || trim($reason) === '') {
            throw new LocalIndexInvariantViolation('A tombstone needs an identifier, a reason and a DELETED or WITHDRAWN state.');
        }
        $this->stage($originalIdentifier, ['kind' => 'tombstone', 'state' => $state, 'reason' => $reason, 'datestamp' => $datestamp]);
    }

    /** @return array<string, array{kind: string, record?: ScientificSearchResult, state?: LocalRecordState, reason?: string, datestamp: ?string}> */
    public function ops(): array
    {
        return $this->ops;
    }

    /** @param  array{kind: string, record?: ScientificSearchResult, state?: LocalRecordState, reason?: string, datestamp: ?string}  $op */
    private function stage(string $identifier, array $op): void
    {
        if (trim($identifier) === '' || strlen($identifier) > self::MAX_IDENTIFIER_LENGTH) {
            throw new LocalIndexInvariantViolation('An original identifier must be non-empty and at most '.self::MAX_IDENTIFIER_LENGTH.' bytes.');
        }
        $staged = $this->ops[$identifier] ?? null;
        if ($staged !== null && $op['datestamp'] !== null && $staged['datestamp'] !== null && $op['datestamp'] < $staged['datestamp']) {
            return;
        }
        $this->ops[$identifier] = $op;
    }
}
