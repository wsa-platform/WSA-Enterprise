<?php

namespace App\Services\Ai\Embeddings;

class UnconfiguredEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(private EmbeddingConfig $config) {}

    public function name(): string
    {
        return $this->config->provider();
    }

    public function model(): string
    {
        return $this->config->model();
    }

    public function dimensions(): int
    {
        return $this->config->dimensions();
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function embed(string $text): EmbeddingResult
    {
        throw new EmbeddingException('The embedding provider is not available.');
    }

    /**
     * @param  list<string>  $texts
     * @return list<EmbeddingResult>
     */
    public function embedMany(array $texts): array
    {
        throw new EmbeddingException('The embedding provider is not available.');
    }
}
