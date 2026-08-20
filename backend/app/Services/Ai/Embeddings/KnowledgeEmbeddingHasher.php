<?php

namespace App\Services\Ai\Embeddings;

use App\Services\Ai\Retrieval\AiKnowledgeDocument;

class KnowledgeEmbeddingHasher
{
    public function hash(AiKnowledgeDocument $document): string
    {
        return hash('sha256', implode("\n", [
            $document->sourceType,
            (string) $document->sourceId,
            $document->title,
            $document->summary,
            $document->body,
            $document->searchableText,
        ]));
    }
}
