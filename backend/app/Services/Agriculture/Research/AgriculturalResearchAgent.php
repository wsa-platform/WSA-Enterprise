<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;

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

        $plan = $knowledgePlan->toAgriculturalResearchPlan();
        $result = $this->knowledgeEngine->execute($organizationId, $plan);

        if ($plan->isCropProfileIntent()) {
            $legacy = $result->toLegacyProfileResponse();
            $legacy['research_agent'] = [
                'orchestrated' => true,
                'stage' => 3,
                'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
                'plan' => $plan->toArray(),
                'knowledge_query_plan' => $knowledgePlan->toArray(),
                'scientific_search' => $scientificSearch->toArray(),
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
        $response['stage'] = 3;
        $response['query_understanding'] = $knowledgePlan->normalizedQuery->toArray();
        $response['knowledge_query_plan'] = $knowledgePlan->toArray();
        $response['scientific_search'] = $scientificSearch->toArray();

        return $response;
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
