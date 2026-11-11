<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\LostConnectionException;

/**
 * Durable record store on the application database (ADR-023 §8.41).
 *
 * Changes are staged in this instance and written by commit() in one database transaction:
 * the source's state row is locked, the staged generation must still be based on the
 * committed generation (otherwise CONCURRENT_GENERATION and nothing is written), the shared
 * merge rules decide the changes, and the changed rows, the generation log entry, the new
 * generation number and the checkpoint commit together. A process that dies before the
 * database commits leaves no trace; readers never see a partial generation.
 *
 * Every row read back is decoded strictly and its content hash re-checked; a mismatch is
 * RECORD_CORRUPT. Database faults surface as LocalStoreFailure::STORE_UNAVAILABLE.
 */
final class DatabaseScientificRecordStore implements ScientificRecordStore
{
    public const RECORDS_TABLE = 'scientific_source_records';

    public const STATES_TABLE = 'scientific_source_states';

    public const GENERATIONS_TABLE = 'scientific_source_generations';

    private const CHUNK = 500;

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /** @var array<string, OpenScientificRecordGeneration> */
    private array $open = [];

    public function __construct(
        private readonly ConnectionResolverInterface $connections,
        private readonly ?string $connectionName = null,
    ) {}

    public function begin(string $sourceKey, string $mode): string
    {
        if (trim($sourceKey) === '' || strlen($sourceKey) > 64) {
            throw new LocalIndexInvariantViolation('A sourceKey must be non-empty and at most 64 bytes.');
        }
        foreach ($this->open as $generation) {
            if ($generation->sourceKey === $sourceKey) {
                throw new LocalIndexInvariantViolation("Source '{$sourceKey}' already has an open generation.");
            }
        }
        $id = $sourceKey.'#'.bin2hex(random_bytes(8));
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
        $committedAt = $at->format(DATE_ATOM);

        $commit = $this->guard('commit', fn (): LocalStoreCommit => $this->connection()->transaction(function (Connection $db) use ($generation, $source, $committedAt, $checkpoint): LocalStoreCommit {
            $now = $this->timestamp();
            $db->table(self::STATES_TABLE)->insertOrIgnore(['source_key' => $source, 'generation' => 0, 'created_at' => $now, 'updated_at' => $now]);
            $state = $db->table(self::STATES_TABLE)->where('source_key', $source)->lockForUpdate()->first();
            $committed = (int) $state->generation;
            if ($committed !== $generation->baseGeneration) {
                throw LocalStoreFailure::concurrentGeneration($source, $generation->baseGeneration, $committed);
            }

            $commit = ScientificRecordGenerationMerge::merge($generation, $this->currentFor($db, $generation), $committed + 1, $committedAt);
            $this->writeChanged($db, $commit, $now);
            $db->table(self::GENERATIONS_TABLE)->insert([
                'source_key' => $source,
                'generation' => $commit->generation,
                'mode' => $generation->mode,
                'counts' => json_encode(array_diff_key($commit->toArray(), ['source_key' => true, 'generation' => true]), self::JSON_FLAGS),
                'checkpoint_state' => $checkpoint?->state,
                'committed_at' => $committedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $update = ['generation' => $commit->generation, 'last_mode' => $generation->mode, 'last_committed_at' => $committedAt, 'updated_at' => $now];
            if ($checkpoint !== null) {
                $update['checkpoint'] = json_encode(ScientificRecordCodec::encodeCheckpoint($checkpoint), self::JSON_FLAGS);
            }
            $db->table(self::STATES_TABLE)->where('source_key', $source)->update($update);

            return $commit;
        }));
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
        $raw = $this->guard('checkpoint', fn (): mixed => $this->connection()->table(self::STATES_TABLE)->where('source_key', $sourceKey)->value('checkpoint'));

        return $raw === null ? null : ScientificRecordCodec::decodeCheckpoint($this->json($raw, $sourceKey, 'checkpoint'), $sourceKey);
    }

    public function get(string $sourceKey, string $originalIdentifier): ?StoredScientificRecord
    {
        $row = $this->guard('get', fn (): ?object => $this->connection()->table(self::RECORDS_TABLE)
            ->where('source_key', $sourceKey)->where('original_identifier', $originalIdentifier)->first());

        return $row === null ? null : $this->hydrate($row, $sourceKey);
    }

    public function records(string $sourceKey): array
    {
        $rows = $this->guard('records', fn (): array => $this->connection()->table(self::RECORDS_TABLE)
            ->where('source_key', $sourceKey)->orderBy('id')->get()->all());

        return array_map(fn (object $row): StoredScientificRecord => $this->hydrate($row, $sourceKey), $rows);
    }

    public function readActive(string $sourceKey): array
    {
        [$generation, $rows] = $this->guard('readActive', fn (): array => $this->connection()->transaction(function (Connection $db) use ($sourceKey): array {
            if ($db->getDriverName() === 'pgsql' && $db->transactionLevel() === 1) {
                $db->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
            }

            return [
                (int) ($db->table(self::STATES_TABLE)->where('source_key', $sourceKey)->value('generation') ?? 0),
                $db->table(self::RECORDS_TABLE)->where('source_key', $sourceKey)->where('lifecycle_state', LocalRecordState::ACTIVE->value)->orderBy('id')->get()->all(),
            ];
        }));

        return ['generation' => $generation, 'records' => array_map(fn (object $row): StoredScientificRecord => $this->hydrate($row, $sourceKey), $rows)];
    }

    /**
     * Stream ACTIVE rows without materializing the full corpus in memory.
     *
     * @return \Generator<int, StoredScientificRecord>
     */
    public function iterateActive(string $sourceKey): \Generator
    {
        $query = $this->connection()->table(self::RECORDS_TABLE)
            ->where('source_key', $sourceKey)
            ->where('lifecycle_state', LocalRecordState::ACTIVE->value)
            ->orderBy('id');

        foreach ($query->cursor() as $row) {
            yield $this->hydrate($row, $sourceKey);
        }
    }

    public function generation(string $sourceKey): int
    {
        return (int) ($this->guard('generation', fn (): mixed => $this->connection()->table(self::STATES_TABLE)->where('source_key', $sourceKey)->value('generation')) ?? 0);
    }

    /** @return array<string, StoredScientificRecord> */
    private function currentFor(Connection $db, OpenScientificRecordGeneration $generation): array
    {
        $source = $generation->sourceKey;
        $current = [];
        foreach (array_chunk(array_map('strval', array_keys($generation->ops())), self::CHUNK) as $identifiers) {
            foreach ($db->table(self::RECORDS_TABLE)->where('source_key', $source)->whereIn('original_identifier', $identifiers)->get() as $row) {
                $current[(string) $row->original_identifier] = $this->hydrate($row, $source);
            }
        }
        if ($generation->mode === ScientificRecordStore::MODE_SNAPSHOT) {
            foreach ($db->table(self::RECORDS_TABLE)->where('source_key', $source)->where('lifecycle_state', LocalRecordState::ACTIVE->value)->orderBy('id')->get() as $row) {
                $current[(string) $row->original_identifier] ??= $this->hydrate($row, $source);
            }
        }

        return $current;
    }

    private function writeChanged(Connection $db, LocalStoreCommit $commit, string $now): void
    {
        $rows = [];
        foreach ($commit->changed as $record) {
            $rows[] = [
                'source_key' => $record->sourceKey,
                'original_identifier' => $record->originalIdentifier,
                'lifecycle_state' => $record->state->value,
                'content_hash' => $record->contentHash,
                'datestamp' => $record->datestamp,
                'generation' => $record->generation,
                'first_generation' => $record->generation,
                'record' => $record->result === null ? null : json_encode(ScientificRecordCodec::encodeResult($record->result), self::JSON_FLAGS),
                'provenance' => json_encode($record->provenance, self::JSON_FLAGS),
                'tombstone_reason' => $record->tombstoneReason,
                'changed_at' => $record->updatedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $update = ['lifecycle_state', 'content_hash', 'datestamp', 'generation', 'record', 'provenance', 'tombstone_reason', 'changed_at', 'updated_at'];
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $db->table(self::RECORDS_TABLE)->upsert($chunk, ['source_key', 'original_identifier'], $update);
        }
    }

    private function hydrate(object $row, string $sourceKey): StoredScientificRecord
    {
        if ((string) $row->source_key !== $sourceKey) {
            throw LocalStoreFailure::corrupt($sourceKey, 'row of another source');
        }
        $state = LocalRecordState::tryFrom((string) $row->lifecycle_state) ?? throw LocalStoreFailure::corrupt($sourceKey, 'lifecycle_state');
        $result = $row->record === null ? null : ScientificRecordCodec::decodeResult($this->json($row->record, $sourceKey, 'record'), $sourceKey);
        if ($result !== null && ScientificRecordGenerationMerge::contentHash($result) !== $row->content_hash) {
            throw LocalStoreFailure::corrupt($sourceKey, 'content hash mismatch');
        }
        try {
            return new StoredScientificRecord(
                sourceKey: $sourceKey,
                originalIdentifier: (string) $row->original_identifier,
                state: $state,
                result: $result,
                contentHash: $row->content_hash === null ? null : (string) $row->content_hash,
                datestamp: $row->datestamp === null ? null : (string) $row->datestamp,
                generation: (int) $row->generation,
                updatedAt: (string) $row->changed_at,
                tombstoneReason: $row->tombstone_reason === null ? null : (string) $row->tombstone_reason,
                provenance: $this->json($row->provenance, $sourceKey, 'provenance'),
            );
        } catch (LocalIndexInvariantViolation) {
            throw LocalStoreFailure::corrupt($sourceKey, 'lifecycle invariant');
        }
    }

    /** @return array<mixed> */
    private function json(mixed $raw, string $sourceKey, string $field): array
    {
        try {
            $value = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : null;
        } catch (\JsonException) {
            $value = null;
        }

        return is_array($value) ? $value : throw LocalStoreFailure::corrupt($sourceKey, $field);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    private function guard(string $name, callable $operation): mixed
    {
        try {
            return $operation();
        } catch (LocalStoreFailure|LocalIndexInvariantViolation $known) {
            throw $known;
        } catch (\PDOException|LostConnectionException $database) {
            throw LocalStoreFailure::unavailable($name, $database);
        }
    }

    private function connection(): Connection
    {
        return $this->connections->connection($this->connectionName);
    }

    private function openGeneration(string $generationId): OpenScientificRecordGeneration
    {
        return $this->open[$generationId] ?? throw new LocalIndexInvariantViolation("Generation '{$generationId}' is not open.");
    }

    private function timestamp(): string
    {
        return (new DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
