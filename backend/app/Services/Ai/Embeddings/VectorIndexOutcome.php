<?php

namespace App\Services\Ai\Embeddings;

final class VectorIndexOutcome
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $fallbackReason = null,
        public readonly int $durationMs = 0,
    ) {}

    public static function indexed(int $durationMs = 0): self
    {
        return new self('indexed', null, $durationMs);
    }

    public static function skipped(): self
    {
        return new self('skipped');
    }

    public static function failed(string $reason = 'semantic_error'): self
    {
        return new self('failed', $reason);
    }

    public static function removed(): self
    {
        return new self('removed');
    }
}
