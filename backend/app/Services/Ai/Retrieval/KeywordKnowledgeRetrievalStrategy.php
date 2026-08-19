<?php

namespace App\Services\Ai\Retrieval;

class KeywordKnowledgeRetrievalStrategy implements KnowledgeRetrievalStrategyInterface
{
    public function __construct(private KeywordKnowledgeRetriever $retriever) {}

    public function name(): string
    {
        return 'keyword';
    }

    public function retrieve(int $organizationId, string $query): AiRetrievalResult
    {
        $result = $this->retriever->retrieve($organizationId, $query);

        return new AiRetrievalResult($result->hits, $result->context, array_merge($result->telemetry, [
            'retrieval_strategy' => $this->name(),
            'keyword_candidate_count' => max(0, (int) ($result->telemetry['candidate_count'] ?? 0)),
            'semantic_candidate_count' => 0,
        ]));
    }
}
