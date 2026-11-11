<?php

namespace App\Services\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatProviderQueryIdentity;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Research\Coexistence\CoexistenceDisclosureContext;
use App\Services\Agriculture\Research\Coexistence\CoexistenceMode;
use App\Services\Agriculture\Research\Coexistence\CoexistenceRetrievalPlan;
use App\Services\Agriculture\Research\Coexistence\Stage3CghiaCoexistenceBoundary;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Projection\ProjectionSearchOptionsBinder;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureClassifier;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityPolicy;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Log;

/**
 * Stage 3 multi-source scientific search orchestration layer.
 *
 * Runs all controlled query variants across selected Internet-First providers,
 * then normalize → deduplicate → query-aware rank → relevance filter.
 */
class MultiSourceScientificSearchOrchestrator
{
    private const ADEQUATE_RESULT_COUNT = 8;

    private const MAX_VARIANTS_PER_PROVIDER = 2;

    private const ADAPTER_EXCEPTION_ERROR = 'adapter_exception';

    private ScientificSourceCatalog $catalog;

    private ScientificSourceCapabilityPolicy $capabilityPolicy;

    private ProjectionSearchOptionsBinder $projectionBinder;

    private ?CoexistenceRetrievalPlan $activeCoexistencePlan = null;

    public function __construct(
        private ScientificSourceAdapterRegistry $registry,
        private ScientificSourceSelector $sourceSelector,
        private ScientificSearchQueryBuilder $queryBuilder,
        private ScientificResultDeduplicator $deduplicator,
        private ScientificResultRanker $ranker,
        private ScientificQueryCompiler $queryCompiler = new ScientificQueryCompiler(),
        private Stage3CghiaCoexistenceBoundary $coexistenceBoundary = new Stage3CghiaCoexistenceBoundary(),
        ?ScientificSourceCatalog $catalog = null,
        ?ScientificSourceCapabilityPolicy $capabilityPolicy = null,
        ?ProjectionSearchOptionsBinder $projectionBinder = null,
    ) {
        $this->catalog = $catalog ?? ScientificSourceCatalog::default();
        $this->capabilityPolicy = $capabilityPolicy ?? new ScientificSourceCapabilityPolicy();
        $this->projectionBinder = $projectionBinder ?? new ProjectionSearchOptionsBinder();
    }

    public function execute(KnowledgeQueryPlan $plan, int $limit = 10, ?array $sourceKeys = null): ScientificSearchExecutionReport
    {
        $stage3StartedNs = hrtime(true);
        $variantBudget = ScientificSearchVariantBudget::apply($this->queryBuilder->buildVariantsFromPlan($plan));
        $variants = $variantBudget['variants'];
        $searchQuery = $variants[0] ?? $this->queryBuilder->buildFromPlan($plan);
        $selectionTrace = $this->sourceSelector->selectionTrace($plan);

        if ($sourceKeys !== null) {
            [$selectedSources, $selectionTrace['capability_denied']] = $this->filterEnabledSources($sourceKeys, $plan);
        } else {
            $selectedSources = $selectionTrace['selected'];
        }
        $selectionTrace['capability_basis'] = $this->capabilityBasis($selectedSources);
        if (($selectionTrace['capability_denied'] ?? []) !== []) {
            Log::notice('Stage 3 sources denied by the capability policy', [
                'denied' => array_map(
                    static fn (array $d): array => ['source_key' => $d['source_key'], 'reason' => $d['reason']],
                    $selectionTrace['capability_denied'],
                ),
            ]);
        }

        $coexistencePlan = $this->buildCoexistencePlan($plan, $selectedSources, $searchQuery);
        $this->activeCoexistencePlan = $coexistencePlan;

        if ($selectedSources === []) {
            return $this->emptyReport(
                plan: $plan,
                status: $plan->needsClarification() ? 'needs_clarification' : 'no_sources_selected',
                searchQuery: $searchQuery,
                searchQueries: $variants,
                stage3ElapsedMs: $this->elapsedMsSince($stage3StartedNs),
                searchObservability: $this->buildSearchObservability(
                    selectionTrace: $selectionTrace,
                    variantBudget: $variantBudget,
                    selectedSources: [],
                    adapterStatus: [],
                    outcomes: [],
                    collections: [],
                    rawCount: 0,
                    dedupCount: 0,
                    finalCount: 0,
                    concurrencyMode: 'none',
                    coexistencePlan: $coexistencePlan,
                ),
            );
        }

        if (trim($searchQuery) === '') {
            return $this->emptyReport(
                plan: $plan,
                status: 'empty_query',
                searchQuery: '',
                selectedSources: $selectedSources,
                searchQueries: $variants,
                stage3ElapsedMs: $this->elapsedMsSince($stage3StartedNs),
                searchObservability: $this->buildSearchObservability(
                    selectionTrace: $selectionTrace,
                    variantBudget: $variantBudget,
                    selectedSources: $selectedSources,
                    adapterStatus: [],
                    outcomes: [],
                    collections: [],
                    rawCount: 0,
                    dedupCount: 0,
                    finalCount: 0,
                    concurrencyMode: 'none',
                    coexistencePlan: $coexistencePlan,
                ),
            );
        }

        $adapters = $this->registry->resolveMany($this->prioritizeSources($selectedSources, $plan));
        if ($adapters === []) {
            return $this->emptyReport(
                plan: $plan,
                status: 'unsupported_sources',
                searchQuery: $searchQuery,
                selectedSources: $selectedSources,
                searchQueries: $variants,
                stage3ElapsedMs: $this->elapsedMsSince($stage3StartedNs),
                searchObservability: $this->buildSearchObservability(
                    selectionTrace: $selectionTrace,
                    variantBudget: $variantBudget,
                    selectedSources: $selectedSources,
                    adapterStatus: [],
                    outcomes: [],
                    collections: [],
                    rawCount: 0,
                    dedupCount: 0,
                    finalCount: 0,
                    concurrencyMode: 'none',
                    coexistencePlan: $coexistencePlan,
                ),
            );
        }

        $budget = ScientificSearchTimeBudget::start();
        $concurrencyMode = $this->resolveConcurrencyMode($plan, $adapters);
        $collections = $this->collectProviderExecutions($adapters, $variants, $limit, $plan, $budget);

        $outcomes = [];
        $allResults = [];
        $attempted = [];
        $successful = [];
        $failed = [];
        $empty = [];
        $adapterStatus = [];
        $skippedBudget = [];

        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            $collection = $collections[$key];
            $adapterStatus[$key] = $collection['adapterStatus'];
            if ($collection['attempted']) {
                $attempted[] = $key;
            }
            if ($collection['skippedBudget']) {
                $skippedBudget[] = $key;
            }
            foreach ($collection['outcomes'] as $outcome) {
                $outcomes[] = $outcome;
            }
            $allResults = array_merge($allResults, $collection['results']);
        }

