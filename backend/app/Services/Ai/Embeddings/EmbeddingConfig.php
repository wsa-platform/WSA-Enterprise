<?php

namespace App\Services\Ai\Embeddings;

class EmbeddingConfig
{
    /** @var list<string> */
    public const PROVIDERS = ['mock', 'openai'];

    public function provider(): string
    {
        $value = strtolower(trim((string) config('ai.embeddings.provider', 'mock')));

        return in_array($value, self::PROVIDERS, true) ? $value : 'mock';
    }

    public function configuredProviderIsInvalid(): bool
    {
        $value = strtolower(trim((string) config('ai.embeddings.provider', 'mock')));

        return $value !== '' && ! in_array($value, self::PROVIDERS, true);
    }

    public function model(): string
    {
        $configured = trim((string) config('ai.embeddings.model', ''));
        if ($configured !== '') {
            return mb_substr($configured, 0, 128);
        }

        return $this->provider() === 'openai' ? 'text-embedding-3-small' : 'mock-hash-v1';
    }

    public function dimensions(): int
    {
        $value = (int) config('ai.embeddings.dimensions', $this->provider() === 'openai' ? 1536 : 64);

        return max(8, min(3072, $value));
    }

    public function timeoutSeconds(): int
    {
        return max(1, (int) config('ai.embeddings.timeout', config('ai.openai.timeout', 30)));
    }

    public function connectTimeoutSeconds(): int
    {
        $configured = (int) config('ai.embeddings.connect_timeout', config('ai.openai.connect_timeout', 10));

        return max(1, min($configured, $this->timeoutSeconds()));
    }

    public function retryTimes(): int
    {
        return max(0, min(5, (int) config('ai.embeddings.retry_times', config('ai.openai.retry_times', 2))));
    }

    public function retrySleepMs(): int
    {
        return max(0, min(2000, (int) config('ai.embeddings.retry_sleep_ms', config('ai.openai.retry_sleep_ms', 200))));
    }

    public function batchSize(): int
    {
        return max(1, min(100, (int) config('ai.embeddings.batch_size', 16)));
    }

    public function similarityThreshold(): float
    {
        $value = (float) config('ai.embeddings.similarity_threshold', 0.15);
        if (! is_finite($value)) {
            return 0.15;
        }

        return max(0.0, min(1.0, $value));
    }

    public function maxCandidates(): int
    {
        return max(1, min(200, (int) config('ai.embeddings.max_candidates', config('ai.retrieval.candidate_limit', 40))));
    }

    public function maxScan(): int
    {
        return max($this->maxCandidates(), min(500, (int) config('ai.embeddings.max_scan', 500)));
    }
}
