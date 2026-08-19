<?php

namespace App\Services\Ai\Retrieval;

final class KnowledgeIngestionResult
{
    /**
     * @param  list<string>  $searchableTokens
     */
    public function __construct(
        public readonly string $action,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $slug,
        public readonly array $searchableTokens = [],
    ) {}
}
