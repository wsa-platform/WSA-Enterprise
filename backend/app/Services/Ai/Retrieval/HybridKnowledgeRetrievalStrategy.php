<?php

namespace App\Services\Ai\Retrieval;

class HybridKnowledgeRetrievalStrategy implements KnowledgeRetrievalStrategyInterface
{
    public function __construct(
        private KeywordKnowledgeRetriever $keywordRetriever,
        private LibraryItemKnowledgeSource $libraryItems,
        private BeeKnowledgeTopicKnowledgeSource $beeKnowledgeTopics,
        private KnowledgeSemanticIndexInterface $index,
        private KnowledgeRanker $ranker,
        private KnowledgeFreshnessService $freshness,
        private KnowledgeRetrievalConfig $config,
        private KnowledgeContextBuilder $context,
        private SemanticKnowledgeCandidateLoader $loader,
        private DeterministicLexicalSemanticScorer $scorer,
    ) {}

    public function name(): string
    {
        return 'hybrid';
    }

    public function retrieve(int $organizationId, string $query): AiRetrievalResult
    {
        $started = (int) round(microtime(true) * 1000);
        $keywords = $this->keywordRetriever->keywords($query);
        $candidateLimit = max(1, (int) config('ai.retrieval.candidate_limit', 40));
        $maxResults = max(1, (int) config('ai.retrieval.max_results', 5));

        $keywordHits = $keywords === [] ? [] : array_merge(
            $this->libraryItems->search($organizationId, $keywords, $candidateLimit, $query),
            $this->beeKnowledgeTopics->search($organizationId, $keywords, $candidateLimit, $query),
        );
        $semanticHits = $this->index->isAvailable()
            ? $this->index->search($organizationId, $query, $candidateLimit)
            : [];

        $keywordByKey = [];
        foreach ($keywordHits as $hit) {
            $keywordByKey[$this->key($hit)] = $hit;
        }
        $semanticByKey = [];
        foreach ($semanticHits as $hit) {
            $semanticByKey[$this->key($hit)] = $hit;
        }

        $documents = [];
        foreach ($this->loader->documents($organizationId, $candidateLimit, $query) as $document) {
            $documents[$this->keyFromDocument($document)] = $document;
        }

        $keys = array_unique(array_merge(array_keys($keywordByKey), array_keys($semanticByKey)));
        $hits = [];
        foreach ($keys as $key) {
            $keywordHit = $keywordByKey[$key] ?? null;
            $semanticHit = $semanticByKey[$key] ?? null;
            $seed = $keywordHit ?? $semanticHit;
            if ($seed === null) {
                continue;
            }
            $document = $documents[$key] ?? null;
            $keywordScore = $keywordHit?->score ?? 0.0;
            $semanticScore = $semanticHit?->score ?? ($document !== null ? $this->scorer->score($query, $document) : 0.0);
            $freshnessScore = $this->freshness->rankingScore($seed->updatedAt);
            $hybridScore = round(
                ($keywordScore * $this->config->keywordWeight())
                + ($semanticScore * $this->config->semanticWeight())
                + ($freshnessScore * $this->config->freshnessWeight()),
                4,
            );
            if ($hybridScore <= 0) {
                continue;
            }

            $hits[] = new AiRetrievalHit(
                sourceType: $seed->sourceType,
                sourceId: $seed->sourceId,
                title: $seed->title,
                content: $seed->content,
                score: $hybridScore,
                metadata: array_merge($seed->metadata, [
                    'keyword_score' => $keywordScore,
                    'semantic_score' => $semanticScore,
                    'freshness_score' => $freshnessScore,
                    'hybrid_score' => $hybridScore,
                    'retrieval_strategy' => $this->name(),
                ]),
                organizationId: $seed->organizationId,
                updatedAt: $seed->updatedAt,
            );
        }

        $candidateCount = count($hits);
        $hits = array_slice($this->ranker->sort($hits), 0, $maxResults);
        $sourceTypes = array_values(array_unique(array_map(
            static fn (AiRetrievalHit $hit): string => $hit->sourceType,
            $hits,
        )));

        return new AiRetrievalResult($hits, $this->context->build($hits), [
            'candidate_count' => $candidateCount,
            'returned_count' => count($hits),
            'retrieval_duration_ms' => max(0, ((int) round(microtime(true) * 1000)) - $started),
            'source_types' => $sourceTypes,
            'freshness_distribution' => $this->freshness->distribution(
                array_map(static fn (AiRetrievalHit $hit) => $hit->updatedAt, $hits),
            ),
            'retrieval_status' => $hits === [] ? 'empty' : 'ok',
            'retrieval_strategy' => $this->name(),
            'keyword_candidate_count' => count($keywordHits),
            'semantic_candidate_count' => count($semanticHits),
            'hybrid_result_count' => count($hits),
        ]);
    }

    private function key(AiRetrievalHit $hit): string
    {
        return $hit->sourceType.':'.$hit->sourceId;
    }

    private function keyFromDocument(AiKnowledgeDocument $document): string
    {
        return $document->sourceType.':'.$document->sourceId;
    }
}
