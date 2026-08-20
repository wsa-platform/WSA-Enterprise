<?php

namespace App\Services\Ai\Embeddings;

final class EmbeddingResult
{
    /**
     * @param  list<float>  $vector
     */
    public function __construct(
        public readonly array $vector,
        public readonly string $model,
        public readonly int $dimensions,
        public readonly int $durationMs = 0,
    ) {}
}
