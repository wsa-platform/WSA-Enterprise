<?php

namespace App\Services\Ai\Rag;

class IdentityReranker implements KnowledgeRerankerInterface
{
    public function name(): string
    {
        return 'identity';
    }

    public function rerank(string $query, array $hits): array
    {
        return array_values($hits);
    }
}
