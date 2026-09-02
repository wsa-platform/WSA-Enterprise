<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Canonical normalized scientific search result (Stage 3).
 * Optional fields remain null when unavailable — never fabricated.
 */
final class ScientificSearchResult
{
    /**
     * @param  list<string>  $authors
     * @param  list<string>  $foundBySources
     * @param  array<string, mixed>|null  $relevanceMetadata
     * @param  array<string, mixed>|null  $rawMetadata
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly ?string $sourceIdentifier,
        public readonly string $title,
        public readonly array $authors,
        public readonly ?int $publicationYear,
        public readonly ?string $doi,
        public readonly ?string $canonicalUrl,
        public readonly ?string $abstract,
        public readonly ?string $journal,
        public readonly array $foundBySources,
        public readonly ?array $relevanceMetadata = null,
        public readonly ?array $rawMetadata = null,
        public readonly ?float $relevanceScore = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->sourceKey,
            'source_identifier' => $this->sourceIdentifier,
            'title' => $this->title,
            'authors' => $this->authors,
            'publication_year' => $this->publicationYear,
            'doi' => $this->doi,
            'canonical_url' => $this->canonicalUrl,
            'abstract' => $this->abstract,
            'journal' => $this->journal,
            'found_by_sources' => $this->foundBySources,
            'relevance_metadata' => $this->relevanceMetadata,
            'relevance_score' => $this->relevanceScore,
        ];
    }

    /**
     * @param  list<string>  $additionalSources
     */
    public function withAdditionalSources(array $additionalSources): self
    {
        return new self(
            sourceKey: $this->sourceKey,
            sourceIdentifier: $this->sourceIdentifier,
            title: $this->title,
            authors: $this->authors,
            publicationYear: $this->publicationYear,
            doi: $this->doi,
            canonicalUrl: $this->canonicalUrl,
            abstract: $this->abstract,
            journal: $this->journal,
            foundBySources: array_values(array_unique(array_merge($this->foundBySources, $additionalSources))),
            relevanceMetadata: $this->relevanceMetadata,
            rawMetadata: $this->rawMetadata,
            relevanceScore: $this->relevanceScore,
        );
    }

    public function withRelevanceScore(float $score, array $metadata = []): self
    {
        return new self(
            sourceKey: $this->sourceKey,
            sourceIdentifier: $this->sourceIdentifier,
            title: $this->title,
            authors: $this->authors,
            publicationYear: $this->publicationYear,
            doi: $this->doi,
            canonicalUrl: $this->canonicalUrl,
            abstract: $this->abstract,
            journal: $this->journal,
            foundBySources: $this->foundBySources,
            relevanceMetadata: array_merge($this->relevanceMetadata ?? [], $metadata),
            rawMetadata: $this->rawMetadata,
            relevanceScore: $score,
        );
    }
}
