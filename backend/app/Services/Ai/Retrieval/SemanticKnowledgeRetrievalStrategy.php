<?php

namespace App\Services\Ai\Retrieval;

class SemanticKnowledgeRetrievalStrategy implements KnowledgeRetrievalStrategyInterface
{
    public function __construct(
        private KnowledgeSemanticIndexInterface $index,
        private KnowledgeContextBuilder $context,
        private KnowledgeFreshnessService $freshness,
    ) {}

    public function name(): string
    {
        return 'semantic';
    }

    public function retrieve(int $organizationId, string $query): AiRetrievalResult
    {
        $started = (int) round(microtime(true) * 1000);
        if (! $this->index->isAvailable()) {
            return AiRetrievalResult::empty([
                'retrieval_duration_ms' => max(0, ((int) round(microtime(true) * 1000)) - $started),
                'retrieval_strategy' => $this->name(),
                'retrieval_status' => 'empty',
                'semantic_unavailable' => true,
            ]);
        }

        $limit = max(1, (int) config('ai.retrieval.max_results', 5));
        $candidateLimit = max(1, (int) config('ai.retrieval.candidate_limit', 40));
        $hits = $this->index->search($organizationId, $query, $candidateLimit);
        $candidateCount = count($hits);
        $hits = array_slice($hits, 0, $limit);
        $sourceTypes = array_values(array_unique(array_map(
            static fn (AiRetrievalHit $hit): string => $hit->sourceType,
            $hits,
        )));

        $telemetry = [
            'candidate_count' => $candidateCount,
            'returned_count' => count($hits),
            'retrieval_duration_ms' => max(0, ((int) round(microtime(true) * 1000)) - $started),
            'source_types' => $sourceTypes,
            'freshness_distribution' => $this->freshness->distribution(
                array_map(static fn (AiRetrievalHit $hit) => $hit->updatedAt, $hits),
            ),
            'retrieval_status' => $hits === [] ? 'empty' : 'ok',
            'retrieval_strategy' => $this->name(),
            'keyword_candidate_count' => 0,
            'semantic_candidate_count' => $candidateCount,
        ];
        if (method_exists($this->index, 'lastSearchStats')) {
            foreach ($this->index->lastSearchStats() as $key => $value) {
                if (in_array($key, ['embedding_provider', 'embedding_model', 'embedding_duration_ms', 'vector_search_duration_ms', 'similarity_threshold', 'semantic_result_count', 'ann_used', 'distance_metric', 'hnsw_available', 'embedding_attempts'], true)) {
                    $telemetry[$key] = $value;
                }
            }
        }

        return new AiRetrievalResult($hits, $this->context->build($hits), $telemetry);
    }
}