        foreach ($adapterStatus as $key => $status) {
            if ($status === 'success') {
                $successful[] = $key;
            } elseif ($status === 'failed') {
                $failed[] = $key;
            } elseif ($status === 'skipped') {
                // Intentionally omitted from empty/failed aggregates.
                continue;
            } else {
                $empty[] = $key;
            }
        }

        $deduplicated = $this->deduplicator->deduplicate($allResults);
        $ranked = $this->ranker->rank($searchQuery, $deduplicated, $plan);
        $ranked = $this->ranker->filterRelevant($ranked);
        $ranked = $this->ranker->diversifyByProviderJournalInstitution($ranked);

        $status = match (true) {
            $successful !== [] && $ranked !== [] => 'search_completed',
            $successful !== [] && $ranked === [] => 'no_results',
            $failed !== [] && $empty !== [] => 'partial_source_failure',
            $failed !== [] => 'all_sources_failed',
            default => 'no_results',
        };

        $stage3ElapsedMs = $this->elapsedMsSince($stage3StartedNs);
        $searchObservability = $this->buildSearchObservability(
            selectionTrace: $selectionTrace,
            variantBudget: $variantBudget,
            selectedSources: $selectedSources,
            adapterStatus: $adapterStatus,
            outcomes: $outcomes,
            collections: $collections,
            rawCount: count($allResults),
            dedupCount: count($deduplicated),
            finalCount: count($ranked),
            concurrencyMode: $concurrencyMode,
            coexistencePlan: $coexistencePlan,
        );

