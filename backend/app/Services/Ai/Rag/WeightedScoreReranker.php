<?php

namespace App\Services\Ai\Rag;

use App\Services\Ai\Retrieval\KnowledgeRanker;

/**
 * Local reranker: reuse existing retrieval scores and deterministic tie-breaking.
 * No external reranking provider is required for AI-14.
 */
class WeightedScoreReranker implements KnowledgeRerankerInterface
{
    public function __construct(private KnowledgeRanker $ranker) {}

    public function name(): string
    {
        return 'weighted';
    }

    public function rerank(string $query, array $hits): array
    {
        unset($query);

        return $this->ranker->sort(array_values($hits));
    }
}
