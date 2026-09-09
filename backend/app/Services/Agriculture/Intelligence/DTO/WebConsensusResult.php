<?php

namespace App\Services\Agriculture\Intelligence\DTO;

final class WebConsensusResult
{
    /**
     * @param  list<array<string, mixed>>  $values
     * @param  list<array<string, mixed>>  $conflicts
     * @param  list<string>  $sources
     * @param  array<string, mixed>  $quality
     */
    public function __construct(
        public readonly bool $hasConsensus,
        public readonly mixed $representativeValue = null,
        public readonly ?float $rangeMin = null,
        public readonly ?float $rangeMax = null,
        public readonly float $agreementScore = 0.0,
        public readonly array $values = [],
        public readonly array $conflicts = [],
        public readonly array $sources = [],
        public readonly array $quality = [],
        public readonly string $status = 'empty',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'has_consensus' => $this->hasConsensus,
            'representative_value' => $this->representativeValue,
            'range_min' => $this->rangeMin,
            'range_max' => $this->rangeMax,
            'agreement_score' => $this->agreementScore,
            'values' => $this->values,
            'conflicts' => $this->conflicts,
            'sources' => $this->sources,
            'quality' => $this->quality,
            'status' => $this->status,
        ];
    }
}
