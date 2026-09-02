<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Deterministic deduplication across multi-source scientific results.
 */
class ScientificResultDeduplicator
{
    /**
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function deduplicate(array $results): array
    {
        $merged = [];

        foreach ($results as $result) {
            $key = $this->dedupeKey($result);
            if ($key === null) {
                $merged[] = $result;

                continue;
            }

            if (! isset($merged[$key])) {
                $merged[$key] = $result;

                continue;
            }

            $existing = $merged[$key];
            $merged[$key] = $this->mergeResults($existing, $result);
        }

        return array_values($merged);
    }

    private function dedupeKey(ScientificSearchResult $result): ?string
    {
        if ($result->doi !== null && $result->doi !== '') {
            return 'doi:'.$result->doi;
        }

        if ($result->canonicalUrl !== null && $result->canonicalUrl !== '') {
            return 'url:'.strtolower(rtrim($result->canonicalUrl, '/'));
        }

        if ($result->sourceIdentifier !== null && $result->sourceIdentifier !== '') {
            return 'id:'.$result->sourceKey.':'.strtolower($result->sourceIdentifier);
        }

        $title = $this->normalizeTitle($result->title);
        if ($title === '') {
            return null;
        }

        $year = $result->publicationYear ?? 'unknown';

        return 'title:'.$title.':'.$year;
    }

    private function mergeResults(ScientificSearchResult $left, ScientificSearchResult $right): ScientificSearchResult
    {
        $mergedSources = array_values(array_unique(array_merge($left->foundBySources, $right->foundBySources)));

        return new ScientificSearchResult(
            sourceKey: $left->sourceKey,
            sourceIdentifier: $left->sourceIdentifier ?? $right->sourceIdentifier,
            title: $left->title,
            authors: $left->authors !== [] ? $left->authors : $right->authors,
            publicationYear: $left->publicationYear ?? $right->publicationYear,
            doi: $left->doi ?? $right->doi,
            canonicalUrl: $left->canonicalUrl ?? $right->canonicalUrl,
            abstract: $left->abstract ?? $right->abstract,
            journal: $left->journal ?? $right->journal,
            foundBySources: $mergedSources,
            relevanceMetadata: $left->relevanceMetadata ?? $right->relevanceMetadata,
            rawMetadata: $left->rawMetadata ?? $right->rawMetadata,
            relevanceScore: max($left->relevanceScore ?? 0.0, $right->relevanceScore ?? 0.0) ?: null,
        );
    }

    private function normalizeTitle(string $title): string
    {
        $normalized = mb_strtolower(trim($title));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
