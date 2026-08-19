<?php

namespace App\Services\Ai\Retrieval;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Support\Facades\Log;

class KnowledgeRetrievalRouter implements AiKnowledgeRetrieverInterface
{
    public function __construct(
        private KeywordKnowledgeRetrievalStrategy $keyword,
        private SemanticKnowledgeRetrievalStrategy $semantic,
        private HybridKnowledgeRetrievalStrategy $hybrid,
        private KnowledgeSemanticIndexInterface $index,
        private KnowledgeRetrievalConfig $config,
    ) {}

    public function retrieve(int $organizationId, string $query): AiRetrievalResult
    {
        $requested = strtolower(trim((string) config('ai.retrieval.strategy', 'keyword')));
        $invalid = $this->config->configuredStrategyIsInvalid();
        $strategy = $this->config->strategy();

        if ($invalid) {
            return $this->withFallback($this->keyword->retrieve($organizationId, $query), 'invalid_strategy', $requested);
        }

        if (in_array($strategy, ['semantic', 'hybrid'], true) && ! $this->index->isAvailable()) {
            return $this->withFallback($this->keyword->retrieve($organizationId, $query), 'semantic_unavailable', $strategy);
        }

        try {
            return match ($strategy) {
                'semantic' => $this->semantic->retrieve($organizationId, $query),
                'hybrid' => $this->hybrid->retrieve($organizationId, $query),
                default => $this->keyword->retrieve($organizationId, $query),
            };
        } catch (\Throwable $exception) {
            if ($strategy === 'keyword') {
                throw $exception;
            }

            Log::warning('AI semantic retrieval failed; falling back to keyword', [
                'organization_id' => $organizationId,
                'strategy' => $strategy,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return $this->withFallback($this->keyword->retrieve($organizationId, $query), 'semantic_error', $strategy);
        }
    }

    private function withFallback(AiRetrievalResult $result, string $reason, string $requested): AiRetrievalResult
    {
        $status = ($result->telemetry['retrieval_status'] ?? 'empty') === 'failed' ? 'failed' : 'fallback';
        if (($result->telemetry['retrieval_status'] ?? '') === 'empty' && $result->hits === []) {
            $status = 'fallback';
        }
        if ($result->hits !== [] && $status !== 'failed') {
            $status = 'fallback';
        }

        return new AiRetrievalResult($result->hits, $result->context, array_merge($result->telemetry, [
            'retrieval_status' => $status,
            'retrieval_strategy' => 'keyword',
            'requested_strategy' => $requested,
            'fallback_reason' => $reason,
        ]));
    }
}
