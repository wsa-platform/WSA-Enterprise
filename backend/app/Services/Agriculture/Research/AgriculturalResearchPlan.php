<?php

namespace App\Services\Agriculture\Research;

/**
 * Deterministic v1 research plan produced by ResearchPlanner.
 */
final class AgriculturalResearchPlan
{
    /**
     * @param  list<array{type: string, value: string, label?: string}>  $entities
     * @param  list<string>  $researchSections
     * @param  list<string>  $requiredEvidenceTypes
     * @param  list<string>  $sourceClasses
     * @param  list<string>  $researchSequence
     * @param  array<string, mixed>  $contextInput
     */
    public function __construct(
        public readonly string $userQuery,
        public readonly string $intent,
        public readonly string $agriculturalDomain,
        public readonly array $entities,
        public readonly array $researchSections,
        public readonly array $requiredEvidenceTypes,
        public readonly array $sourceClasses,
        public readonly array $researchSequence,
        public readonly array $contextInput = [],
    ) {}

    public function isCropProfileIntent(): bool
    {
        return $this->intent === 'crop_profile';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user_query' => $this->userQuery,
            'intent' => $this->intent,
            'agricultural_domain' => $this->agriculturalDomain,
            'entities' => $this->entities,
            'research_sections' => $this->researchSections,
            'required_evidence_types' => $this->requiredEvidenceTypes,
            'source_classes' => $this->sourceClasses,
            'research_sequence' => $this->researchSequence,
        ];
    }
}