        return new ScientificSearchExecutionReport(
            status: $status,
            searchQuery: $searchQuery,
            selectedSources: $selectedSources,
            attemptedSources: $attempted,
            successfulSources: $successful,
            failedSources: $failed,
            emptySources: $empty,
            sourceOutcomes: $outcomes,
            results: $allResults,
            // Named historically; value is post-rank / relevance-filtered survivors.
            deduplicatedResults: $ranked,
            planSummary: array_merge($plan->toArray(), [
                'search_queries' => $variants,
                'search_time_budget_seconds' => $budget->seconds,
                'adapters_skipped_time_budget' => array_values(array_unique($skippedBudget)),
                'stage3_elapsed_ms' => $stage3ElapsedMs,
                'provider_duration_ms' => $this->providerDurationMsBySource($outcomes),
                'search_observability' => $searchObservability,
                'result_pipeline' => [
                    'raw_retrieved_count' => count($allResults),
                    'raw_result_count' => count($allResults),
                    'after_dedup_count' => count($deduplicated),
                    'after_rank_filter_count' => count($ranked),
                    'final_survivor_count' => count($ranked),
                    'deduplicated_results_field' => 'historical_name_for_post_rank_filtered_survivors',
                    'deduplicated_results_means' => 'post_rank_filtered_survivors',
                    'stages' => [
                        'rawRetrievedResults' => count($allResults),
                        'deduplicatedResults' => count($deduplicated),
                        'rankedAndRelevanceFilteredResults' => count($ranked),
                        'finalResults' => count($ranked),
                    ],
                ],
            ]),
            internetFirst: $plan->isInternetFirst(),
            searchQueries: $variants,
        );
    }

    /**
     * OpenAlex Consensus options (domain=agri, country=ISO) must not reach FAOSTAT.
     * FAOSTAT interprets domain as a dataset code (QCL) and must receive only FAOSTAT options.
     * OD-C Projection approved metadata is attached per source from resolved Cap/Path decisions.
     *
     * @return array<string, mixed>
     */
    private function optionsForSource(string $sourceKey, KnowledgeQueryPlan $plan): array
    {
        $entry = $this->activeCoexistencePlan?->entryFor($sourceKey);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $canonicalQueryId = $this->activeCoexistencePlan?->csqIdentity
            ?? ('csq:'.hash('sha256', $plan->normalizedQuery->normalizedQuestion));

        $projectionSeed = $this->projectionBinder->attach(
            [],
            $canonicalQueryId,
            $csq,
            $entry?->capabilityDecision,
            $entry?->pathDecision,
        );
        $approved = $projectionSeed[ProjectionSearchOptionsBinder::OPTIONS_KEY] ?? null;
        $projectionStatus = $projectionSeed[ProjectionSearchOptionsBinder::STATUS_KEY] ?? null;
        $approvedForBuilder = $projectionStatus === ProjectionSearchOptionsBinder::STATUS_ATTACHED
            && is_array($approved)
            ? $approved
            : null;

        if ($sourceKey === 'fao_stat') {
            $queries = FaoStatSearchOptionsResolver::canonicalQueriesFromPlan($plan);
            $primary = $queries[0] ?? FaoStatSearchOptionsResolver::fromPlan($plan);
            // Internal orchestration hint only — never sent as a FAOSTAT query parameter.
            $primary['_faostat_canonical_queries'] = $queries !== [] ? $queries : [$primary];
            $options = $this->attachCompiledQueryMetadata($sourceKey, $plan, $primary);
        } else {
            $options = $this->attachCompiledQueryMetadata(
                $sourceKey,
                $plan,
                $this->queryBuilder->buildConsensusRequestOptions($plan, $approvedForBuilder),
            );
        }

        return array_merge($options, $projectionSeed);
    }

    /**
     * Additive local compilation metadata only — does not add provider HTTP calls,
     * does not replace NL variants, and does not execute adapters.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function attachCompiledQueryMetadata(string $sourceKey, KnowledgeQueryPlan $plan, array $options): array
    {
        $csq = $plan->normalizedQuery->canonicalQuestion;
        if ($csq === null) {
            return $options;
        }

        $profile = ScientificSourceProfileCatalog::get($sourceKey);
        if ($profile === null) {
            return $options;
        }

        $bundle = $this->queryCompiler->compile($csq, $plan, $profile);
        $options['scientific_query_compilation'] = $bundle->toArray();

        return $options;
    }

    /**
     * An expected source failure (ScientificSourceFailureClassifier) becomes one FAILED outcome
     * so it cannot abort Stage 3 for other providers. Anything else is a defect or a
     * test-framework signal and propagates. Only the exception class is logged: messages can
     * carry request URLs or credentials.
     *
     * A v2 request requires limit >= 1; callers may pass any int, and v1 adapters clamp it themselves.
     *
     * @param  array<string, mixed>  $options
     */
    private static function searchAdapter(
        ScientificSourceAdapterInterface $adapter,
        string $query,
        int $limit,
        array $options,
        ?string $accessFamily = null,
    ): ScientificSourceSearchOutcome {
        try {
            $outcome = $adapter instanceof ScientificSourceAdapterV2Interface
                ? $adapter->searchRequest(new ScientificSourceSearchRequest($query, max(1, $limit), $options))
                : $adapter->search($query, $limit, $options);
        } catch (\Throwable $exception) {
            $category = ScientificSourceFailureClassifier::classifyThrowable($exception);
            if ($category === null) {
                throw $exception;
            }
            Log::warning('Stage 3 provider search threw; recorded as a failed outcome', [
                'provider' => $adapter->sourceKey(),
                'exception' => $exception::class,
                'failure_category' => $category->value,
            ]);

            return new ScientificSourceSearchOutcome(
                sourceKey: $adapter->sourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: self::ADAPTER_EXCEPTION_ERROR,
                observability: ['exception_class' => $exception::class, 'failure_category' => $category->value],
            );
        }

        return self::withRetrievalProvenance($adapter, $outcome, $accessFamily);
    }

    /**
     * Stamps when and through which adapter each returned record was retrieved, and the
     * licensing the adapter declares; a v1 adapter declares none, so its licensing is unresolved.
     */
    private static function withRetrievalProvenance(
        ScientificSourceAdapterInterface $adapter,
        ScientificSourceSearchOutcome $outcome,
        ?string $accessFamily,
    ): ScientificSourceSearchOutcome {
        if ($outcome->results === []) {
            return $outcome;
        }

        $retrievedAt = now()->utc()->toIso8601String();
        if ($adapter instanceof ScientificSourceAdapterV2Interface) {
            $descriptor = $adapter->descriptor();
            $contractVersion = $descriptor->contractVersion;
            $licensing = $descriptor->licensing;
        } else {
            $contractVersion = ScientificSourceAdapterInterface::CONTRACT_VERSION;
            $licensing = ScientificSourceLicensingDeclaration::unresolved();
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $outcome->sourceKey,
            status: $outcome->status,
            results: array_map(
                static fn (ScientificSearchResult $result): ScientificSearchResult => $result->withRetrievalContext(
                    $outcome->sourceKey,
                    $retrievedAt,
                    $adapter::class,
                    $contractVersion,
                    $accessFamily,
                    $licensing,
                ),
                $outcome->results,
            ),
            error: $outcome->error,
            httpStatus: $outcome->httpStatus,
            observability: $outcome->observability,
        );
    }

    private static function isRateLimitedOutcome(ScientificSourceSearchOutcome $outcome): bool
    {
        return $outcome->status === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
            && ($outcome->httpStatus === 429 || $outcome->error === 'rate_limited');
    }

    private static function hasCompleteStructuredObservation(ScientificSourceSearchOutcome $outcome): bool
    {
        if ($outcome->status !== ScientificSourceSearchOutcome::STATUS_SUCCESS) {
            return false;
        }

        foreach ($outcome->results as $result) {
            $observation = ScientificStructuredObservation::fromResult($result);
            if ($observation !== null && $observation->isComplete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Explicitly requested sources pass the same enablement and capability checks as selected ones.
     *
     * @param  list<string>  $sourceKeys
     * @return array{0: list<string>, 1: list<array<string, string|bool|null>>}
     */
    private function filterEnabledSources(array $sourceKeys, KnowledgeQueryPlan $plan): array
    {
        $enabled = [];
        $denied = [];
        foreach ($sourceKeys as $key) {
            $key = strtolower(trim((string) $key));
            if ($key === '') {
                continue;
            }
            $registration = $this->catalog->get($key);
            if ($registration === null) {
                // Unregistered keys pass through; resolveMany() drops them as unsupported.
                $enabled[] = $key;

                continue;
            }
            if (! $registration->isEnabled()) {
                continue;
            }
            $decision = $this->capabilityPolicy->decide($registration, ScientificSourceSelector::REQUIRED_OPERATION);
            if (! $decision->allowed) {
                $denied[$key] = $decision->toArray();

                continue;
            }
            $enabled[] = $key;
        }

        return [array_values(array_unique($this->prioritizeSources($enabled, $plan))), array_values($denied)];
    }

    /**
     * @param  list<string>  $sourceKeys
     * @return array<string, string>
     */
    private function capabilityBasis(array $sourceKeys): array
    {
        $basis = [];
        foreach ($sourceKeys as $key) {
            $registration = $this->catalog->get($key);
            if ($registration !== null) {
                $basis[$key] = $this->capabilityPolicy
                    ->decide($registration, ScientificSourceSelector::REQUIRED_OPERATION)->reason;
            }
        }

        return $basis;
    }

    private function accessFamilyOf(string $sourceKey): ?string
    {
        return $this->catalog->get($sourceKey)?->accessFamily->value;
    }

    /**
     * Statistical FAOSTAT runs first so a slow scholarly provider cannot consume the whole budget.
     * Non-statistical Home/Crop flows keep scholarly providers ahead of FAOSTAT consideration.
     *
     * @param  list<string>  $sourceKeys
     * @return list<string>
     */
    private function prioritizeSources(array $sourceKeys, KnowledgeQueryPlan $plan): array
    {
        $preferred = [];
        $rest = [];
        foreach ($sourceKeys as $key) {
            if ($key === FaoStatRuntimePolicy::canonicalSourceKey()) {
                $preferred[] = $key;
            } else {
                $rest[] = $key;
            }
        }

        if (FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan)) {
            return [...$preferred, ...$rest];
        }

        return [...$rest, ...$preferred];
    }

    /**
     * @param  list<string>  $selectedSources
     * @param  list<string>  $searchQueries
     * @param  array<string, mixed>|null  $searchObservability
     */
    private function emptyReport(
        KnowledgeQueryPlan $plan,
        string $status,
        string $searchQuery,
        array $selectedSources = [],
        array $searchQueries = [],
        ?int $stage3ElapsedMs = null,
        ?array $searchObservability = null,
    ): ScientificSearchExecutionReport {
        $planSummary = $plan->toArray();
        if ($stage3ElapsedMs !== null) {
            $planSummary['stage3_elapsed_ms'] = $stage3ElapsedMs;
            $planSummary['provider_duration_ms'] = [];
        }
        if ($searchObservability !== null) {
            $planSummary['search_observability'] = $searchObservability;
        }

        return new ScientificSearchExecutionReport(
            status: $status,
            searchQuery: $searchQuery,
            selectedSources: $selectedSources,
            attemptedSources: [],
            successfulSources: [],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: $planSummary,
            internetFirst: $plan->isInternetFirst(),
            searchQueries: $searchQueries,
        );
    }

    private function elapsedMsSince(int $startedNs): int
    {
        return max(0, (int) ((hrtime(true) - $startedNs) / 1_000_000));
    }

    /**
     * Sum known outcome duration_ms per source key. Missing latency stays absent (never coerced to 0).
     *
     * @param  list<ScientificSourceSearchOutcome>  $outcomes
     * @return array<string, int>
     */
    private function providerDurationMsBySource(array $outcomes): array
    {
        $sums = [];
        foreach ($outcomes as $outcome) {
            $ms = $outcome->toArray()['duration_ms'] ?? null;
            if (! is_int($ms)) {
                continue;
            }
            $key = $outcome->sourceKey;
            $sums[$key] = ($sums[$key] ?? 0) + $ms;
        }

        return $sums;
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     * @param  list<string>  $variants
     * @return array<string, array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool, variantsAttempted: int, equivalentVariantsSuppressed: int}>
     */
    private function collectProviderExecutions(
        array $adapters,
        array $variants,
        int $limit,
        KnowledgeQueryPlan $plan,
        ScientificSearchTimeBudget $budget,
    ): array {
        if (! $this->shouldOverlapIndependentScholarlyProviders($plan)
            || $this->independentScholarlyAdapterCount($adapters) < 2) {
            return $this->collectAdaptersSequentially($adapters, $variants, $limit, $plan, $budget, 0);
        }

        $faoAdapters = [];
        $scholarlyAdapters = [];
        $otherAdapters = [];
        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            if ($key === FaoStatRuntimePolicy::canonicalSourceKey()) {
                $faoAdapters[] = $adapter;
            } elseif ($this->isIndependentScholarlySource($key)) {
                $scholarlyAdapters[] = $adapter;
            } else {
                $otherAdapters[] = $adapter;
            }
        }

        $prioritizeFaostat = FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan);
        $collections = [];
        $priorResultCount = 0;

        if ($prioritizeFaostat) {
            $collections = $this->collectAdaptersSequentially($faoAdapters, $variants, $limit, $plan, $budget, 0);
            foreach ($faoAdapters as $adapter) {
                $priorResultCount += count($collections[$adapter->sourceKey()]['results']);
            }
        }

        $scholarlyCollections = $this->collectIndependentScholarlyAdapters(
            $scholarlyAdapters,
            $variants,
            $limit,
            $plan,
            $budget,
            $priorResultCount,
        );
        foreach ($scholarlyAdapters as $adapter) {
            $key = $adapter->sourceKey();
            $collections[$key] = $scholarlyCollections[$key];
            $priorResultCount += count($collections[$key]['results']);
        }

        if (! $prioritizeFaostat && $faoAdapters !== []) {
            $faoCollections = $this->collectAdaptersSequentially(
                $faoAdapters,
                $variants,
                $limit,
                $plan,
                $budget,
                $priorResultCount,
            );
            foreach ($faoAdapters as $adapter) {
                $key = $adapter->sourceKey();
                $collections[$key] = $faoCollections[$key];
                $priorResultCount += count($collections[$key]['results']);
            }
        }

        $otherCollections = $this->collectAdaptersSequentially(
            $otherAdapters,
            $variants,
            $limit,
            $plan,
            $budget,
            $priorResultCount,
        );

        return $collections + $otherCollections;
    }

    /**
     * Home generic_research may overlap independent scholarly HTTP.
     * Crop profile keeps the existing sequential provider loop.
     */
    private function shouldOverlapIndependentScholarlyProviders(KnowledgeQueryPlan $plan): bool
    {
        $researchPlan = $plan->toAgriculturalResearchPlan();
        if ($researchPlan->isCropProfileIntent()) {
            return false;
        }
        if ($researchPlan->intent !== 'generic_research') {
            return false;
        }
        if (app()->runningUnitTests()
            && ! (bool) config('agricultural_intelligence.stage3_home_scholarly_concurrency', false)) {
            return false;
        }

        return true;
    }

    private function isIndependentScholarlySource(string $sourceKey): bool
    {
        return $this->catalog->isInExecutionGroup($sourceKey, ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY);
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     */
    private function independentScholarlyAdapterCount(array $adapters): int
    {
        $count = 0;
        foreach ($adapters as $adapter) {
            if ($this->isIndependentScholarlySource($adapter->sourceKey())) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     * @param  list<string>  $variants
     * @return array<string, array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool}>
     */
    private function collectAdaptersSequentially(
        array $adapters,
        array $variants,
        int $limit,
        KnowledgeQueryPlan $plan,
        ScientificSearchTimeBudget $budget,
        int $priorResultCount,
    ): array {
        $collections = [];
        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            $collections[$key] = self::collectAdapterSearch(
                $adapter,
                $variants,
                $limit,
                $this->optionsForSource($key, $plan),
                $budget,
                $priorResultCount,
                $this->accessFamilyOf($key),
            );
            $priorResultCount += count($collections[$key]['results']);
        }

        return $collections;
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     * @param  list<string>  $variants
     * @return array<string, array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool}>
     */
    private function collectIndependentScholarlyAdapters(
        array $adapters,
        array $variants,
        int $limit,
        KnowledgeQueryPlan $plan,
        ScientificSearchTimeBudget $budget,
        int $priorResultCount,
    ): array {
        if ($adapters === []) {
            return [];
        }
        if (count($adapters) === 1) {
            return $this->collectAdaptersSequentially($adapters, $variants, $limit, $plan, $budget, $priorResultCount);
        }

        $tasks = [];
        foreach ($adapters as $adapter) {
            $sourceKey = $adapter->sourceKey();
            $baseOptions = $this->optionsForSource($sourceKey, $plan);
            $accessFamily = $this->accessFamilyOf($sourceKey);
            $capturedAdapter = $adapter;

            if (app()->runningUnitTests()) {
                $adapterFile = (new \ReflectionClass($capturedAdapter))->getFileName();
                $tasks[$sourceKey] = static function () use (
                    $adapterFile,
                    $capturedAdapter,
                    $variants,
                    $limit,
                    $baseOptions,
                    $budget,
                    $priorResultCount,
                    $accessFamily,
                ) {
                    if (is_string($adapterFile) && $adapterFile !== '' && is_file($adapterFile)) {
                        require_once $adapterFile;
                    }
                    $budget->bind();

                    return self::collectAdapterSearch(
                        $capturedAdapter,
                        $variants,
                        $limit,
                        $baseOptions,
                        $budget,
                        $priorResultCount,
                        $accessFamily,
                    );
                };

                continue;
            }

            $tasks[$sourceKey] = static function () use (
                $sourceKey,
                $variants,
                $limit,
                $baseOptions,
                $budget,
                $priorResultCount,
                $accessFamily,
            ) {
                $budget->bind();
                $resolved = app(ScientificSourceAdapterRegistry::class)->get($sourceKey);
                if (! $resolved instanceof ScientificSourceAdapterInterface) {
                    return [
                        'attempted' => false,
                        'adapterStatus' => 'skipped',
                        'outcomes' => [
                            new ScientificSourceSearchOutcome(
                                sourceKey: $sourceKey,
                                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                                error: 'unsupported_sources',
                            ),
                        ],
                        'results' => [],
                        'skippedBudget' => false,
                        'variantsAttempted' => 0,
                        'equivalentVariantsSuppressed' => 0,
                    ];
                }

                return self::collectAdapterSearch(
                    $resolved,
                    $variants,
                    $limit,
                    $baseOptions,
                    $budget,
                    $priorResultCount,
                    $accessFamily,
                );
            };
        }

        try {
            /** @var array<string, array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool}> $wave */
            $wave = Concurrency::driver('process')->run($tasks);
        } catch (\Throwable $exception) {
            // Expected source failures never leave a task (searchAdapter contains them), so a
            // child exception re-created here by the driver is a defect and must propagate.
            if (! ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure($exception)) {
                throw $exception;
            }
            Log::warning('Stage 3 scholarly concurrency process pool failed; not replaying provider searches', [
                'exception' => $exception::class,
                'providers' => array_map(
                    static fn (ScientificSourceAdapterInterface $adapter): string => $adapter->sourceKey(),
                    $adapters,
                ),
            ]);

            $collections = [];
            foreach ($adapters as $adapter) {
                $collections[$adapter->sourceKey()] = self::unavailableWaveCollection($adapter->sourceKey());
            }

            return $collections;
        }

        $collections = [];
        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            if (! isset($wave[$key]) || ! is_array($wave[$key])) {
                Log::warning('Stage 3 scholarly concurrency missing wave result; not replaying provider search', [
                    'provider' => $key,
                ]);
                $collections[$key] = self::unavailableWaveCollection($key);

                continue;
            }
            $collections[$key] = $wave[$key];
        }

        return $collections;
    }

    /**
     * @return array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool, variantsAttempted: int, equivalentVariantsSuppressed: int}
     */
    private static function unavailableWaveCollection(string $sourceKey): array
    {
        return [
            'attempted' => true,
            'adapterStatus' => 'failed',
            'outcomes' => [
                new ScientificSourceSearchOutcome(
                    sourceKey: $sourceKey,
                    status: ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'request_exception',
                ),
            ],
            'results' => [],
            'skippedBudget' => false,
            'variantsAttempted' => 0,
            'equivalentVariantsSuppressed' => 0,
        ];
    }

    /**
     * @param  list<string>  $variants
     * @param  array<string, mixed>  $baseOptions
     * @return array{attempted: bool, adapterStatus: string, outcomes: list<ScientificSourceSearchOutcome>, results: list<ScientificSearchResult>, skippedBudget: bool}
     */
    private static function collectAdapterSearch(
        ScientificSourceAdapterInterface $adapter,
        array $variants,
        int $limit,
        array $baseOptions,
        ScientificSearchTimeBudget $budget,
        int $priorResultCount,
        ?string $accessFamily = null,
    ): array {
        $key = $adapter->sourceKey();
        if ($budget->remainingSeconds() < 1.0) {
            return [
                'attempted' => false,
                'adapterStatus' => 'skipped',
                'outcomes' => [
                    new ScientificSourceSearchOutcome(
                        sourceKey: $key,
                        status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                        error: 'time_budget_exhausted',
                        observability: [
                            'reason' => 'search_time_budget',
                            'budget_seconds' => $budget->seconds,
                            'remaining_seconds' => $budget->remainingSeconds(),
                        ],
                    ),
                ],
                'results' => [],
                'skippedBudget' => true,
                'variantsAttempted' => 0,
                'equivalentVariantsSuppressed' => 0,
            ];
        }

        $outcomes = [];
        $results = [];
        $adapterStatus = 'empty';
        $skippedBudget = false;
        $skipRemainingVariants = false;
        $variantsAttempted = 0;
        $equivalentVariantsSuppressed = 0;
        $key = $adapter->sourceKey();
        $isFaostat = $key === FaoStatRuntimePolicy::canonicalSourceKey();
        $seenScholarlyIdentities = [];

        // FAOSTAT: execute each canonical dimension tuple once (multi-measure decomposition).
        // NL search variants must not re-hit the portal for equivalent identities.
        if ($isFaostat) {
            $canonicalQueries = [];
            if (isset($baseOptions['_faostat_canonical_queries']) && is_array($baseOptions['_faostat_canonical_queries'])) {
                $canonicalQueries = $baseOptions['_faostat_canonical_queries'];
            }
            if ($canonicalQueries === []) {
                $canonicalQueries = [$baseOptions];
            }

            $queryText = $variants[0] ?? '';
            $seenIdentities = [];
            foreach ($canonicalQueries as $opts) {
                if (! is_array($opts)) {
                    continue;
                }
                if ($budget->remainingSeconds() < 1.0) {
                    $skippedBudget = true;
                    break;
                }
                unset($opts['_faostat_canonical_queries']);
                $identity = FaoStatProviderQueryIdentity::fromOptions($opts);
                if ($identity !== null && isset($seenIdentities[$identity])) {
                    $equivalentVariantsSuppressed++;

                    continue;
                }
                if ($identity !== null) {
                    $seenIdentities[$identity] = true;
                }

                $outcome = self::searchAdapter(
                    $adapter,
                    $queryText,
                    $limit,
                    array_merge($opts, [
                        'search_budget_remaining_seconds' => $budget->remainingSeconds(),
                    ]),
                    $accessFamily,
                );
                $variantsAttempted++;
                $obs = is_array($outcome->observability) ? $outcome->observability : [];
                if ($identity !== null) {
                    $obs['query_identity'] = $identity;
                }
                $outcome = new ScientificSourceSearchOutcome(
                    sourceKey: $outcome->sourceKey,
                    status: $outcome->status,
                    results: $outcome->results,
                    error: $outcome->error,
                    httpStatus: $outcome->httpStatus,
                    observability: $obs,
                );
                $outcomes[] = $outcome;

                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
                    $adapterStatus = 'success';
                    $results = array_merge($results, $outcome->results);
                } elseif ($outcome->status === ScientificSourceSearchOutcome::STATUS_EMPTY) {
                    if ($adapterStatus !== 'success' && $adapterStatus !== 'failed') {
                        $adapterStatus = 'empty';
                    }
                } elseif ($outcome->status === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
                    && $outcome->error === 'missing_api_key') {
                    if ($adapterStatus !== 'success' && $adapterStatus !== 'failed') {
                        $adapterStatus = 'skipped';
                    }
                } else {
                    if ($adapterStatus !== 'success') {
                        $adapterStatus = 'failed';
                    }
                    if ($outcome->error === self::ADAPTER_EXCEPTION_ERROR) {
                        break;
                    }
                }
            }

            return [
                'attempted' => true,
                'adapterStatus' => $adapterStatus,
                'outcomes' => $outcomes,
                'results' => $results,
                'skippedBudget' => $skippedBudget,
                'variantsAttempted' => $variantsAttempted,
                'equivalentVariantsSuppressed' => $equivalentVariantsSuppressed,
            ];
        }

        foreach ($variants as $variant) {
            if ($skipRemainingVariants || $variantsAttempted >= self::MAX_VARIANTS_PER_PROVIDER) {
                continue;
            }
            if ($budget->remainingSeconds() < 1.0) {
                $skipRemainingVariants = true;
                $skippedBudget = true;

                continue;
            }

            $variantIdentity = ScientificSearchVariantBudget::identityKey($variant);
            if (isset($seenScholarlyIdentities[$variantIdentity])) {
                $equivalentVariantsSuppressed++;

                continue;
            }
            $seenScholarlyIdentities[$variantIdentity] = true;

            $outcome = self::searchAdapter(
                $adapter,
                $variant,
                $limit,
                array_merge($baseOptions, [
                    'search_budget_remaining_seconds' => $budget->remainingSeconds(),
                ]),
                $accessFamily,
            );
            $variantsAttempted++;
            $outcomes[] = $outcome;

            if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
                $adapterStatus = 'success';
                $results = array_merge($results, $outcome->results);
                if ($outcome->results !== [] && ($priorResultCount + count($results)) >= self::ADEQUATE_RESULT_COUNT) {
                    $skipRemainingVariants = true;
                }
            } elseif ($outcome->status === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
                && $outcome->error === 'missing_api_key') {
                // Missing Consensus key: unavailable but not a provider failure — OA+CR continue.
                if ($adapterStatus !== 'success' && $adapterStatus !== 'failed') {
                    $adapterStatus = 'skipped';
                }
                $skipRemainingVariants = true;
            } elseif ($outcome->status === ScientificSourceSearchOutcome::STATUS_EMPTY) {
                if ($adapterStatus !== 'success' && $adapterStatus !== 'failed') {
                    $adapterStatus = 'empty';
                }
            } else {
                // failed / unavailable / rate-limited / timeout — provider-partial only
                if ($adapterStatus !== 'success') {
                    $adapterStatus = 'failed';
                }

                // After first OpenAlex (or any provider) 429, skip remaining variants for
                // that provider in this request; other providers still run all variants.
                if (self::isRateLimitedOutcome($outcome) || $outcome->error === self::ADAPTER_EXCEPTION_ERROR) {
                    $skipRemainingVariants = true;
                }
            }
        }

        return [
            'attempted' => true,
            'adapterStatus' => $adapterStatus,
            'outcomes' => $outcomes,
            'results' => $results,
            'skippedBudget' => $skippedBudget,
            'variantsAttempted' => $variantsAttempted,
            'equivalentVariantsSuppressed' => $equivalentVariantsSuppressed,
        ];
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     */
    private function resolveConcurrencyMode(KnowledgeQueryPlan $plan, array $adapters): string
    {
        if (! $this->shouldOverlapIndependentScholarlyProviders($plan)
            || $this->independentScholarlyAdapterCount($adapters) < 2) {
            return 'sequential';
        }

        return FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan)
            ? 'home_overlap_faostat_first'
            : 'home_overlap_scholarly_first';
    }

    /**
     * @param  array<string, mixed>  $selectionTrace
     * @param  array<string, mixed>  $variantBudget
     * @param  list<string>  $selectedSources
     * @param  array<string, string>  $adapterStatus
     * @param  list<ScientificSourceSearchOutcome>  $outcomes
     * @param  array<string, array<string, mixed>>  $collections
     * @return array<string, mixed>
     */
    private function buildSearchObservability(
        array $selectionTrace,
        array $variantBudget,
        array $selectedSources,
        array $adapterStatus,
        array $outcomes,
        array $collections,
        int $rawCount,
        int $dedupCount,
        int $finalCount,
        string $concurrencyMode,
        ?CoexistenceRetrievalPlan $coexistencePlan = null,
    ): array {
        $providerVariantCounts = [];
        $executionCount = 0;
        $duplicateSuppressedExecutions = (int) ($variantBudget['equivalent_suppressed'] ?? 0)
            + (int) ($variantBudget['truncated'] ?? 0);
        $timeouts = 0;
        $retryCounts = 0;
        $sourceFailures = [];

        foreach ($collections as $key => $collection) {
            $attempted = (int) ($collection['variantsAttempted'] ?? 0);
            $suppressed = (int) ($collection['equivalentVariantsSuppressed'] ?? 0);
            $providerVariantCounts[$key] = [
                'attempted' => $attempted,
                'equivalent_suppressed' => $suppressed,
                'max_per_provider' => self::MAX_VARIANTS_PER_PROVIDER,
            ];
            $executionCount += $attempted;
            $duplicateSuppressedExecutions += $suppressed;
        }

        foreach ($outcomes as $outcome) {
            $error = (string) ($outcome->error ?? '');
            if ($error === 'timeout' || str_contains($error, 'timeout')) {
                $timeouts++;
            }
            $obs = is_array($outcome->observability) ? $outcome->observability : [];
            if (isset($obs['retry_count']) && is_numeric($obs['retry_count'])) {
                $retryCounts += (int) $obs['retry_count'];
            } elseif (isset($obs['retries']) && is_numeric($obs['retries'])) {
                $retryCounts += (int) $obs['retries'];
            }
            $category = ScientificSourceFailureClassifier::classifyOutcome($outcome);
            if ($category !== null) {
                $sourceFailures[] = [
                    'source' => $outcome->sourceKey,
                    'error' => $outcome->error,
                    'failure_category' => $category->value,
                    'retryable' => $category->isRetryable(),
                ];
            }
        }

        $successful = [];
        $failed = [];
        $empty = [];
        $skipped = [];
        foreach ($adapterStatus as $key => $status) {
            match ($status) {
                'success' => $successful[] = $key,
                'failed' => $failed[] = $key,
                'skipped' => $skipped[] = $key,
                default => $empty[] = $key,
            };
        }

        $payload = [
            'selected_providers' => array_values($selectedSources),
            'skipped_providers' => array_values(array_unique(array_merge(
                $selectionTrace['skipped_inactive'] ?? [],
                $skipped,
            ))),
            'optional_not_selected' => $selectionTrace['optional_not_selected']
                ?? $this->catalog->keysWithRole(ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY),
            'faostat_consideration' => $selectionTrace['faostat_consideration'] ?? null,
            'selection_policy' => $selectionTrace['policy'] ?? null,
            'source_capability' => [
                'required_operation' => ScientificSourceSelector::REQUIRED_OPERATION->value,
                'basis' => $selectionTrace['capability_basis'] ?? [],
                'denied' => $selectionTrace['capability_denied'] ?? [],
            ],
            'variant_count' => (int) ($variantBudget['output_count'] ?? count($variantBudget['variants'] ?? [])),
            'variant_input_count' => (int) ($variantBudget['input_count'] ?? 0),
            'variant_equivalent_suppressed' => (int) ($variantBudget['equivalent_suppressed'] ?? 0),
            'variant_truncated' => (int) ($variantBudget['truncated'] ?? 0),
            'max_variants' => ScientificSearchVariantBudget::MAX_VARIANTS,
            'max_variants_per_provider' => self::MAX_VARIANTS_PER_PROVIDER,
            'provider_variant_counts' => $providerVariantCounts,
            'execution_count' => $executionCount,
            'duplicate_suppressed_executions' => $duplicateSuppressedExecutions,
            'successful_provider_calls' => $successful,
            'empty_provider_responses' => $empty,
            'provider_failures' => $failed,
            'source_failures' => $sourceFailures,
            'timeouts' => $timeouts,
            'retry_counts' => $retryCounts,
            'normalized_result_count' => $rawCount,
            'deduplicated_result_count' => $dedupCount,
            'final_handoff_result_count' => $finalCount,
            'concurrency_mode' => $concurrencyMode,
            'provider_status' => $adapterStatus,
        ];

        if ($coexistencePlan !== null) {
            $payload = array_merge($payload, $coexistencePlan->toObservabilityFragment());
        }

        return $payload;
    }

    /**
     * IU-09 coexistence attach — does not replace Selector/Registry/Builder.
     *
     * @param  list<string>  $selectedSources
     */
    private function buildCoexistencePlan(
        KnowledgeQueryPlan $plan,
        array $selectedSources,
        string $searchQuery,
    ): CoexistenceRetrievalPlan {
        CoexistenceDisclosureContext::instance()->clear();

        $nq = $plan->normalizedQuery;
        $questionIdentity = 'q:'.hash('sha256', $nq->originalQuestion);
        $csqIdentity = $nq->canonicalQuestion !== null
            ? 'csq:'.hash('sha256', 'frozen|'.$nq->normalizedQuestion)
            : 'csq:'.hash('sha256', 'uncanonical|'.$nq->normalizedQuestion);

        return $this->coexistenceBoundary->plan(
            CoexistenceMode::fromConfig(),
            $selectedSources,
            $searchQuery,
            $questionIdentity,
            $csqIdentity,
        );
    }
}
