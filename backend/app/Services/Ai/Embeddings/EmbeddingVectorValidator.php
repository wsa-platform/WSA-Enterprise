<?php

namespace App\Services\Ai\Embeddings;

class EmbeddingVectorValidator
{
    /**
     * @return list<float>
     */
    public function assert(mixed $vector, int $expectedDimensions): array
    {
        if (! is_array($vector) || $vector === []) {
            throw new EmbeddingException('The embedding provider returned an invalid vector.');
        }

        $values = array_values($vector);
        if (count($values) !== $expectedDimensions) {
            throw new EmbeddingException('The embedding dimension does not match the configured size.');
        }

        $normalized = [];
        $hasMagnitude = false;
        foreach ($values as $value) {
            if (! is_numeric($value)) {
                throw new EmbeddingException('The embedding provider returned an invalid vector.');
            }
            $float = (float) $value;
            if (! is_finite($float)) {
                throw new EmbeddingException('The embedding provider returned an invalid vector.');
            }
            if (abs($float) > 0.0) {
                $hasMagnitude = true;
            }
            $normalized[] = $float;
        }

        if (! $hasMagnitude) {
            throw new EmbeddingException('The embedding provider returned an invalid vector.');
        }

        return $normalized;
    }
}
