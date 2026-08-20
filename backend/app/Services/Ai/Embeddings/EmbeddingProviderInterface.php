<?php

namespace App\Services\Ai\Embeddings;

interface EmbeddingProviderInterface
{
    public function name(): string;

    public function model(): string;

    public function dimensions(): int;

    public function isAvailable(): bool;

    public function embed(string $text): EmbeddingResult;

    /**
     * @param  list<string>  $texts
     * @return list<EmbeddingResult>
     */
    public function embedMany(array $texts): array;
}
