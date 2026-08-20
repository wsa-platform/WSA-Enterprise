<?php

namespace App\Services\Ai\Embeddings;

final class PgvectorLiteral
{
    /**
     * Format a validated dense vector as a pgvector textual literal.
     *
     * @param  list<float>  $vector
     */
    public static function format(array $vector): string
    {
        $parts = [];
        foreach (array_values($vector) as $value) {
            if (! is_numeric($value)) {
                throw new EmbeddingException('The embedding dimension does not match the configured size.');
            }
            $float = (float) $value;
            if (! is_finite($float)) {
                throw new EmbeddingException('The embedding provider returned an invalid vector.');
            }
            $parts[] = sprintf('%.8F', $float);
        }

        if ($parts === []) {
            throw new EmbeddingException('The embedding provider returned an invalid vector.');
        }

        return '['.implode(',', $parts).']';
    }
}
