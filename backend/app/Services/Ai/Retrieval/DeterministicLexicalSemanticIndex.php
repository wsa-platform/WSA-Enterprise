<?php

namespace App\Services\Ai\Retrieval;

/**
 * In-memory deterministic semantic index. Replaceable later by embeddings/vector storage.
 */
class DeterministicLexicalSemanticIndex implements KnowledgeSemanticIndexInterface
{
    /** @var array<string, AiKnowledgeDocument> */
    private array $documents = [];

    public function __construct(
        private KnowledgeRetrievalConfig $config,
        private SemanticKnowledgeCandidateLoader $loader,
        private DeterministicLexicalSemanticScorer $scorer,
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
    ) {}

    public function isAvailable(): bool
    {
        return $this->config->semanticEnabled();
    }

    public function index(AiKnowledgeDocument $document): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->documents[$this->key($document->sourceType, $document->sourceId)] = $document;
    }

    public function remove(string $sourceType, int $sourceId): void
    {
        unset($this->documents[$this->key($sourceType, $sourceId)]);
    }

    public function search(int $organizationId, string $query, int $limit): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $limit = max(1, $limit);
        $hits = [];
        foreach ($this->visibleDocuments($organizationId, $limit, $query) as $document) {
            $score = $this->scorer->score($query, $document);
            if ($score <= 0) {
                continue;
            }
            $hits[] = new AiRetrievalHit(
                sourceType: $document->sourceType,
                sourceId: $document->sourceId,
                title: $document->title,
                content: $this->indexer->excerpt($document),
                score: $score,
                metadata: array_merge($document->metadata, [
                    'semantic_score' => $score,
                    'retrieval_strategy' => 'semantic',
                ]),
                organizationId: $document->organizationId,
                updatedAt: $document->updatedAt,
            );
        }

        return array_slice($this->ranker->sort($hits), 0, $limit);
    }

    public function fingerprint(string $sourceType, int $sourceId): ?string
    {
        $document = $this->documents[$this->key($sourceType, $sourceId)] ?? null;
        if ($document === null) {
            return null;
        }

        return hash('sha256', implode('|', [
            $document->sourceType,
            (string) $document->sourceId,
            $document->title,
            $document->summary,
            $document->body,
            $document->searchableText,
            $document->visible ? '1' : '0',
        ]));
    }

    /**
     * @return list<AiKnowledgeDocument>
     */
    private function visibleDocuments(int $organizationId, int $limit, string $query): array
    {
        $merged = [];
        foreach ($this->loader->documents($organizationId, $limit, $query) as $document) {
            if ($this->visibleToTenant($document, $organizationId)) {
                $merged[$this->key($document->sourceType, $document->sourceId)] = $document;
            }
        }

        foreach ($this->documents as $key => $document) {
            if (! $this->visibleToTenant($document, $organizationId)) {
                unset($merged[$key]);

                continue;
            }
            $merged[$key] = $document;
        }

        return array_values($merged);
    }

    private function visibleToTenant(AiKnowledgeDocument $document, int $organizationId): bool
    {
        if (! $document->visible) {
            return false;
        }

        if ($document->sourceType === 'library_items') {
            return $document->organizationId === $organizationId;
        }

        return $document->organizationId === null;
    }

    private function key(string $sourceType, int $sourceId): string
    {
        return $sourceType.':'.$sourceId;
    }
}
