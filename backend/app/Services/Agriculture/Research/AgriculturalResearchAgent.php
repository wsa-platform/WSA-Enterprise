<?php

namespace App\Services\Agriculture\Research;

/**
 * Top-level agricultural research orchestration layer.
 * Coordinates planning, scientific discovery, validation, library memory, and aggregation.
 */
class AgriculturalResearchAgent
{
    public function __construct(
        private ResearchPlanner $planner,
        private AgriculturalScientificKnowledgeEngine $knowledgeEngine,
    ) {}

    /**
     * Conduct generic agricultural research.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function conductResearch(int $organizationId, array $input): array
    {
        $plan = $this->planner->plan($input);
        $result = $this->knowledgeEngine->execute($organizationId, $plan);

        if ($plan->isCropProfileIntent()) {
            $legacy = $result->toLegacyProfileResponse();
            $legacy['research_agent'] = [
                'orchestrated' => true,
                'plan' => $plan->toArray(),
                'discovery' => [
                    'discoverers_used' => $result->discoverersUsed,
                    'external_discoverers_used' => $result->externalDiscoverersUsed,
                    'library_discoverers_used' => $result->libraryDiscoverersUsed,
                    'internet_first' => $result->toAgentResponse()['discovery']['internet_first'],
                ],
            ];

            return $legacy;
        }

        return $result->toAgentResponse();
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
