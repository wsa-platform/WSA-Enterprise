<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use DateTimeImmutable;

/**
 * Process-memory record store for unit tests and offline tooling.
 *
 * Applies the same ScientificRecordGenerationMerge rules as DatabaseScientificRecordStore but
 * is not durable: everything is lost with the process. It is not bound in the service container.
 */
final class InMemoryScientificRecordStore implements ScientificRecordStore
{
    /** @var array<string, array<string, StoredScientificRecord>> sourceKey => identifier => record */
    private array $records = [];

    /** @var array<string, int> */
    private array $generations = [];

    /** @var array<string, HarvestCheckpoint> */
    private array $checkpoints = [];

    /** @var array<string, OpenScientificRecordGeneration> */
    private array $open = [];

    private int $sequence = 0;

    public function begin(string $sourceKey, string $mode): string
    {
        foreach ($this->open as $generation) {
            if ($generation->sourceKey === $sourceKey) {
                throw new LocalIndexInvariantViolation("Source '{$sourceKey}' already has an open generation.");
            }
        }
        $id = $sourceKey.'#'.(++$this->sequence);
        $this->open[$id] = new OpenScientificRecordGeneration($id, $sourceKey, $mode, $this->generation($sourceKey));

        return $id;
    }

    public function stageUpsert(string $generationId, ScientificSearchResult $record, ?string $datestamp = null): void
    {
        $this->openGeneration($generationId)->stageUpsert($record, $datestamp);
    }

    public function stageTombstone(string $generationId, string $originalIdentifier, LocalRecordState $state, string $reason, ?string $datestamp = null): void
    {
        $this->openGeneration($generationId)->stageTombstone($originalIdentifier, $state, $reason, $datestamp);
    }

    public function commit(string $generationId, DateTimeImmutable $at, ?HarvestCheckpoint $checkpoint = null): LocalStoreCommit
    {
        $generation = $this->openGeneration($generationId);
        $source = $generation->sourceKey;
        if ($checkpoint !== null && $checkpoint->sourceKey !== $source) {
            throw new LocalIndexInvariantViolation("Checkpoint of '{$checkpoint->sourceKey}' cannot be committed with '{$source}'.");
        }
        $committed = $this->generation($source);
        if ($committed !== $generation->baseGeneration) {
            throw LocalStoreFailure::concurrentGeneration($source, $generation->baseGeneration, $committed);
        }

        $commit = ScientificRecordGenerationMerge::merge($generation, $this->records[$source] ?? [], $committed + 1, $at->format(DATE_ATOM));
        foreach ($commit->changed as $record) {
            $this->records[$source][$record->originalIdentifier] = $record;
        }
        $this->records[$source] ??= [];
        $this->generations[$source] = $commit->generation;
        if ($checkpoint !== null) {
            $this->checkpoints[$source] = $checkpoint;
        }
        unset($this->open[$generationId]);

        return $commit;
    }

    public function discard(string $generationId): void
    {
        $this->openGeneration($generationId);
        unset($this->open[$generationId]);
    }

    public function checkpoint(string $sourceKey): ?HarvestCheckpoint
    {
        return $this->checkpoints[$sourceKey] ?? null;
    }

    public function get(string $sourceKey, string $originalIdentifier): ?StoredScientificRecord
    {
        return $this->records[$sourceKey][$originalIdentifier] ?? null;
    }

    public function records(string $sourceKey): array
    {
        return array_values($this->records[$sourceKey] ?? []);
    }

    public function readActive(string $sourceKey): array
    {
        return [
            'generation' => $this->generation($sourceKey),
            'records' => array_values(array_filter($this->records($sourceKey), static fn (StoredScientificRecord $r): bool => $r->isActive())),
        ];
    }

    public function iterateActive(string $sourceKey): \Generator
    {
        foreach ($this->readActive($sourceKey)['records'] as $record) {
            yield $record;
        }
    }

    public function generation(string $sourceKey): int
    {
        return $this->generations[$sourceKey] ?? 0;
    }

    private function openGeneration(string $generationId): OpenScientificRecordGeneration
    {
        return $this->open[$generationId] ?? throw new LocalIndexInvariantViolation("Generation '{$generationId}' is not open.");
    }
}
