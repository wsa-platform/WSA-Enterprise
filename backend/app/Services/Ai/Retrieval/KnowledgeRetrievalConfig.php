<?php

namespace App\Services\Ai\Retrieval;

class KnowledgeRetrievalConfig
{
    /** @var list<string> */
    public const STRATEGIES = ['keyword', 'semantic', 'hybrid'];

    /** @var list<string> */
    public const FALLBACK_REASONS = [
        'semantic_unavailable',
        'semantic_error',
        'invalid_strategy',
        'keyword_error',
        'retrieval_unavailable',
    ];

    public function strategy(): string
    {
        $value = strtolower(trim((string) config('ai.retrieval.strategy', 'keyword')));

        return in_array($value, self::STRATEGIES, true) ? $value : 'keyword';
    }

    public function configuredStrategyIsInvalid(): bool
    {
        $value = strtolower(trim((string) config('ai.retrieval.strategy', 'keyword')));

        return $value !== '' && ! in_array($value, self::STRATEGIES, true);
    }

    public function semanticEnabled(): bool
    {
        return (bool) config('ai.retrieval.semantic_enabled', true);
    }

    public function keywordWeight(): float
    {
        return $this->bound((float) config('ai.retrieval.keyword_weight', 1.0), 0.0, 2.0);
    }

    public function semanticWeight(): float
    {
        return $this->bound((float) config('ai.retrieval.semantic_weight', 0.25), 0.0, 0.5);
    }

    public function freshnessWeight(): float
    {
        return $this->bound((float) config('ai.retrieval.freshness_weight', 0.05), 0.0, 1.0);
    }

    private function bound(float $value, float $min, float $max): float
    {
        if (! is_finite($value)) {
            return $min;
        }

        return max($min, min($max, $value));
    }
}
