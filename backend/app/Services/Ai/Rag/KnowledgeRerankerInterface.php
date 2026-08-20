<?php

namespace App\Services\Ai\Rag;

use App\Services\Ai\Retrieval\AiRetrievalHit;

interface KnowledgeRerankerInterface
{
    public function name(): string;

    /**
     * Reorder retrieval candidates. Implementations must not call external APIs
     * unless a future provider is explicitly configured.
     *
     * @param  list<AiRetrievalHit>  $hits
     * @return list<AiRetrievalHit>
     */
    public function rerank(string $query, array $hits): array;
}
