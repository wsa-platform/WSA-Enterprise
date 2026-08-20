<?php

namespace App\Services\Ai\Rag;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\AiRetrievalResult;
use App\Services\Ai\Retrieval\KnowledgeContextBuilder;
use Illuminate\Support\Facades\Log;

class RagOrchestrator
{
    public function __construct(
        private AiKnowledgeRetrieverInterface $retriever,
        private RagQueryNormalizer $queries,
        private RagCandidateProcessor $processor,
        private KnowledgeRerankerInterface $reranker,
        private KnowledgeContextBuilder $context,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function assemble(int $organizationId, array $input): RagResult
    {
        $query = $this->queries->fromInput($input);
        $started = (int) round(microtime(true) * 1000);

        try {
            $result = $this->retriever->retrieve($organizationId, $query);
        } catch (\Throwable $exception) {
            Log::warning('AI retrieval failed', [
                'organization_id' => $organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return new RagResult([], '', [], [
                'candidate_count' => 0,
                'returned_count' => 0,
                'merged_candidate_count' => 0,
                'final_context_count' => 0,
                'retrieval_duration_ms' => max(0, ((int) round(microtime(true) * 1000)) - $started),
                'context_assembly_duration_ms' => 0,
                'source_types' => [],
                'retrieval_status' => 'failed',
                'fallback_reason' => 'retrieval_unavailable',
                'rag_orchestrated' => true,
                'reranker' => $this->reranker->name(),
            ], true);
        }

        return $this->finalize($organizationId, $result, $query, $started);
    }

    private function finalize(int $organizationId, AiRetrievalResult $result, string $query, int $started): RagResult
    {
        $merged = count($result->hits);
        $processed = $this->processor->process($organizationId, $result->hits);
        $ranked = $this->reranker->rerank($query, $processed);
        $maxResults = max(1, (int) config('ai.retrieval.max_results', 5));
        $hits = array_slice($ranked, 0, $maxResults);

        $contextStarted = (int) round(microtime(true) * 1000);
        $context = $this->context->build($hits);
        $contextMs = max(0, ((int) round(microtime(true) * 1000)) - $contextStarted);
        $citations = $this->citations($hits);

        $failed = ($result->telemetry['retrieval_status'] ?? '') === 'failed';
        $telemetry = array_merge($result->telemetry, [
            'merged_candidate_count' => $merged,
            'final_context_count' => count($hits),
            'returned_count' => count($hits),
            'context_assembly_duration_ms' => $contextMs,
            'retrieval_duration_ms' => max(
                0,
                (int) ($result->telemetry['retrieval_duration_ms'] ?? 0),
                ((int) round(microtime(true) * 1000)) - $started,
            ),
            'rag_orchestrated' => true,
            'reranker' => $this->reranker->name(),
        ]);

        return new RagResult($hits, $context, $citations, $telemetry, $failed);
    }

    /**
     * @param  list<AiRetrievalHit>  $hits
     * @return list<array<string, mixed>>
     */
    private function citations(array $hits): array
    {
        $citations = [];
        $seen = [];
        foreach ($hits as $hit) {
            $key = $hit->sourceType.':'.$hit->sourceId;
            if (isset($seen[$key]) || trim($hit->title) === '' || $hit->sourceId < 1) {
                continue;
            }
            $seen[$key] = true;
            $citations[] = [
                'title' => $hit->title,
                'reference' => $hit->sourceType.':'.$hit->sourceId,
                'source_type' => $hit->sourceType,
                'source_id' => $hit->sourceId,
                'score' => round($hit->score, 4),
                'freshness' => $this->processor->freshnessLabel($hit),
            ];
        }

        return $citations;
    }
}
