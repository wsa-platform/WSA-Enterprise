<?php

namespace App\Services\Ai\Retrieval;

final class BeeKnowledgeBackfillResult
{
    /**
     * @param  list<int>  $updatedIds
     * @param  list<int>  $skippedIds
     * @param  list<int>  $unchangedIds
     */
    public function __construct(
        public readonly int $updated,
        public readonly int $skipped,
        public readonly int $unchanged,
        public readonly array $updatedIds = [],
        public readonly array $skippedIds = [],
        public readonly array $unchangedIds = [],
    ) {}
}
