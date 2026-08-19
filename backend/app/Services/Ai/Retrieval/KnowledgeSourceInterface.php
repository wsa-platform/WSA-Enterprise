<?php

namespace App\Services\Ai\Retrieval;

interface KnowledgeSourceInterface
{
    public function sourceType(): string;

    /**
     * @param  list<string>  $keywords
     * @return list<AiRetrievalHit>
     */
    public function search(int $organizationId, array $keywords, int $limit): array;
}
