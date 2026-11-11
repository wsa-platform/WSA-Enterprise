<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;

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
     * @param  list<ScientificSourceRecordProvenance>  $provenance  one record per contributing source record
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
        public readonly array $provenance = [],
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
            'provenance' => array_map(
                static fn (ScientificSourceRecordProvenance $record): array => $record->toArray(),
                $this->provenance,
            ),
        ];
    }

    /** The record of the source this result is keyed by, if one was recorded. */
    public function primaryProvenance(): ?ScientificSourceRecordProvenance
    {
        foreach ($this->provenance as $record) {
            if ($record->sourceKey === $this->sourceKey) {
                return $record;
            }
        }

        return null;
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
            provenance: $this->provenance,
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
            provenance: $this->provenance,
        );
    }

    /**
     * Stamps the retrieval of $sourceKey's records; a result without one gains a record whose
     * provider fields stay unknown.
     */
    public function withRetrievalContext(
        string $sourceKey,
        string $retrievedAt,
        string $adapterClass,
        ?string $contractVersion,
        ?string $accessFamily,
        ScientificSourceLicensingDeclaration $licensing,
    ): self {
        $records = [];
        $stamped = false;
        foreach ($this->provenance as $record) {
            if ($record->sourceKey === $sourceKey) {
                $record = $record->withRetrievalContext($retrievedAt, $adapterClass, $contractVersion, $accessFamily, $licensing);
                $stamped = true;
            }
            $records[] = $record;
        }
        if (! $stamped) {
            $records[] = (new ScientificSourceRecordProvenance($sourceKey))
                ->withRetrievalContext($retrievedAt, $adapterClass, $contractVersion, $accessFamily, $licensing);
        }

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
            relevanceMetadata: $this->relevanceMetadata,
            rawMetadata: $this->rawMetadata,
            relevanceScore: $this->relevanceScore,
            provenance: $records,
        );
    }
}
