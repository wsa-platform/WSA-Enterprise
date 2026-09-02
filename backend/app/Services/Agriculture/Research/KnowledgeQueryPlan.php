<?php

namespace App\Services\Agriculture\Research;

/**
 * Structured research plan produced from normalized query understanding.
 * Stage 2 ends here — ready for Stage 3 execution.
 */
final class KnowledgeQueryPlan
{
    public const STRATEGY_INTERNET_FIRST = 'INTERNET_FIRST';

    /** @var list<string> */
    public const STAGE_EXECUTION_SEQUENCE = [
        'external_scientific_research',
        'source_validation',
        'evidence_extraction',
        'library_memory',
        'evidence_merge',
        'synthesis',
    ];

    /** @var list<string> */
    public const LIBRARY_ROLES = [
        'memory',
        'recall',
        'enrichment',
        'comparison',
        'gap_filling',
        'historical_evidence',
    ];

    /**
     * @param  array{type: string, value: string, label?: string}|null  $subjectEntity
     * @param  list<string>  $topics
     * @param  list<string>  $subtopics
     * @param  list<string>  $requestedInformation
     * @param  list<string>  $evidenceRequirements
     * @param  list<string>  $sourcePriorities
     * @param  list<string>  $researchSequence
     * @param  list<string>  $clarificationRequirements
     * @param  array<string, mixed>  $contextInput
     */
    public function __construct(
        public readonly AgriculturalKnowledgeQuery $normalizedQuery,
        public readonly string $researchIntent,
        public readonly string $agriculturalDomain,
        public readonly ?array $subjectEntity,
        public readonly array $topics,
        public readonly array $subtopics,
        public readonly array $requestedInformation,
        public readonly array $evidenceRequirements,
        public readonly array $sourcePriorities,
        public readonly string $primaryResearchStrategy,
        public readonly array $researchSequence,
        public readonly string $ambiguityState,
        public readonly array $clarificationRequirements,
        public readonly array $contextInput = [],
        public readonly bool $readyForStage3 = true,
    ) {}

    public function needsClarification(): bool
    {
        return $this->ambiguityState === AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;
    }

    public function isInternetFirst(): bool
    {
        return $this->primaryResearchStrategy === self::STRATEGY_INTERNET_FIRST;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'normalized_query' => $this->normalizedQuery->toArray(),
            'research_intent' => $this->researchIntent,
            'agricultural_domain' => $this->agriculturalDomain,
            'subject_entity' => $this->subjectEntity,
            'topics' => $this->topics,
            'subtopics' => $this->subtopics,
            'requested_information' => $this->requestedInformation,
            'evidence_requirements' => $this->evidenceRequirements,
            'source_priorities' => $this->sourcePriorities,
            'primary_research_strategy' => $this->primaryResearchStrategy,
            'research_sequence' => $this->researchSequence,
            'library_roles' => self::LIBRARY_ROLES,
            'ambiguity_state' => $this->ambiguityState,
            'clarification_requirements' => $this->clarificationRequirements,
            'ready_for_stage_3' => $this->readyForStage3,
        ];
    }

    public function toAgriculturalResearchPlan(): AgriculturalResearchPlan
    {
        $query = $this->normalizedQuery;
        $entities = [];

        if ($this->subjectEntity !== null) {
            $entities[] = $this->subjectEntity;
        }

        if ($query->scientificName !== null && $query->scientificName !== '') {
            $entities[] = ['type' => 'scientific_name', 'value' => $query->scientificName];
        }

        if ($entities === [] && $query->normalizedQuestion !== '') {
            $entities[] = [
                'type' => 'topic',
                'value' => $query->normalizedQuestion,
                'label' => $query->originalQuestion !== '' ? $query->originalQuestion : $query->normalizedQuestion,
            ];
        }

        $explicitCropProfile = trim((string) ($this->contextInput['selected_crop_id'] ?? '')) !== ''
            && trim((string) ($this->contextInput['selected_crop_name'] ?? '')) !== '';

        $intent = $explicitCropProfile ? 'crop_profile' : 'generic_research';

        $researchSections = match ($intent) {
            'crop_profile' => is_array($this->contextInput['research_sections'] ?? null)
                ? $this->contextInput['research_sections']
                : $this->resolveCropSections($query),
            default => ['overview', 'scientific_evidence', 'practices', 'recommendations'],
        };

        return new AgriculturalResearchPlan(
            userQuery: $query->originalQuestion !== '' ? $query->originalQuestion : $query->normalizedQuestion,
            intent: $intent,
            agriculturalDomain: $this->mapDomainForLegacyEngine($this->agriculturalDomain),
            entities: $entities,
            researchSections: $researchSections,
            requiredEvidenceTypes: $this->evidenceRequirements,
            sourceClasses: $this->sourcePriorities,
            researchSequence: $this->mapSequenceForLegacyEngine($this->researchSequence),
            contextInput: array_merge($this->contextInput, [
                'query' => $query->originalQuestion,
                'knowledge_query_plan' => $this->toArray(),
            ]),
            knowledgeQueryPlan: $this,
        );
    }

    /** @return list<string> */
    private function resolveCropSections(AgriculturalKnowledgeQuery $query): array
    {
        $knowledgeOption = $query->subtopic ?? 'farming-needs';

        return match ($knowledgeOption) {
            'scientific-research' => ['overview', 'scientific_evidence', 'references'],
            'industries' => ['overview', 'industries', 'value_chain'],
            default => ['overview', 'scientific_evidence', 'recommendations'],
        };
    }

    private function mapDomainForLegacyEngine(string $domain): string
    {
        $reverse = array_flip(AgriculturalDomainCatalog::legacyDomainMap());

        return $reverse[$domain] ?? $domain;
    }

    /**
     * @param  list<string>  $sequence
     * @return list<string>
     */
    private function mapSequenceForLegacyEngine(array $sequence): array
    {
        return ResearchPlanner::RESEARCH_SEQUENCE;
    }
}
