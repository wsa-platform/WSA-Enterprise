<?php

namespace App\Services\Ai\Retrieval;

interface KnowledgeSemanticIndexInterface
{
    public function isAvailable(): bool;

    public function index(AiKnowledgeDocument $document): void;

    public function remove(string $sourceType, int $sourceId): void;

    /**
     * @return list<AiRetrievalHit>
     */
    public function search(int $organizationId, string $query, int $limit): array;

    public function fingerprint(string $sourceType, int $sourceId): ?string;
}
