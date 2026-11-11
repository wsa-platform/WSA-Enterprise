<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * Versioned inverted index over the ACTIVE records of the local store (ADR-023 §8.40).
 *
 * The index is derived data: it is rebuilt from the store, and the version names the
 * tokenization and scoring so a change forces a rebuild. Matching is conjunctive — a record
 * matches only when it contains every query term — so the index never widens the compiled
 * query it receives. Ranking is title-weighted term overlap with a stable tie-break; it is not
 * a relevance verdict, which stays with the orchestrator's evidence evaluation.
 */
final class LocalScientificIndex implements LocalIndexSearchEngine
{
    public const INDEX_VERSION = 'local_scientific_index.v1';

    private const WEIGHTS = ['title' => 3, 'abstract' => 1, 'journal' => 1, 'authors' => 1];

    private const STOPWORDS = ['a', 'an', 'and', 'are', 'as', 'at', 'by', 'for', 'from', 'in', 'is', 'not', 'of', 'on', 'or', 'the', 'to', 'with'];

    /** @var array<string, array<string, array<string, int>>> sourceKey => term => record key => weight */
    private array $postings = [];

    /** @var array<string, array<string, StoredScientificRecord>> */
    private array $documents = [];

    /** @var array<string, array<string, list<string|int>>> sourceKey => record key => indexed terms */
    private array $documentTerms = [];

    /** @var array<string, int> sourceKey => store generation the index reflects */
    private array $generations = [];

    public function version(): string
    {
        return self::INDEX_VERSION;
    }

    /** @param  iterable<StoredScientificRecord>  $records */
    public function rebuild(string $sourceKey, iterable $records, int $generation): void
    {
        $this->postings[$sourceKey] = [];
        $this->documents[$sourceKey] = [];
        $this->documentTerms[$sourceKey] = [];
        foreach ($records as $record) {
            if ($record->sourceKey !== $sourceKey) {
                throw new LocalIndexInvariantViolation("Record of '{$record->sourceKey}' cannot rebuild the index of '{$sourceKey}'.");
            }
            $this->apply($record);
        }
        $this->generations[$sourceKey] = $generation;
    }

    /** Brings the index to a committed store generation. */
    public function synchronize(LocalStoreCommit $commit): void
    {
        foreach ($commit->changed as $record) {
            $this->apply($record);
        }
        $this->generations[$commit->sourceKey] = max($commit->generation, $this->generations[$commit->sourceKey] ?? 0);
    }

    /** Applies one committed change; a tombstone removes the record from search. */
    public function apply(StoredScientificRecord $record): void
    {
        $source = $record->sourceKey;
        $key = $record->recordKey();
        foreach ($this->documentTerms[$source][$key] ?? [] as $term) {
            unset($this->postings[$source][$term][$key]);
            if ($this->postings[$source][$term] === []) {
                unset($this->postings[$source][$term]);
            }
        }
        unset($this->documents[$source][$key], $this->documentTerms[$source][$key]);
        if (! $record->isActive()) {
            return;
        }
        $result = $record->result;
        $fields = [
            'title' => $result->title,
            'abstract' => $result->abstract ?? '',
            'journal' => $result->journal ?? '',
            'authors' => implode(' ', $result->authors),
        ];
        $terms = [];
        foreach ($fields as $field => $text) {
            foreach (array_unique(self::terms($text)) as $term) {
                $this->postings[$source][$term][$key] = max(self::WEIGHTS[$field], $this->postings[$source][$term][$key] ?? 0);
                $terms[$term] = true;
            }
        }
        $this->documents[$source][$key] = $record;
        $this->documentTerms[$source][$key] = array_keys($terms);
    }

    public function hasSource(string $sourceKey): bool
    {
        return isset($this->generations[$sourceKey]);
    }

    public function generation(string $sourceKey): int
    {
        return $this->generations[$sourceKey] ?? 0;
    }

    public function documentCount(string $sourceKey): int
    {
        return count($this->documents[$sourceKey] ?? []);
    }

    /**
     * @return list<array{record: StoredScientificRecord, score: int}>
     */
    public function search(string $sourceKey, string $query, int $limit): array
    {
        $terms = array_values(array_unique(self::terms($query)));
        if ($terms === [] || $limit < 1) {
            return [];
        }
        $scores = null;
        foreach ($terms as $term) {
            $posting = $this->postings[$sourceKey][$term] ?? [];
            if ($scores === null) {
                $scores = $posting;
            } else {
                $scores = array_intersect_key($scores, $posting);
                foreach ($scores as $key => $score) {
                    $scores[$key] = $score + $posting[$key];
                }
            }
            if ($scores === []) {
                return [];
            }
        }
        uksort($scores, static fn (string $a, string $b): int => [$scores[$b], $a] <=> [$scores[$a], $b]);
        $hits = [];
        foreach (array_slice($scores, 0, $limit, true) as $key => $score) {
            $hits[] = ['record' => $this->documents[$sourceKey][$key], 'score' => $score];
        }

        return $hits;
    }

    /** @return list<string> lower-cased letter/digit runs, stopwords and one-character runs removed */
    public static function terms(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => mb_strlen($token) > 1 && ! in_array($token, self::STOPWORDS, true),
        ));
    }
}
