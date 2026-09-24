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
use App\Services\Agriculture\Research\Synthesis\ScientificAnswerCandidatePresenter;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use Illuminate\Validation\ValidationException;

/**
 * Top-level agricultural research orchestration layer.
 * Coordinates query understanding, planning, scientific search, validation, synthesis, and persistence.
 * Scientific Research terminates at its own Stage 5 result. Library Search is not a research stage.
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

        $startedNs = hrtime(true);
        $searchStartedNs = hrtime(true);
        $searchReport = $this->scientificSearchService->search(
            $knowledgePlan,
            (int) ($input['limit'] ?? 10),
        );
        $stage3Ms = $this->elapsedMsSince($searchStartedNs);
        $validationStartedNs = hrtime(true);
        $validationReport = $this->scientificValidationService->validate($knowledgePlan, $searchReport);
        $stage4Ms = $this->elapsedMsSince($validationStartedNs);
        $composeStartedNs = hrtime(true);
        $synthesisReport = $this->answerComposer->compose($knowledgePlan, $validationReport);
        $synthesisReport = $this->applyHomeEvidenceLifecycleDisposition(
            $knowledgePlan,
            $validationReport,
            $synthesisReport,
        );
        $composerMs = $this->elapsedMsSince($composeStartedNs);
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $validationReport,
        );

        $candidates = ScientificAnswerCandidatePresenter::fromSynthesis($synthesisReport);

        $payload = array_merge(
            $synthesisReport->toArray(),
            $persistenceReport->toArray(),
            $candidates,
            [
                'status' => $synthesisReport->status,
                'persistence_status' => $persistenceReport->status,
                'observability' => array_merge(
                    $synthesisReport->observability,
                    $persistenceReport->observability,
                    [
                        'legacy_post_processing' => 'removed',
                        'legacy_post_processing_reason' => 'library_search_separated',
                    ],
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
                'stage_timings' => $this->stageTimings(
                    stage3Ms: $stage3Ms,
                    stage4Ms: $stage4Ms,
                    composerMs: $composerMs,
                    totalMs: $this->elapsedMsSince($startedNs),
                    searchReport: $searchReport,
                    validationReport: $validationReport,
                    synthesisReport: $synthesisReport,
                ),
            ],
        );

        return $payload;
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

        $startedNs = hrtime(true);
        $searchStartedNs = hrtime(true);
        $scientificSearch = $this->scientificSearchService->search($knowledgePlan);
        $stage3Ms = $this->elapsedMsSince($searchStartedNs);
        $validationStartedNs = hrtime(true);
        $scientificValidation = $this->scientificValidationService->validate($knowledgePlan, $scientificSearch);
        $stage4Ms = $this->elapsedMsSince($validationStartedNs);
        $composeStartedNs = hrtime(true);
        $synthesisReport = $this->answerComposer->compose($knowledgePlan, $scientificValidation);
        $synthesisReport = $this->applyHomeEvidenceLifecycleDisposition(
            $knowledgePlan,
            $scientificValidation,
            $synthesisReport,
        );
        $composerMs = $this->elapsedMsSince($composeStartedNs);
        $persistenceReport = $this->knowledgePersistenceService->persist(
            $organizationId,
            $knowledgePlan,
            $synthesisReport,
            $scientificValidation,
        );

        $plan = $knowledgePlan->toAgriculturalResearchPlan();
        $timings = $this->stageTimings(
            stage3Ms: $stage3Ms,
            stage4Ms: $stage4Ms,
            composerMs: $composerMs,
            totalMs: $this->elapsedMsSince($startedNs),
            searchReport: $scientificSearch,
            validationReport: $scientificValidation,
            synthesisReport: $synthesisReport,
        );

        if ($plan->isCropProfileIntent()) {
            $legacy = $this->cropCompatibilityEnvelope(
                $plan,
                $persistenceReport,
                $this->scientificLoadState($synthesisReport),
            );
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
                    'performed' => false,
                    'reason' => 'library_search_separated',
                    'discoverers_used' => [],
                    'external_discoverers_used' => [],
                    'library_discoverers_used' => [],
                    'internet_first' => $knowledgePlan->isInternetFirst(),
                ],
            ];

            $response = CropCanonicalStage5Response::dualEmit($legacy, $synthesisReport);

            return $this->terminateScientificResponse(
                $response,
                $knowledgePlan,
                $plan,
                $scientificSearch,
                $scientificValidation,
                $synthesisReport,
                $persistenceReport,
                $timings,
            );
        }

        return $this->stage5ResponseWithoutBlockingPostProcessing(
            $knowledgePlan,
            $plan,
            $scientificSearch,
            $scientificValidation,
            $synthesisReport,
            $persistenceReport,
            $timings,
        );
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
     * Home DIRECT sufficiency: performed synthesis, non-empty answer and citations,
     * and research_metadata.direct_evidence_gate === PASSED. Does not treat
     * evidence_sufficient or supported_answer as DIRECT. Does not start Library Search.
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
     * @param  array<string, mixed>  $timings
     * @return array<string, mixed>
     */
    private function stage5ResponseWithoutBlockingPostProcessing(
        KnowledgeQueryPlan $knowledgePlan,
        AgriculturalResearchPlan $plan,
        ScientificSearchExecutionReport $scientificSearch,
        EvidenceValidationExecutionReport $scientificValidation,
        AnswerSynthesisExecutionReport $synthesisReport,
        KnowledgePersistenceExecutionReport $persistenceReport,
        array $timings = [],
    ): array {
        $sufficient = $this->hasSufficientScientificSynthesis($synthesisReport);
        $synthesis = $synthesisReport->toArray();
        $persistence = $persistenceReport->toArray();
        $citations = is_array($synthesis['citations'] ?? null) ? $synthesis['citations'] : [];
        $candidates = ScientificAnswerCandidatePresenter::fromSynthesis($synthesisReport);
        $status = $sufficient ? 'scientific_generated' : $synthesisReport->status;
        $loadState = $sufficient ? 'scientific_generated' : $synthesisReport->status;

        return array_merge($synthesis, $persistence, $candidates, [
            'status' => $status,
            'load_state' => $loadState,
            'stage' => 5,
            'plan' => $plan->toArray(),
            'research' => [
                'query' => $plan->userQuery,
                'agricultural_domain' => $plan->agriculturalDomain,
                'intent' => $plan->intent,
                'entities' => $plan->entities,
                'sections' => [],
                'references' => $citations,
                'load_state' => $loadState,
                'library' => [
                    'discoverers_used' => [],
                    'retrieval_failed' => false,
                    'legacy_discovery_skipped' => true,
                    'library_search_separated' => true,
                ],
            ],
            'discovery' => [
                'performed' => false,
                'reason' => 'library_search_separated',
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
            'stage_timings' => $timings,
            'observability' => array_merge(
                $synthesisReport->observability,
                $persistenceReport->observability,
                [
                    'legacy_post_processing' => 'removed',
                    'legacy_post_processing_reason' => 'library_search_separated',
                    'synthesis_status' => $synthesisReport->status,
                ],
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $timings
     * @return array<string, mixed>
     */
    private function terminateScientificResponse(
        array $response,
        KnowledgeQueryPlan $knowledgePlan,
        AgriculturalResearchPlan $plan,
        ScientificSearchExecutionReport $scientificSearch,
        EvidenceValidationExecutionReport $scientificValidation,
        AnswerSynthesisExecutionReport $synthesisReport,
        KnowledgePersistenceExecutionReport $persistenceReport,
        array $timings,
    ): array {
        $candidates = ScientificAnswerCandidatePresenter::fromSynthesis($synthesisReport);
        $sufficient = $this->hasSufficientScientificSynthesis($synthesisReport);

        $status = $sufficient ? 'scientific_generated' : $synthesisReport->status;

        return array_merge($response, $candidates, [
            'status' => $status,
            'load_state' => $status,
            'stage' => 5,
            'scientific_search' => $scientificSearch->toArray(),
            'scientific_validation' => $scientificValidation->toArray(),
            'persistence_status' => $persistenceReport->status,
            'stage_timings' => $timings,
            'discovery' => [
                'performed' => false,
                'reason' => 'library_search_separated',
                'discoverers_used' => [],
                'external_discoverers_used' => [],
                'library_discoverers_used' => [],
                'internet_first' => $knowledgePlan->isInternetFirst(),
            ],
            'observability' => array_merge(
                is_array($response['observability'] ?? null) ? $response['observability'] : [],
                $synthesisReport->observability,
                $persistenceReport->observability,
                [
                    'legacy_post_processing' => 'removed',
                    'legacy_post_processing_reason' => 'library_search_separated',
                    'synthesis_status' => $synthesisReport->status,
                ],
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stageTimings(
        int $stage3Ms,
        int $stage4Ms,
        int $composerMs,
        int $totalMs,
        ScientificSearchExecutionReport $searchReport,
        EvidenceValidationExecutionReport $validationReport,
        AnswerSynthesisExecutionReport $synthesisReport,
    ): array {
        $providerMs = $searchReport->planSummary['provider_duration_ms'] ?? [];

        return [
            'stage_3' => [
                'executed' => true,
                'duration_ms' => $stage3Ms,
                'input_count' => count($searchReport->searchQueries !== [] ? $searchReport->searchQueries : [$searchReport->searchQuery]),
                'output_count' => count($searchReport->deduplicatedResults),
                'blocking' => true,
                'provider_duration_ms' => is_array($providerMs) ? $providerMs : [],
            ],
            'stage_4' => [
                'executed' => true,
                'duration_ms' => $stage4Ms,
                'input_count' => count($searchReport->deduplicatedResults),
                'output_count' => $validationReport->validatedCount,
                'blocking' => true,
            ],
            'stage_5' => [
                'executed' => true,
                'duration_ms' => $composerMs,
                'input_count' => $validationReport->validatedCount,
                'output_count' => count($synthesisReport->citations),
                'blocking' => true,
            ],
            'answer_composer' => [
                'executed' => $synthesisReport->performed,
                'duration_ms' => $composerMs,
                'input_count' => $validationReport->validatedCount,
                'output_count' => trim((string) $synthesisReport->answer) !== '' ? 1 : 0,
                'blocking' => true,
            ],
            'final_response' => [
                'executed' => true,
                'duration_ms' => $totalMs,
                'input_count' => 1,
                'output_count' => 1,
                'blocking' => true,
            ],
        ];
    }

    private function elapsedMsSince(int $startedNs): int
    {
        return (int) round((hrtime(true) - $startedNs) / 1e6);
    }

    private function isUniversalOrchestratorEnabled(): bool
    {
        return filter_var(config('agricultural_intelligence.orchestrator_enabled', true), FILTER_VALIDATE_BOOL);
    }

    private function scientificLoadState(AnswerSynthesisExecutionReport $synthesisReport): string
    {
        return $this->hasSufficientScientificSynthesis($synthesisReport)
            ? 'scientific_generated'
            : $synthesisReport->status;
    }

    /**
     * Dual-emit siblings without Library Search or scientific rediscovery.
     *
     * @return array<string, mixed>
     */
    private function cropCompatibilityEnvelope(
        AgriculturalResearchPlan $plan,
        KnowledgePersistenceExecutionReport $persistenceReport,
        string $loadState,
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
            'load_state' => $loadState,
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
