<?php

namespace App\Services\Ai\Evaluation;

use App\Services\Ai\Rag\RagOrchestrator;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\KnowledgeRetrievalConfig;
use Illuminate\Support\Facades\Config;

/**
 * Isolated retrieval evaluation. Does not execute AI provider requests.
 * Temporarily overrides retrieval strategy for a case, then restores it.
 */
class RetrievalEvaluationRunner
{
    public function __construct(
        private RagOrchestrator $orchestrator,
        private RetrievalEvaluationMetrics $metrics,
        private KnowledgeRetrievalConfig $config,
    ) {}

    public function run(RetrievalEvaluationCase $case): RetrievalEvaluationResult
    {
        $previous = (string) config('ai.retrieval.strategy', 'keyword');
        $configured = $this->normalizeStrategy($case->strategy);
        Config::set('ai.retrieval.strategy', $configured);
        try {
            $rag = $this->orchestrator->assemble($case->organizationId, ['query' => $this->safeQuery($case->query)]);
        } finally {
            Config::set('ai.retrieval.strategy', $previous);
        }

        $retrieved = [];
        foreach ($rag->hits as $hit) {
            if (! $hit instanceof AiRetrievalHit) {
                continue;
            }
            $retrieved[] = $hit->sourceType.':'.$hit->sourceId;
        }
        $retrieved = $this->metrics->uniquePreserveOrder($retrieved);
        $expected = $this->metrics->uniquePreserveOrder($case->expectedIds);
        $k = max(1, $case->k);
        $metrics = $this->metrics->score($retrieved, $expected, $k);
        $top = $retrieved[0] ?? null;
        $telemetry = is_array($rag->telemetry) ? $rag->telemetry : [];
        $effective = (string) ($telemetry['retrieval_strategy'] ?? $this->config->strategy());
        if (! in_array($effective, KnowledgeRetrievalConfig::STRATEGIES, true)) {
            $effective = 'keyword';
        }
        $reason = (string) ($telemetry['fallback_reason'] ?? '');
        if (! in_array($reason, KnowledgeRetrievalConfig::FALLBACK_REASONS, true)) {
            $reason = '';
        }

        return new RetrievalEvaluationResult(
            caseId: $this->safeCaseId($case->id),
            configuredStrategy: $configured,
            effectiveStrategy: $effective,
            k: $k,
            retrievedIds: array_slice($retrieved, 0, $k),
            expectedIds: $expected,
            metrics: $metrics,
            fallbackReason: $reason !== '' ? $reason : null,
            expectedTopId: $case->expectedTopId,
            expectedTopMatched: $case->expectedTopId !== null && $top === $case->expectedTopId,
            telemetry: [
                'retrieval_status' => (string) ($telemetry['retrieval_status'] ?? 'empty'),
                'reranker' => (string) ($telemetry['reranker'] ?? ''),
            ],
        );
    }

    /**
     * @param  list<RetrievalEvaluationCase>  $cases
     */
    public function runMany(array $cases): RetrievalEvaluationReport
    {
        $results = [];
        foreach ($cases as $case) {
            if ($case instanceof RetrievalEvaluationCase) {
                $results[] = $this->run($case);
            }
        }

        return new RetrievalEvaluationReport($results);
    }

    private function normalizeStrategy(string $strategy): string
    {
        $value = strtolower(trim($strategy));

        return in_array($value, KnowledgeRetrievalConfig::STRATEGIES, true) ? $value : 'keyword';
    }

    private function safeQuery(string $query): string
    {
        $clean = trim($query);
        if ($clean === '' || str_contains($clean, 'sk-') || str_contains(strtolower($clean), 'bearer ')) {
            return '';
        }

        return mb_substr($clean, 0, 500);
    }

    private function safeCaseId(string $id): string
    {
        $id = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($id)) ?? 'case';

        return mb_substr($id !== '' ? $id : 'case', 0, 64);
    }
}
