<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceRegistry;

/**
 * Deterministic research planner — generic across agriculture domains.
 * Stage 2: query understanding → KnowledgeQueryPlan → legacy AgriculturalResearchPlan.
 */
class ResearchPlanner
{
    /** @var list<string> */
    public const RESEARCH_SEQUENCE = [
        'external_scientific_search',
        'source_validation',
        'evidence_extraction',
        'library_memory_recall',
        'library_enrichment_gap_fill',
        'evidence_comparison_merge',
    ];

    public function __construct(
        private QueryUnderstandingService $queryUnderstanding,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function plan(array $input): AgriculturalResearchPlan
    {
        return $this->planKnowledgeQuery($input)->toAgriculturalResearchPlan();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function planKnowledgeQuery(array $input): KnowledgeQueryPlan
    {
        $understood = $this->queryUnderstanding->understand($input);

        if ($understood->cropId !== null && ($input['selected_crop_id'] ?? '') !== '' && ($input['selected_crop_name'] ?? '') !== '') {
            return $this->buildCropProfileKnowledgePlan($understood, $input);
        }

        return $this->buildGenericKnowledgePlan($understood, $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildCropProfileKnowledgePlan(AgriculturalKnowledgeQuery $query, array $input): KnowledgeQueryPlan
    {
        $knowledgeOption = trim((string) ($input['knowledge_option'] ?? $input['service_option'] ?? 'farming-needs'));
        $sections = CropKnowledgeSectionCatalog::keysFor($knowledgeOption);
        if ($sections === []) {
            $sections = ['overview', 'scientific_evidence', 'recommendations'];
        }

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: $query->agriculturalDomain,
            subjectEntity: $query->subject,
            topics: [$query->researchIntent],
            subtopics: [$knowledgeOption],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: $this->defaultEvidenceTypes(),
            sourcePriorities: $this->defaultSourcePriorities(),
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: $query->ambiguityState,
            clarificationRequirements: $query->clarificationRequirements,
            contextInput: array_merge($input, [
                'selected_crop_id' => $query->cropId,
                'selected_crop_name' => $query->crop,
                'knowledge_option' => $knowledgeOption,
                'research_sections' => $sections,
            ]),
            readyForStage3: $query->researchRequired,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildGenericKnowledgePlan(AgriculturalKnowledgeQuery $query, array $input): KnowledgeQueryPlan
    {
        $topics = [$query->topic];
        $factorTopics = $query->constraints['scientific_topics'] ?? [];
        if (is_array($factorTopics)) {
            foreach ($factorTopics as $factorTopic) {
                $label = trim((string) $factorTopic);
                if ($label !== '' && ! in_array($label, $topics, true)) {
                    $topics[] = $label;
                }
            }
        }
        $subtopics = $query->subtopic !== null ? [$query->subtopic] : [];

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: $query->agriculturalDomain,
            subjectEntity: $query->subject,
            topics: $topics,
            subtopics: $subtopics,
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: $this->defaultEvidenceTypes(),
            sourcePriorities: $this->defaultSourcePriorities(),
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: $query->ambiguityState,
            clarificationRequirements: $query->clarificationRequirements,
            contextInput: $input,
            readyForStage3: $query->researchRequired && $query->ambiguityState !== AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
        );
    }

    /** @return list<string> */
    private function defaultEvidenceTypes(): array
    {
        return [
            'peer_reviewed_publication',
            'official_research',
            'extension_publication',
            'verified_technical_manual',
        ];
    }

    /** @return list<string> */
    private function defaultSourcePriorities(): array
    {
        return [
            'official_agricultural_institutions',
            'government_agricultural_authorities',
            'universities_agricultural_faculties',
            'research_centers',
            'peer_reviewed_scientific_literature',
            'international_agricultural_organizations',
            'scientific_indexes',
            ...ScientificSourceRegistry::approvedSourceTypes(),
        ];
    }
}
