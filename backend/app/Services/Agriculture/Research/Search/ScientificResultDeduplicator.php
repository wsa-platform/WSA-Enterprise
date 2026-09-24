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
        $observation = ScientificStructuredObservation::fromResult($result);
        if (ScientificEvidenceModality::isDirectStatistical($result)) {
            if ($observation !== null && $observation->isComplete()) {
                return 'stat:'.ScientificEvidenceModality::DIRECT_STATISTICAL.':'.$observation->identityKey();
            }
            if ($result->sourceIdentifier !== null && $result->sourceIdentifier !== '') {
                return 'id:'.$result->sourceKey.':'.strtolower($result->sourceIdentifier);
            }

            // Statistical rows must not collapse on a shared query URL.
            return null;
        }

        if ($result->doi !== null && $result->doi !== '') {
            return 'doi:'.strtolower(trim($result->doi));
        }

        if ($result->canonicalUrl !== null && $result->canonicalUrl !== '') {
            return 'url:'.strtolower(rtrim($result->canonicalUrl, '/'));
        }

        if ($result->sourceIdentifier !== null && $result->sourceIdentifier !== '') {
            return 'id:'.$result->sourceKey.':'.strtolower($result->sourceIdentifier);
        }

        // Title+year is not a unique scholarly identity.
        return null;
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
            relevanceMetadata: $this->mergeAssociative(
                $left->relevanceMetadata,
                $right->relevanceMetadata,
                $mergedSources,
            ),
            rawMetadata: $this->mergeAssociative(
                $left->rawMetadata,
                $right->rawMetadata,
                $mergedSources,
            ),
            relevanceScore: max($left->relevanceScore ?? 0.0, $right->relevanceScore ?? 0.0) ?: null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $left
     * @param  array<string, mixed>|null  $right
     * @param  list<string>  $foundBySources
     * @return array<string, mixed>|null
     */
    private function mergeAssociative(?array $left, ?array $right, array $foundBySources): ?array
    {
        if ($left === null && $right === null) {
            return ['provenance' => ['found_by_sources' => $foundBySources]];
        }
        if ($left === null) {
            $right['provenance'] = array_merge(
                is_array($right['provenance'] ?? null) ? $right['provenance'] : [],
                ['found_by_sources' => $foundBySources],
            );

            return $right;
        }
        if ($right === null) {
            $left['provenance'] = array_merge(
                is_array($left['provenance'] ?? null) ? $left['provenance'] : [],
                ['found_by_sources' => $foundBySources],
            );

            return $left;
        }

        $merged = $left;
        foreach ($right as $key => $value) {
            if (! array_key_exists($key, $merged) || $merged[$key] === null || $merged[$key] === '') {
                $merged[$key] = $value;
            }
        }
        $merged['provenance'] = array_merge(
            is_array($left['provenance'] ?? null) ? $left['provenance'] : [],
            is_array($right['provenance'] ?? null) ? $right['provenance'] : [],
            ['found_by_sources' => $foundBySources],
        );

        return $merged;
    }

    private function normalizeTitle(string $title): string
    {
        $normalized = mb_strtolower(trim($title));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
