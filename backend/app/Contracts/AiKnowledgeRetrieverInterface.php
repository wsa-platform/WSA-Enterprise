<?php

namespace App\Contracts;

use App\Services\Ai\Retrieval\AiRetrievalResult;

interface AiKnowledgeRetrieverInterface
{
    public function retrieve(int $organizationId, string $query): AiRetrievalResult;
}
