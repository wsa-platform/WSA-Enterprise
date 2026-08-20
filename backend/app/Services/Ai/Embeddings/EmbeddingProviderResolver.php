<?php

namespace App\Services\Ai\Embeddings;

class EmbeddingProviderResolver
{
    public function __construct(private EmbeddingConfig $config) {}

    public function resolve(): EmbeddingProviderInterface
    {
        if ($this->config->configuredProviderIsInvalid()) {
            return app(UnconfiguredEmbeddingProvider::class);
        }

        return match ($this->config->provider()) {
            'openai' => app(OpenAiEmbeddingProvider::class),
            default => app(MockEmbeddingProvider::class),
        };
    }
}
