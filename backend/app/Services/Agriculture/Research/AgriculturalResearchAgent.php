<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropProfileIdentityValidator;
use App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\Persistence\KnowledgePersistenceExecutionReport;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use Illuminate\Validation\ValidationException;

/**
 * Top-level agricultural research orchestration layer.
 * Coordinates query understanding, planning, scientific search, validation, library memory, and aggregation.
 * When UNIVERSAL_ANSWER_ORCHESTRATOR_ENABLED, synthesizes via UniversalAnswerOrchestrator (ADR-002).
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
        private HomeEvidenceLifecycleDisposition $homeEvidenceLifecycleDisposition = new HomeEvidenceLifecycleDisposition,
        private ?UniversalAnswerOrchestrator $universalAnswerOrchestrator = null,
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
        $synthesisReport = $this->applyHomeEvidenceLifecycleDisposition(
            $knowledgePlan,
            $validationReport,
            $synthesisReport,
        );
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $validationReport,
        );

        $payload = array_merge(
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

        return $this->maybeEnrichWithUniversalOrchestrator($payload, $input);
    }

    /**
     * Conduct generic agricultural research.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function conductResearch(int $organizationId, array $input): array
    {
        // Phase 6 U6.3: incomplete Crop selectors must not silently fall through to Home/generic.
        if (CropProfileIdentityValidator::isIncompleteCropSelector($input)) {
            return [
                'status' => 'needs_clarification',
                'stage' => 2,
                'query_understanding' => null,
                'knowledge_query_plan' => null,
                'execution' => [
                    'performed' => false,
                    'reason' => 'incomplete_crop_selector',
                ],
                'clarification_requirements' => ['selected_crop_id', 'selected_crop_name'],
                'error' => [
                    'code' => 'incomplete_crop_selector',
                    'http_status' => 422,
                    'message' => 'Crop profile requires both selected_crop_id and selected_crop_name.',
                    'details' => null,
                ],
            ];
        }

        $cropId = trim((string) ($input['selected_crop_id'] ?? ''));
        $cropName = trim((string) ($input['selected_crop_name'] ?? ''));
        if ($cropId !== '' && $cropName !== '') {
            try {
                $identity = CropProfileIdentityValidator::normalizePair($input);
                $input['selected_crop_id'] = $identity['selected_crop_id'];
                $input['selected_crop_name'] = $identity['selected_crop_name'];
                $input['scientific_name'] = $identity['scientific_name'];
            } catch (ValidationException $exception) {
                return [
                    'status' => 'invalid_crop_identity',
                    'stage' => 2,
                    'execution' => [
                        'performed' => false,
                        'reason' => 'invalid_crop_identity',
                    ],
                    'error' => [
                        'code' => 'invalid_crop_identity',
                        'http_status' => 422,
                        'message' => 'Crop identity failed authoritative taxonomy validation.',
                        'details' => $exception->errors(),
                    ],
                ];
            }
        }

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
        $synthesisReport = $this->applyHomeEvidenceLifecycleDisposition(
            $knowledgePlan,
            $scientificValidation,
            $synthesisReport,
        );
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $scientificValidation,
        );

        $plan = $knowledgePlan->toAgriculturalResearchPlan();

        if (! $this->shouldRunLegacyPostProcessing($plan, $synthesisReport)) {
            if ($plan->isCropProfileIntent()) {
                $legacy = $this->cropCompatibilityEnvelope($plan, $persistenceReport);
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
                        'discoverers_used' => [],
                        'external_discoverers_used' => [],
                        'library_discoverers_used' => [],
                        'internet_first' => $knowledgePlan->isInternetFirst(),
                    ],
                ];

                return CropCanonicalStage5Response::dualEmit($legacy, $synthesisReport);
            }

            return $this->stage5ResponseWithoutBlockingPostProcessing(
                $knowledgePlan,
                $plan,
                $scientificSearch,
                $scientificValidation,
                $synthesisReport,
                $persistenceReport,
            );
        }

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

            // Phase 7 P7-U1 / STRUCT-03: Stage 5 is the canonical scientific Crop answer
            // at root; legacy sections/load_state/library remain compatibility siblings.
            return CropCanonicalStage5Response::dualEmit($legacy, $synthesisReport);
        }

        $response = $result->toAgentResponse();
        $response['stage'] = 5;
        $response['query_understanding'] = $knowledgePlan->normalizedQuery->toArray();
        $response['knowledge_query_plan'] = $knowledgePlan->toArray();
        $response['scientific_search'] = $scientificSearch->toArray();
        $response['scientific_validation'] = $scientificValidation->toArray();

        $merged = array_merge($response, $synthesisReport->toArray(), $persistenceReport->toArray(), [
            'status' => $response['status'] ?? $synthesisReport->status,
            'persistence_status' => $persistenceReport->status,
            'observability' => array_merge(
                $synthesisReport->observability,
                $persistenceReport->observability,
            ),
        ]);

        return $this->maybeEnrichWithUniversalOrchestrator($merged, $input);
    }

    /**
     * @param  array<string, mixed>  $cropContextInput
     * @return array<string, mixed>
     */
    public function conductCropProfileResearch(int $organizationId, array $cropContextInput): array
    {
        return $this->conductResearch($organizationId, $cropContextInput);
    }

    /**
     * Full ADR-002 multi-source answer path (feature-flagged).
     * Gated by UNIVERSAL_ANSWER_ORCHESTRATOR_ENABLED / agricultural_intelligence.orchestrator_enabled.
     * HTTP surface remains legacy synthesize/query enrichment (no dedicated route).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function answerUniversal(array $input): array
    {
        if (! $this->isUniversalOrchestratorEnabled()) {
            return [
                'status' => 'disabled',
                'answer' => null,
                'concise_summary' => null,
                'answer_status' => 'INSUFFICIENT',
                'web_answer_eligible' => false,
                'scientific_answer_eligible' => false,
                'overall_answer_eligible' => false,
                'providers_used' => [],
                'limitations' => ['universal_orchestrator_disabled'],
                'citations' => [],
                'universal_orchestrator' => [
                    'enabled' => false,
                    'version' => '1.0.0',
                ],
            ];
        }

        $orchestrator = $this->universalAnswerOrchestrator ?? app(UniversalAnswerOrchestrator::class);

        return $orchestrator->answer($input)->toArray();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function maybeEnrichWithUniversalOrchestrator(array $payload, array $input): array
    {
        if ($this->payloadHasSufficientScientificResult($payload)) {
            return $payload;
        }

        if (! $this->isUniversalOrchestratorEnabled()) {
            return $payload;
        }

        if (! filter_var(config('agricultural_intelligence.enrich_legacy_synthesis', true), FILTER_VALIDATE_BOOL)) {
            return $payload;
        }

        try {
            $orchestrator = $this->universalAnswerOrchestrator ?? app(UniversalAnswerOrchestrator::class);

            return $orchestrator->enrichLegacySynthesis($payload, $input);
        } catch (\Throwable) {
            // Backward-compatible: enrichment failures must not break existing consumers.
            return $payload;
        }
    }

    /**
     * Home and Crop share the same Stage 5 sufficiency gate.
     * Sufficient DIRECT synthesis skips CropKnowledgeEngine (no second discovery).
     */
    private function shouldRunLegacyPostProcessing(
        AgriculturalResearchPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
    ): bool {
        return ! $this->hasSufficientScientificSynthesis($synthesisReport);
    }

    /**
     * Home DIRECT short-circuit: performed synthesis, non-empty answer and citations,
     * and research_metadata.direct_evidence_gate === PASSED. Does not treat
     * evidence_sufficient or supported_answer as DIRECT.
     */
    private function hasSufficientScientificSynthesis(AnswerSynthesisExecutionReport $synthesis): bool
    {
        if (! $synthesis->performed) {
            return false;
        }

        if (trim((string) $synthesis->answer) === '') {
            return false;
        }

        if ($synthesis->citations === []) {
            return false;
        }

        return ($synthesis->researchMetadata['direct_evidence_gate'] ?? null) === 'PASSED';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHasSufficientScientificResult(array $payload): bool
    {
        if (trim((string) ($payload['answer'] ?? '')) === '') {
            return false;
        }

        if (($payload['citations'] ?? []) === []) {
            return false;
        }

        return ($payload['research_metadata']['direct_evidence_gate'] ?? null) === 'PASSED';
    }

    /**
     * @return array<string, mixed>
     */
    private function stage5ResponseWithoutBlockingPostProcessing(
        KnowledgeQueryPlan $knowledgePlan,
        AgriculturalResearchPlan $plan,
        ScientificSearchExecutionReport $scientificSearch,
        EvidenceValidationExecutionReport $scientificValidation,
        AnswerSynthesisExecutionReport $synthesisReport,
        KnowledgePersistenceExecutionReport $persistenceReport,
    ): array {
        $synthesis = $synthesisReport->toArray();
        $persistence = $persistenceReport->toArray();
        $citations = is_array($synthesis['citations'] ?? null) ? $synthesis['citations'] : [];

        return array_merge($synthesis, $persistence, [
            'status' => 'scientific_generated',
            'stage' => 5,
            'plan' => $plan->toArray(),
            'research' => [
                'query' => $plan->userQuery,
                'agricultural_domain' => $plan->agriculturalDomain,
                'intent' => $plan->intent,
                'entities' => $plan->entities,
                'sections' => [],
                'references' => $citations,
                'load_state' => 'scientific_generated',
                'library' => [
                    'discoverers_used' => [],
                    'retrieval_failed' => false,
                    'legacy_discovery_skipped' => true,
                ],
            ],
            'discovery' => [
                'performed' => false,
                'reason' => 'sufficient_scientific_result',
                'discoverers_used' => [],
                'external_discoverers_used' => [],
                'library_discoverers_used' => [],
                'internet_first' => $knowledgePlan->isInternetFirst(),
            ],
            'query_understanding' => $knowledgePlan->normalizedQuery->toArray(),
            'knowledge_query_plan' => $knowledgePlan->toArray(),
            'scientific_search' => $scientificSearch->toArray(),
            'scientific_validation' => $scientificValidation->toArray(),
            'persistence_status' => $persistenceReport->status,
            'internet_first' => $scientificSearch->internetFirst,
            'observability' => array_merge(
                $synthesisReport->observability,
                $persistenceReport->observability,
                [
                    'legacy_post_processing' => 'skipped',
                    'legacy_post_processing_reason' => 'sufficient_scientific_result',
                    'synthesis_status' => $synthesisReport->status,
                ],
            ),
        ]);
    }

    private function isUniversalOrchestratorEnabled(): bool
    {
        return filter_var(config('agricultural_intelligence.orchestrator_enabled', true), FILTER_VALIDATE_BOOL);
    }

    /**
     * Dual-emit siblings without scientific discovery.
     *
     * @return array<string, mixed>
     */
    private function cropCompatibilityEnvelope(
        AgriculturalResearchPlan $plan,
        KnowledgePersistenceExecutionReport $persistenceReport,
    ): array {
        $query = $plan->knowledgeQueryPlan?->normalizedQuery;
        $cropId = trim((string) ($plan->contextInput['selected_crop_id'] ?? $query?->cropId ?? ''));
        $cropName = trim((string) ($plan->contextInput['selected_crop_name'] ?? $query?->crop ?? ''));
        $knowledgeOption = trim((string) ($plan->contextInput['knowledge_option'] ?? $query?->subtopic ?? 'farming-needs'));
        $persistence = $persistenceReport->toArray();

        return [
            'crop' => [
                'id' => $cropId,
                'name' => $cropName,
                'category_id' => (string) ($plan->contextInput['selected_category_id'] ?? ''),
                'category_name' => (string) ($plan->contextInput['selected_category_name'] ?? ''),
                'scientific_name' => (string) ($plan->contextInput['scientific_name'] ?? $query?->scientificName ?? ''),
            ],
            'knowledge_option' => $knowledgeOption,
            'service_option' => $knowledgeOption,
            'title' => CropKnowledgeOptionCatalog::titleFor($knowledgeOption, $cropName),
            'load_state' => 'scientific_generated',
            'message' => null,
            'sections' => [],
            'references' => [],
            'library' => [
                'item_id' => $persistence['library_persistence']['library_item_id'] ?? $persistenceReport->libraryItemId,
                'slug' => $persistence['library_persistence']['slug'] ?? $persistenceReport->slug,
                'reused_existing' => false,
                'was_missing_before_retrieval' => false,
                'missing_sections_filled' => [],
                'scientific_sections_retrieved' => [],
                'discoverers_used' => [],
                'legacy_discovery_skipped' => true,
            ],
        ];
    }

    /**
     * Home-only: attach evidence lifecycle disposition. Crop profile plans are unchanged.
     */
    private function applyHomeEvidenceLifecycleDisposition(
        KnowledgeQueryPlan $knowledgePlan,
        EvidenceValidationExecutionReport $validationReport,
        AnswerSynthesisExecutionReport $synthesisReport,
    ): AnswerSynthesisExecutionReport {
        return $this->homeEvidenceLifecycleDisposition->applyToSynthesis(
            $knowledgePlan,
            $validationReport,
            $synthesisReport,
        );
    }
}
