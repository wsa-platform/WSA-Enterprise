<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * What one committed generation changed.
 */
final readonly class LocalStoreCommit
{
    /**
     * @param  list<StoredScientificRecord>  $changed  records whose stored version changed, for index maintenance
     */
    public function __construct(
        public string $sourceKey,
        public int $generation,
        public int $inserted = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public int $deleted = 0,
        public int $withdrawn = 0,
        public int $stale = 0,
        public array $changed = [],
    ) {}

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'generation' => $this->generation,
            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'deleted' => $this->deleted,
            'withdrawn' => $this->withdrawn,
            'stale' => $this->stale,
        ];
    }
}
