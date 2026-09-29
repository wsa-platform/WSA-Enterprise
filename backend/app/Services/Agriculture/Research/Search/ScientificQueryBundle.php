<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Compiled source-specific query representation.
 *
 * Holds projection of CSQ meaning for one source. Contains no evidence,
 * answers, confidence, or ranking.
 */
final readonly class ScientificQueryBundle
{
    /**
     * @param  list<string>  $variants
     * @param  array<string, mixed>  $structuredFilters
     * @param  array<string, mixed>  $scientificIdentity
     */
    public function __construct(
        public string $sourceKey,
        public string $sourceQuery,
        public array $variants,
        public array $structuredFilters,
        public array $scientificIdentity,
        public bool $representable,
        public ?string $skipReason = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'source_query' => $this->sourceQuery,
            'variants' => $this->variants,
            'structured_filters' => $this->structuredFilters,
            'scientific_identity' => $this->scientificIdentity,
            'representable' => $this->representable,
            'skip_reason' => $this->skipReason,
        ];
    }
}
