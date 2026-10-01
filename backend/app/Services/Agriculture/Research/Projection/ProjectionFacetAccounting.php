<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Facet accounting buckets for Projection envelope (no silent drop).
 */
final readonly class ProjectionFacetAccounting
{
    /**
     * @param  list<ProjectionFacetRecord>  $records
     */
    private function __construct(
        public array $records,
    ) {}

    /**
     * @param  list<ProjectionFacetRecord>  $records
     */
    public static function of(array $records): self
    {
        foreach ($records as $record) {
            if (! $record instanceof ProjectionFacetRecord) {
                throw new ProjectionInvariantViolation('Facet accounting requires ProjectionFacetRecord entries.');
            }
        }

        return new self(array_values($records));
    }

    /**
     * @return list<ProjectionFacetRecord>
     */
    public function byDisposition(ProjectionFacetDisposition $disposition): array
    {
        return array_values(array_filter(
            $this->records,
            static fn (ProjectionFacetRecord $r) => $r->disposition === $disposition,
        ));
    }

    /**
     * @return list<string>
     */
    public function facetCodes(): array
    {
        return array_map(static fn (ProjectionFacetRecord $r) => $r->facetCode, $this->records);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function toBucketArrays(): array
    {
        $buckets = [
            'mapped_facets' => [],
            'expanded_terms' => [],
            'unsupported_facets' => [],
            'omitted_facets' => [],
            'unresolved_facets' => [],
            'compressed_facets' => [],
            'approximated_facets' => [],
        ];

        foreach ($this->records as $record) {
            $payload = $record->toArray();
            match ($record->disposition) {
                ProjectionFacetDisposition::MAPPED => $buckets['mapped_facets'][] = $payload,
                ProjectionFacetDisposition::EXPANDED => $buckets['expanded_terms'][] = $payload,
                ProjectionFacetDisposition::UNSUPPORTED => $buckets['unsupported_facets'][] = $payload,
                ProjectionFacetDisposition::OMITTED => $buckets['omitted_facets'][] = $payload,
                ProjectionFacetDisposition::UNRESOLVED => $buckets['unresolved_facets'][] = $payload,
                ProjectionFacetDisposition::COMPRESSED => $buckets['compressed_facets'][] = $payload,
                ProjectionFacetDisposition::APPROXIMATED => $buckets['approximated_facets'][] = $payload,
            };
        }

        return $buckets;
    }
}
