<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;

/**
 * Top-level agricultural research orchestration layer.
 * Coordinates query understanding, planning, scientific search, validation, library memory, and aggregation.
 */
class AgriculturalResearchAgent
{
    public function __construct(
        private ResearchPlanner $planner,
        private QueryUnderstandingService $queryUnderstanding,
        private AgriculturalScientificSearchService $scientificSearchService,
        private AgriculturalScientificValidationService $scientificValidationService,
        private AnswerComposer $answerComposer,
        private ScientificKnowledgePersistenceService $knowledgePersistenceService,
        private AgriculturalScientificKnowledgeEngine $knowledgeEngine,
    ) {}

    /**
     * Stage 2 planning only — no external search or evidence execution.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function planResearch(array $input): array
    {
        $understood = $this->queryUnderstanding->understand($input);
        $knowledgePlan = $this->planner->planKnowledgeQuery($input);

        return [
            'status' => $knowledgePlan->needsClarification() ? 'needs_clarification' : 'plan_ready',
            'stage' => 2,
            'query_understanding' => $understood->toArray(),
            'knowledge_query_plan' => $knowledgePlan->toArray(),
            'execution' => [
                'performed' => false,
                'reason' => 'stage_2_planning_only',
            ],
        ];
    }

    /**
     * Stage 3 multi-source scientific search — no Stage 4 validation/synthesis.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function searchResearch(array $input): array
    {
        $knowledgePlan = $this->planner->planKnowledgeQuery($input);

        if ($knowledgePlan->needsClarification() && ! filter_var($input['force_execute'] ?? false, FILTER_VALIDATE_BOOL)) {
            return [
                'status' => 'needs_clarification',
                'stage' => 2,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'scientific_search' => [
                    'performed' => false,
                    'reason' => 'ambiguous_query_requires_clarification',
                ],
            ];
        }

        $report = $this->scientificSearchService->search($knowledgePlan);

        return array_merge($report->toArray(), [
            'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
            'knowledge_query_plan' => $knowledgePlan->toArray(),
        ]);
    }

    /**
     * Stage 4 scientific validation — no Stage 5 synthesis or library persistence.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateResearch(array $input): array
    {
        $knowledgePlan = $this->planner->planKnowledgeQuery($input);

        if ($knowledgePlan->needsClarification() && ! filter_var($input['force_execute'] ?? false, FILTER_VALIDATE_BOOL)) {
            return [
                'status' => 'needs_clarification',
                'stage' => 2,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'validation' => [
                    'performed' => false,
                    'reason' => 'ambiguous_query_requires_clarification',
                ],
            ];
        }

        $searchReport = $this->scientificSearchService->search(
            $knowledgePlan,
            (int) ($input['limit'] ?? 10),
        );

        $validationReport = $this->scientificValidationService->validate($knowledgePlan, $searchReport);

        return array_merge($validationReport->toArray(), [
            'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
            'knowledge_query_plan' => $knowledgePlan->toArray(),
            'scientific_search' => $searchReport->toArray(),
            'internet_first' => $searchReport->internetFirst,
        ]);
    }

    /**
     * Stage 5 synthesis + verified knowledge persistence — full pipeline through Stage 4.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function synthesizeResearch(int $organizationId, array $input): array
    {
        $knowledgePlan = $this->planner->planKnowledgeQuery($input);

        if ($knowledgePlan->needsClarification() && ! filter_var($input['force_execute'] ?? false, FILTER_VALIDATE_BOOL)) {
            return [
                'status' => 'needs_clarification',
                'stage' => 2,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'synthesis' => [
                    'performed' => false,
                    'reason' => 'ambiguous_query_requires_clarification',
                ],
                'library_persistence' => [
                    'performed' => false,
                    'reason' => 'ambiguous_query_requires_clarification',
                ],
            ];
        }

        $searchReport = $this->scientificSearchService->search(
            $knowledgePlan,
            (int) ($input['limit'] ?? 10),
        );
        $validationReport = $this->scientificValidationService->validate($knowledgePlan, $searchReport);
        $synthesisReport = $this->answerComposer->compose($knowledgePlan, $validationReport);
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $validationReport,
        );

        return array_merge(
            $synthesisReport->toArray(),
            $persistenceReport->toArray(),
            [
                'status' => $synthesisReport->status,
                'persistence_status' => $persistenceReport->status,
                'observability' => array_merge(
                    $synthesisReport->observability,
                    $persistenceReport->observability,
                ),
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'scientific_search' => $searchReport->toArray(),
                'scientific_validation' => $validationReport->toArray(),
                'validated_evidence' => array_map(
                    static fn ($item): array => $item->toArray(),
                    $validationReport->validatedEvidence,
                ),
                'rejected_evidence' => array_map(
                    static fn ($item): array => $item->toArray(),
                    $validationReport->rejectedEvidence,
                ),
                'internet_first' => $searchReport->internetFirst,
            ],
        );
    }

    /**
     * Conduct generic agricultural research.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function conductResearch(int $organizationId, array $input): array
    {
        $knowledgePlan = $this->planner->planKnowledgeQuery($input);

        if ($knowledgePlan->needsClarification() && ! filter_var($input['force_execute'] ?? false, FILTER_VALIDATE_BOOL)) {
            return [
                'status' => 'needs_clarification',
                'stage' => 2,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'execution' => [
                    'performed' => false,
                    'reason' => 'ambiguous_query_requires_clarification',
                ],
            ];
        }

        $scientificSearch = $this->scientificSearchService->search($knowledgePlan);
        $scientificValidation = $this->scientificValidationService->validate($knowledgePlan, $scientificSearch);
        $synthesisReport = $this->answerComposer->compose($knowledgePlan, $scientificValidation);
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $scientificValidation,
        );

        $plan = $knowledgePlan->toAgriculturalResearchPlan();
        $result = $this->knowledgeEngine->execute($organizationId, $plan);

        if ($plan->isCropProfileIntent()) {
            $legacy = $result->toLegacyProfileResponse();
            $legacy['research_agent'] = [
                'orchestrated' => true,
                'stage' => 5,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'plan' => $plan->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'scientific_search' => $scientificSearch->toArray(),
                'scientific_validation' => $scientificValidation->toArray(),
                'synthesis' => $synthesisReport->toArray(),
                'library_persistence' => $persistenceReport->toArray(),
                'discovery' => [
                    'discoverers_used' => $result->discoverersUsed,
                    'external_discoverers_used' => $result->externalDiscoverersUsed,
                    'library_discoverers_used' => $result->libraryDiscoverersUsed,
                    'internet_first' => $result->toAgentResponse()['discovery']['internet_first'],
                ],
            ];

            return $legacy;
        }

        $response = $result->toAgentResponse();
        $response['stage'] = 5;
        $response['query_understanding'] = $knowledgePlan->normalizedQuery->toArray();
        $response['knowledge_query_plan'] = $knowledgePlan->toArray();
        $response['scientific_search'] = $scientificSearch->toArray();
        $response['scientific_validation'] = $scientificValidation->toArray();

        return array_merge($response, $synthesisReport->toArray(), $persistenceReport->toArray(), [
            'status' => $response['status'] ?? $synthesisReport->status,
            'persistence_status' => $persistenceReport->status,
            'observability' => array_merge(
                $synthesisReport->observability,
                $persistenceReport->observability,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $cropContextInput
     * @return array<string, mixed>
     */
    public function conductCropProfileResearch(int $organizationId, array $cropContextInput): array
    {
        return $this->conductResearch($organizationId, $cropContextInput);
    }
}
