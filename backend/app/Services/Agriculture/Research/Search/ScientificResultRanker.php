<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Deterministic relevance ranking — not scientific validity (Stage 4).
 */
class ScientificResultRanker
{
    /**
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function rank(string $searchQuery, array $results): array
    {
        $queryTerms = $this->terms($searchQuery);
        $ranked = [];

        foreach ($results as $result) {
            $score = $this->scoreResult($result, $queryTerms);
            $ranked[] = $result->withRelevanceScore($score, [
                'ranking_basis' => 'query_relevance_only',
                'not_scientific_validation' => true,
            ]);
        }

        usort($ranked, function (ScientificSearchResult $a, ScientificSearchResult $b): int {
            $scoreCompare = ($b->relevanceScore ?? 0.0) <=> ($a->relevanceScore ?? 0.0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp($a->title, $b->title);
        });

        return $ranked;
    }

    /**
     * @param  list<string>  $queryTerms
     */
    private function scoreResult(ScientificSearchResult $result, array $queryTerms): float
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $result->journal,
            implode(' ', $result->authors),
        ])));

        if ($haystack === '') {
            return 0.0;
        }

        $score = 0.0;
        foreach ($queryTerms as $term) {
            if ($term === '') {
                continue;
            }
            if (str_contains($haystack, $term)) {
                $score += mb_strlen($term);
            }
        }

        $normalizedTitle = mb_strtolower(trim($result->title));
        $normalizedQuery = mb_strtolower(trim(implode(' ', $queryTerms)));
        if ($normalizedTitle !== '' && $normalizedQuery !== '' && str_contains($normalizedTitle, $normalizedQuery)) {
            $score += 20.0;
        }

        if ($result->publicationYear !== null && $result->publicationYear >= (int) date('Y') - 10) {
            $score += 1.0;
        }

        if (count($result->foundBySources) > 1) {
            $score += 2.0;
        }

        return $score;
    }

    /** @return list<string> */
    private function terms(string $query): array
    {
        $normalized = mb_strtolower(trim($query));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 3));
    }
}
