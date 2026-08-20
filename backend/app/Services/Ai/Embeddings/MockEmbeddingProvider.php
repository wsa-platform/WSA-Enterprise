<?php

namespace App\Services\Ai\Embeddings;

use App\Services\Ai\Retrieval\KnowledgeTextNormalizer;

class MockEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(
        private EmbeddingConfig $config,
        private KnowledgeTextNormalizer $normalizer,
        private EmbeddingVectorValidator $validator,
    ) {}

    public function name(): string
    {
        return 'mock';
    }

    public function model(): string
    {
        $model = $this->config->model();

        return str_starts_with($model, 'text-embedding-') ? 'mock-hash-v1' : $model;
    }

    public function dimensions(): int
    {
        return $this->config->dimensions();
    }

    public function isAvailable(): bool
    {
        return $this->config->enabled();
    }

    public function embed(string $text): EmbeddingResult
    {
        $started = (int) round(microtime(true) * 1000);
        $vector = $this->validator->assert($this->hashVector($text), $this->dimensions());

        return new EmbeddingResult(
            $vector,
            $this->model(),
            $this->dimensions(),
            max(0, ((int) round(microtime(true) * 1000)) - $started),
        );
    }

    /**
     * @param  list<string>  $texts
     * @return list<EmbeddingResult>
     */
    public function embedMany(array $texts): array
    {
        $results = [];
        foreach (array_values($texts) as $text) {
            $results[] = $this->embed($text);
        }

        return $results;
    }

    /**
     * Deterministic hashed n-gram embedding (feature hashing). This is a real dense vector,
     * not lexical ranking and not random noise.
     *
     * @return list<float>
     */
    private function hashVector(string $text): array
    {
        $dim = $this->dimensions();
        $values = array_fill(0, $dim, 0.0);
        $tokens = $this->normalizer->tokens($text);
        if ($tokens === []) {
            $tokens = $this->fallbackTokens($text);
        }
        foreach ($tokens as $token) {
            $h1 = abs(crc32('v1|'.$token));
            $h2 = abs(crc32('v2|'.$token));
            $sign = ($h1 & 1) === 1 ? 1.0 : -1.0;
            $values[$h1 % $dim] += $sign;
            $values[$h2 % $dim] += 0.5 * $sign;
        }

        return CosineSimilarity::l2Normalize($values);
    }

    /**
     * @return list<string>
     */
    private function fallbackTokens(string $text): array
    {
        $clean = $this->normalizer->searchable($text);
        if ($clean === '') {
            return ['empty-query'];
        }

        return $this->normalizer->tokens($clean.' pad') !== []
            ? $this->normalizer->tokens($clean.' pad')
            : [$clean];
    }
}
