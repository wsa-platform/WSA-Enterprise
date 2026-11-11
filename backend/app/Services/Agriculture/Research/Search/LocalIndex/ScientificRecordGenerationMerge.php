<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * The lifecycle semantics of committing one generation, shared by every store implementation.
 *
 * Pure: given the staged changes and the current stored versions it returns what changes.
 * A change older than the stored datestamp is stale and ignored; identical content is
 * unchanged; a tombstone keeps the last known provenance; a SNAPSHOT generation withdraws
 * every ACTIVE record it did not contain.
 */
final class ScientificRecordGenerationMerge
{
    public const ABSENT_FROM_SNAPSHOT = 'absent_from_complete_snapshot';

    /**
     * @param  array<string, StoredScientificRecord>  $current  the stored version of every staged identifier
     *                                                          and, for a SNAPSHOT, of every ACTIVE record of the source
     */
    public static function merge(OpenScientificRecordGeneration $generation, array $current, int $number, string $committedAt): LocalStoreCommit
    {
        $source = $generation->sourceKey;
        $counts = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 0, 'stale' => 0];
        $changed = [];
        $ops = $generation->ops();

        foreach ($ops as $identifier => $op) {
            $identifier = (string) $identifier;
            $stored = $current[$identifier] ?? null;
            if ($stored !== null && $stored->sourceKey !== $source) {
                throw new LocalIndexInvariantViolation("Stored record of '{$stored->sourceKey}' cannot be merged into '{$source}'.");
            }
            if ($stored !== null && $op['datestamp'] !== null && $stored->datestamp !== null && $op['datestamp'] < $stored->datestamp) {
                $counts['stale']++;

                continue;
            }
            if ($op['kind'] === 'upsert') {
                $hash = self::contentHash($op['record']);
                if ($stored !== null && $stored->isActive() && $stored->contentHash === $hash) {
                    $counts['unchanged']++;

                    continue;
                }
                $next = new StoredScientificRecord(
                    sourceKey: $source,
                    originalIdentifier: $identifier,
                    state: LocalRecordState::ACTIVE,
                    result: $op['record'],
                    contentHash: $hash,
                    datestamp: $op['datestamp'],
                    generation: $number,
                    updatedAt: $committedAt,
                    provenance: $op['record']->primaryProvenance()?->toArray() ?? [],
                );
                $counts[$stored === null ? 'inserted' : 'updated']++;
            } else {
                if ($stored !== null && $stored->state === $op['state']) {
                    $counts['unchanged']++;

                    continue;
                }
                $next = new StoredScientificRecord(
                    sourceKey: $source,
                    originalIdentifier: $identifier,
                    state: $op['state'],
                    result: null,
                    contentHash: null,
                    datestamp: $op['datestamp'] ?? $stored?->datestamp,
                    generation: $number,
                    updatedAt: $committedAt,
                    tombstoneReason: $op['reason'],
                    provenance: $stored?->provenance ?? [],
                );
                $counts[$op['state'] === LocalRecordState::DELETED ? 'deleted' : 'withdrawn']++;
            }
            $changed[$identifier] = $next;
        }

        if ($generation->mode === ScientificRecordStore::MODE_SNAPSHOT) {
            foreach ($current as $identifier => $stored) {
                $identifier = (string) $identifier;
                if ($stored->isActive() && ! isset($ops[$identifier])) {
                    $changed[$identifier] = new StoredScientificRecord(
                        sourceKey: $source,
                        originalIdentifier: $identifier,
                        state: LocalRecordState::WITHDRAWN,
                        result: null,
                        contentHash: null,
                        datestamp: $stored->datestamp,
                        generation: $number,
                        updatedAt: $committedAt,
                        tombstoneReason: self::ABSENT_FROM_SNAPSHOT,
                        provenance: $stored->provenance,
                    );
                    $counts['withdrawn']++;
                }
            }
        }

        return new LocalStoreCommit($source, $number, ...$counts, changed: array_values($changed));
    }

    /** Content identity: what a reader sees, never when it was fetched. */
    public static function contentHash(ScientificSearchResult $record): string
    {
        $provenance = $record->primaryProvenance();

        return hash('sha256', (string) json_encode([
            $record->title,
            $record->authors,
            $record->publicationYear,
            $record->doi,
            $record->canonicalUrl,
            $record->abstract,
            $record->journal,
            $provenance?->recordUrl,
            $provenance?->publishedAt,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
