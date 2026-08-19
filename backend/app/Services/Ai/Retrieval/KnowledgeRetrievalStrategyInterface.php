<?php

namespace App\Services\Ai\Retrieval;

interface KnowledgeRetrievalStrategyInterface
{
    public function name(): string;

    public function retrieve(int $organizationId, string $query): AiRetrievalResult;
}
