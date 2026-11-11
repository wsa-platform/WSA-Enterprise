<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Throwable;

/**
 * Disk-backed LocalIndex search using SQLite FTS5 (bounded memory).
 *
 * Documents stay in scientific_source_records; this index stores searchable text only.
 * Compatible with LocalScientificIndex method surface used by ingestion and adapters.
 */
final class SqliteFtsScientificIndex implements LocalIndexSearchEngine
{
    public const INDEX_VERSION = 'sqlite_fts_scientific_index.v1';

    private const FTS_TABLE = 'scientific_source_fts';

    private const META_TABLE = 'scientific_source_fts_meta';

    private const STOPWORDS = ['a', 'an', 'and', 'are', 'as', 'at', 'by', 'for', 'from', 'in', 'is', 'not', 'of', 'on', 'or', 'the', 'to', 'with'];

    /** @var array<string, int> */
    private array $generations = [];

    private bool $schemaReady = false;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ScientificRecordStore $store,
        private readonly string $connectionName = 'scientific_records',
    ) {}

    public function version(): string
    {
        return self::INDEX_VERSION;
    }

    /** @param  iterable<StoredScientificRecord>  $records */
    public function rebuild(string $sourceKey, iterable $records, int $generation): void
    {
        $this->ensureSchema();
        $conn = $this->connection();
        $conn->table(self::FTS_TABLE)->where('source_key', $sourceKey)->delete();

        $batch = [];
        foreach ($records as $record) {
            if ($record->sourceKey !== $sourceKey) {
                throw new LocalIndexInvariantViolation("Record of '{$record->sourceKey}' cannot rebuild the index of '{$sourceKey}'.");
            }
            if (! $record->isActive() || $record->result === null) {
                continue;
            }
            $batch[] = $this->rowFromRecord($record);
            if (count($batch) >= 250) {
                $this->insertBatch($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->insertBatch($batch);
        }
        $this->setGeneration($sourceKey, $generation);
        $this->generations[$sourceKey] = $generation;
    }

    public function synchronize(LocalStoreCommit $commit): void
    {
        $this->ensureSchema();
        foreach ($commit->changed as $record) {
            $this->apply($record);
        }
        $this->setGeneration($commit->sourceKey, max($commit->generation, $this->generation($commit->sourceKey)));
        $this->generations[$commit->sourceKey] = $this->generation($commit->sourceKey);
    }

    public function apply(StoredScientificRecord $record): void
    {
        $this->ensureSchema();
        $conn = $this->connection();
        $conn->table(self::FTS_TABLE)
            ->where('source_key', $record->sourceKey)
            ->where('original_identifier', $record->originalIdentifier)
            ->delete();
        if (! $record->isActive() || $record->result === null) {
            return;
        }
        $this->insertBatch([$this->rowFromRecord($record)]);
    }

    public function hasSource(string $sourceKey): bool
    {
        $this->ensureSchema();

        return $this->generation($sourceKey) > 0;
    }

    public function generation(string $sourceKey): int
    {
        if (isset($this->generations[$sourceKey])) {
            return $this->generations[$sourceKey];
        }
        $this->ensureSchema();
        $value = $this->connection()->table(self::META_TABLE)->where('source_key', $sourceKey)->value('generation');
        $gen = (int) ($value ?? 0);
        $this->generations[$sourceKey] = $gen;

        return $gen;
    }

    public function documentCount(string $sourceKey): int
    {
        $this->ensureSchema();

        return (int) $this->connection()->table(self::FTS_TABLE)->where('source_key', $sourceKey)->count();
    }

    /**
     * @return list<array{record: StoredScientificRecord, score: int}>
     */
    public function search(string $sourceKey, string $query, int $limit): array
    {
        $this->ensureSchema();
        $terms = array_values(array_unique(LocalScientificIndex::terms($query)));
        if ($terms === [] || $limit < 1) {
            return [];
        }

        $match = implode(' ', array_map(static fn (string $t): string => '"'.str_replace('"', '', $t).'"', $terms));
        try {
            $rows = $this->connection()->select(
                'SELECT original_identifier, bm25('.self::FTS_TABLE.') AS rank
                 FROM '.self::FTS_TABLE."
                 WHERE source_key = ? AND ".self::FTS_TABLE." MATCH ?
                 ORDER BY rank
                 LIMIT ?",
                [$sourceKey, $match, $limit]
            );
        } catch (Throwable) {
            // Fallback: AND token LIKE scan (still disk-backed, bounded by LIMIT).
            return $this->searchFallback($sourceKey, $terms, $limit);
        }

        $hits = [];
        $scoreBase = 1000;
        foreach ($rows as $i => $row) {
            $record = $this->store->get($sourceKey, (string) $row->original_identifier);
            if ($record === null || ! $record->isActive()) {
                continue;
            }
            $hits[] = [
                'record' => $record,
                'score' => $scoreBase - $i,
            ];
        }

        return $hits;
    }

    /**
     * Stream rebuild from the durable store without loading all records into PHP at once.
     */
    public function rebuildFromStore(string $sourceKey): int
    {
        if (! $this->store instanceof DatabaseScientificRecordStore) {
            $active = $this->store->readActive($sourceKey);
            $this->rebuild($sourceKey, $active['records'], $active['generation']);

            return count($active['records']);
        }

        $generation = $this->store->generation($sourceKey);
        $this->ensureSchema();
        $this->connection()->table(self::FTS_TABLE)->where('source_key', $sourceKey)->delete();

        $count = 0;
        $batch = [];
        foreach ($this->store->iterateActive($sourceKey) as $record) {
            $batch[] = $this->rowFromRecord($record);
            $count++;
            if (count($batch) >= 250) {
                $this->insertBatch($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $this->insertBatch($batch);
        }
        $this->setGeneration($sourceKey, $generation);
        $this->generations[$sourceKey] = $generation;

        return $count;
    }

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }
        $conn = $this->connection();
        if ($conn->getDriverName() !== 'sqlite') {
            throw new LocalIndexInvariantViolation('SqliteFtsScientificIndex requires a sqlite scientific_records connection.');
        }
        $conn->statement(
            'CREATE VIRTUAL TABLE IF NOT EXISTS '.self::FTS_TABLE.' USING fts5(
                source_key UNINDEXED,
                original_identifier UNINDEXED,
                title,
                abstract,
                journal,
                authors,
                tokenize = "unicode61"
            )'
        );
        $conn->statement(
            'CREATE TABLE IF NOT EXISTS '.self::META_TABLE.' (
                source_key TEXT PRIMARY KEY NOT NULL,
                generation INTEGER NOT NULL DEFAULT 0,
                index_version TEXT NOT NULL,
                updated_at TEXT NULL
            )'
        );
        $this->schemaReady = true;
    }

    private function connection(): ConnectionInterface
    {
        return $this->db->connection($this->connectionName);
    }

    private function setGeneration(string $sourceKey, int $generation): void
    {
        $this->connection()->table(self::META_TABLE)->upsert([
            [
                'source_key' => $sourceKey,
                'generation' => $generation,
                'index_version' => self::INDEX_VERSION,
                'updated_at' => now()->utc()->toIso8601String(),
            ],
        ], ['source_key'], ['generation', 'index_version', 'updated_at']);
    }

    /** @return array<string, string> */
    private function rowFromRecord(StoredScientificRecord $record): array
    {
        $result = $record->result;
        if ($result === null) {
            throw new LocalIndexInvariantViolation('Active FTS row requires a retained result payload.');
        }

        return [
            'source_key' => $record->sourceKey,
            'original_identifier' => $record->originalIdentifier,
            'title' => (string) $result->title,
            'abstract' => (string) ($result->abstract ?? ''),
            'journal' => (string) ($result->journal ?? ''),
            'authors' => implode(' ', $result->authors),
        ];
    }

    /** @param  list<array<string, string>>  $batch */
    private function insertBatch(array $batch): void
    {
        $this->connection()->table(self::FTS_TABLE)->insert($batch);
    }

    /**
     * @param  list<string>  $terms
     * @return list<array{record: StoredScientificRecord, score: int}>
     */
    private function searchFallback(string $sourceKey, array $terms, int $limit): array
    {
        $query = $this->connection()->table(self::FTS_TABLE)->where('source_key', $sourceKey);
        foreach ($terms as $term) {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('abstract', 'like', $like)
                    ->orWhere('authors', 'like', $like)
                    ->orWhere('journal', 'like', $like);
            });
        }
        $rows = $query->limit($limit)->get(['original_identifier']);
        $hits = [];
        foreach ($rows as $i => $row) {
            $record = $this->store->get($sourceKey, (string) $row->original_identifier);
            if ($record === null || ! $record->isActive()) {
                continue;
            }
            $hits[] = ['record' => $record, 'score' => 100 - $i];
        }

        return $hits;
    }
}
