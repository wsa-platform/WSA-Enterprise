<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Evidence supporting a candidate diagnosis (explainable, non-fabricated).
 */
final class DiagnosisEvidence
{
    /**
     * @param  list<string>  $observationIds
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $summary,
        public readonly array $observationIds = [],
        public readonly ?string $sourceLabel = null,
        public readonly bool $fromKnowledgeSupport = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'summary' => $this->summary,
            'observation_ids' => $this->observationIds,
            'source_label' => $this->sourceLabel,
            'from_knowledge_support' => $this->fromKnowledgeSupport,
        ];
    }
}
