<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Result of one domain verification walk. Never includes credentials or tokens.
 */
final class FaoStatDomainVerificationReport
{
    /**
     * @param  list<string>  $dimensionIds
     * @param  array<string, int>  $codeCounts
     * @param  array<string, mixed>  $live
     * @param  array<string, mixed>  $claimMapping
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $status,
        public readonly string $reason,
        public readonly bool $discovered,
        public readonly bool $metadataVerified,
        public readonly bool $dimensionsVerified,
        public readonly bool $codesVerified,
        public readonly bool $liveQueryVerified,
        public readonly bool $claimMappingVerified,
        public readonly bool $activatable,
        public readonly array $dimensionIds = [],
        public readonly array $codeCounts = [],
        public readonly array $live = [],
        public readonly array $claimMapping = [],
        public readonly array $provenance = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'status' => $this->status,
            'reason' => $this->reason,
            'discovered' => $this->discovered,
            'metadata_verified' => $this->metadataVerified,
            'dimensions_verified' => $this->dimensionsVerified,
            'codes_verified' => $this->codesVerified,
            'live_query_verified' => $this->liveQueryVerified,
            'claim_mapping_verified' => $this->claimMappingVerified,
            'activatable' => $this->activatable,
            'dimension_ids' => $this->dimensionIds,
            'code_counts' => $this->codeCounts,
            'live' => $this->live,
            'claim_mapping' => $this->claimMapping,
            'provenance' => $this->provenance,
        ];
    }
}
