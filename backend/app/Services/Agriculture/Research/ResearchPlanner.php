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
     * Execution topics derived from frozen CSQ. Not a second role graph.
     *
     * @return list<string>
     */
    private function executionTopicsFromCanonical(CanonicalScientificQuestion $csq): array
    {
        $topics = [];
        foreach ([
            $csq->entity->surface,
            $csq->process->surface,
            $csq->target->surface,
            $csq->property->surface,
        ] as $surface) {
            if (is_string($surface) && trim($surface) !== '' && ! in_array(trim($surface), $topics, true)) {
                $topics[] = trim($surface);
            }
        }
        foreach ($csq->relation->operands as $operand) {
            $surface = trim((string) ($operand['surface'] ?? ''));
            if ($surface !== '' && ! in_array($surface, $topics, true)) {
                $topics[] = $surface;
            }
        }
        $intent = trim((string) ($csq->context->researchIntent ?? ''));
        if ($topics === [] && $intent !== '') {
            $topics[] = $intent;
        }
        $sense = trim((string) ($csq->context->scientificSense ?? ''));
        if ($sense === 'land_classification' && ! in_array('land classification', $topics, true)) {
            array_unshift($topics, 'land classification');
        }

        return $topics !== [] ? $topics : ['agriculture'];
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
        $topics = $query->canonicalQuestion !== null
            ? $this->executionTopicsFromCanonical($query->canonicalQuestion)
            : [$query->researchIntent];

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: $query->agriculturalDomain,
            subjectEntity: $query->subject,
            topics: $topics,
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
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        if ($query->canonicalQuestion !== null) {
            $topics = $this->executionTopicsFromCanonical($query->canonicalQuestion);
            $subtopics = $query->subtopic !== null ? [$query->subtopic] : [];
            if ($query->canonicalQuestion->context->scientificSense === 'land_classification'
                && ! in_array('classification_inventory', $subtopics, true)) {
                array_unshift($subtopics, 'classification_inventory');
            }
            $requiredEvidenceType = trim((string) ($query->constraints['required_evidence_type'] ?? ''));
            $evidenceRequirements = $this->defaultEvidenceTypes();
            if ($requiredEvidenceType !== '' && ! in_array($requiredEvidenceType, $evidenceRequirements, true)) {
                array_unshift($evidenceRequirements, $requiredEvidenceType);
            }

            return new KnowledgeQueryPlan(
                normalizedQuery: $query,
                researchIntent: $query->researchIntent,
                agriculturalDomain: $query->agriculturalDomain,
                subjectEntity: $query->subject,
                topics: $topics,
                subtopics: $subtopics,
                requestedInformation: $query->requestedInformation,
                evidenceRequirements: $evidenceRequirements,
                sourcePriorities: $this->defaultSourcePriorities(),
                primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
                researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
                ambiguityState: $query->ambiguityState,
                clarificationRequirements: $query->clarificationRequirements,
                contextInput: $input,
                readyForStage3: $query->researchRequired && $query->ambiguityState !== AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
            );
        }

        $topics = [$query->topic];
        if ($sense === 'land_classification' || $query->researchIntent === 'land_classification') {
            // Lead with land/soil inventory topics — never cultivation-first.
            $topics = ['land classification'];
        }
        $factorTopics = $query->constraints['scientific_topics'] ?? [];
        if (is_array($factorTopics)) {
            foreach ($factorTopics as $factorTopic) {
                $label = trim((string) $factorTopic);
                if ($label !== '' && ! in_array($label, $topics, true)) {
                    $topics[] = $label;
                }
            }
        }
        $environmentalConstraints = $query->constraints['environmental_constraints'] ?? [];
        if (is_array($environmentalConstraints)) {
            foreach (AgriculturalEntityCatalog::constraintQueryTerms($environmentalConstraints) as $constraintTerm) {
                if ($constraintTerm !== '' && ! in_array($constraintTerm, $topics, true)) {
                    $topics[] = $constraintTerm;
                }
            }
        }
        $subtopics = $query->subtopic !== null ? [$query->subtopic] : [];
        if ($sense === 'land_classification' && ! in_array('classification_inventory', $subtopics, true)) {
            array_unshift($subtopics, 'classification_inventory');
        }
        $requiredEvidenceType = trim((string) ($query->constraints['required_evidence_type'] ?? ''));
        $evidenceRequirements = $this->defaultEvidenceTypes();
        if ($requiredEvidenceType !== '' && ! in_array($requiredEvidenceType, $evidenceRequirements, true)) {
            array_unshift($evidenceRequirements, $requiredEvidenceType);
        }

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: $query->agriculturalDomain,
            subjectEntity: $query->subject,
            topics: $topics,
            subtopics: $subtopics,
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: $evidenceRequirements,
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
