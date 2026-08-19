<?php

namespace App\Services\Ai\Retrieval;

/**
 * Deterministic lexical similarity used as the AI-10 semantic foundation.
 * This is not neural embedding quality; it is a replaceable approximation.
 */
class DeterministicLexicalSemanticScorer
{
    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    public function score(string $query, AiKnowledgeDocument $document): float
    {
        if (! $document->visible) {
            return 0.0;
        }

        $normalizedQuery = $this->normalizer->searchable($query);
        $tokens = $this->normalizer->tokens($query);
        if ($normalizedQuery === '' || $tokens === []) {
            return 0.0;
        }

        $title = $this->normalizer->searchable($document->title);
        $summary = $this->normalizer->searchable($document->summary);
        $body = $this->normalizer->searchable($document->body);
        $score = 0.0;

        if ($title === $normalizedQuery) {
            $score += 100.0;
        }
        if ($normalizedQuery !== '' && mb_strlen($normalizedQuery) >= 8 && mb_strpos($title, $normalizedQuery) !== false) {
            $score += 30.0;
        }

        foreach ($tokens as $token) {
            if (mb_strpos($title, $token) !== false) {
                $score += 8.0;
            }
            if (mb_strpos($summary, $token) !== false) {
                $score += 4.0;
            }
            if (mb_strpos($body, $token) !== false) {
                $score += 1.0;
            }
        }

        $documentTokens = $this->normalizer->tokens($document->searchableText);
        $overlap = count(array_intersect($tokens, $documentTokens));
        $union = count(array_unique(array_merge($tokens, $documentTokens))) ?: 1;
        $score += ($overlap / $union) * 20.0;

        if ($document->sourceType === 'library_items') {
            $score += 0.25;
        }

        return round(min(100.0, $score), 4);
    }
}
