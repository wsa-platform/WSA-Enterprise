<?php

namespace App\Services\Agriculture\Intelligence\Orchestration;

use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;
use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\AnswerEligibility;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\DTO\UniversalAnswerResult;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;
use App\Services\Agriculture\Intelligence\Fusion\EvidenceFusionService;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use Illuminate\Support\Facades\Log;

/**
 * Universal Agricultural Answer Orchestrator (ADR-002).
 *
 * QUS → plan → select providers → retrieve → normalize → dedupe → rank/conflict
 * → web consensus → scientific fusion → validation → eligibility → compose.
 *
 * Reuses existing Research pipeline for scientific validation; does not weaken ASVS/Matcher.
 */
final class UniversalAnswerOrchestrator
{
    public function __construct(
        private QueryUnderstandingService $queryUnderstanding,
        private ResearchPlanner $planner,
        private CapabilityDrivenSourceSelector $sourceSelector,
        private RequiredCapabilityResolver $capabilityResolver,
        private EvidenceFusionService $fusionService,
        private AnswerEligibilityResolver $eligibilityResolver,
        private WebSearchProviderInterface $webSearch,
        private AgriculturalScientificSearchService $scientificSearchService,
        private AgriculturalScientificValidationService $scientificValidationService,
        private AnswerComposer $answerComposer,
    ) {}

    /**
     * Full multi-source answer path.
     *
     * @param  array<string, mixed>  $input
     */
    public function answer(array $input): UniversalAnswerResult
    {
        $started = microtime(true);
        $understood = $this->queryUnderstanding->understand($input);
        $plan = $this->planner->planKnowledgeQuery($input);

        if ($plan->needsClarification() && ! filter_var($input['force_execute'] ?? false, FILTER_VALIDATE_BOOL)) {
            $eligibility = AnswerEligibility::resolve(false, false, ['needs_clarification']);

            return new UniversalAnswerResult(
                eligibility: $eligibility,
                answer: null,
                conciseSummary: null,
                limitations: ['needs_clarification'],
                status: 'needs_clarification',
                observability: [
                    'stage' => 'clarification',
                    'query_understanding' => $understood->toArray(),
                ],
                legacy: [
                    'query_understanding' => $understood->toArray(),
                    'knowledge_query_plan' => $plan->toArray(),
                ],
            );
        }

        $providerInput = $this->buildProviderInput($plan, $input);
        $selection = $this->sourceSelector->selectWithTrace($plan, $providerInput);
        $providers = $selection->selected;

        $providerResults = [];
        $providersAttempted = [];
        $scientificSourceKeys = [];
        foreach ($providers as $provider) {
            $id = $provider->descriptor()->id;
            $providersAttempted[] = $id;
            // Scientific scholarly/official adapters run via Stage 3 to preserve ranking/dedupe/validation.
            if ($provider->descriptor()->type === 'scientific') {
                $scientificSourceKeys[] = $id;

                continue;
            }
            // Web is retrieved once via retrieveWeb() to avoid duplicate hits.
            if ($provider->descriptor()->type === 'web') {
                continue;
            }
            $providerResults[] = $provider->retrieve($providerInput);
        }

        $webResult = null;
        if (in_array('web_search', $selection->requiredCapabilities, true)
            || in_array('general_knowledge', $selection->requiredCapabilities, true)
            || $selection->requiredCapabilities === []) {
            $webResult = $this->retrieveWeb($providerInput);
        }
        if ($webResult !== null) {
            $providerResults[] = $webResult;
            $providersAttempted[] = $webResult->providerId;
        }

        $wantsScientific = array_intersect(
            $selection->requiredCapabilities,
            ['scientific_search', 'scholarly_evidence', 'citation_metadata', 'official_agricultural_data', 'agricultural_statistics'],
        ) !== [];
        $searchReport = $wantsScientific
            ? $this->scientificSearchService->search(
                $plan,
                (int) ($input['limit'] ?? 10),
                $scientificSourceKeys,
            )
            : $this->scientificSearchService->search($plan, (int) ($input['limit'] ?? 10), []);
        $validationReport = $this->scientificValidationService->validate($plan, $searchReport);
        $synthesisReport = $this->answerComposer->compose($plan, $validationReport);

        $scientificEligible = (bool) ($synthesisReport->researchMetadata['evidence_sufficient'] ?? false);
        $scientificPartial = ! $scientificEligible
            && ($synthesisReport->performed ?? false)
            && ($synthesisReport->keyFindings !== [] || $synthesisReport->citations !== []);

        // Bridge scientific validated evidence into canonical results for fusion
        $scientificCanonical = $this->scientificToCanonical($searchReport->toArray(), $validationReport->toArray());
        $providerResults[] = $scientificCanonical;

        $fusion = $this->fusionService->fuse($providerResults);
        $eligibility = $this->eligibilityResolver->resolve(
            $fusion,
            $scientificEligible,
            $scientificPartial,
        );

        $answer = $synthesisReport->answer;
        $summary = $synthesisReport->conciseSummary;
        $limitations = $synthesisReport->limitations;
        $citations = array_map(
            static fn ($c) => is_object($c) && method_exists($c, 'toArray') ? $c->toArray() : (array) $c,
            $synthesisReport->citations,
        );
        $scientificCitations = $citations;
        $webCitations = [];

        foreach ($fusion->results as $result) {
            foreach ($result->webEvidence as $w) {
                if (is_array($w)) {
                    $webCitations[] = $w;
                }
            }
        }

        // GENERAL_WEB fallback: scientific insufficient + web sufficient → still display answer
        if (! $eligibility->scientificAnswerEligible && $eligibility->webAnswerEligible) {
            $answer = $this->composeGeneralWebAnswer($plan, $fusion, $providerInput->language);
            $summary = $answer;
            $limitations = array_values(array_unique(array_merge($limitations, [
                'Answer based on general web evidence; not scientifically verified',
                'scientific_answer_eligible=false does not suppress overall eligibility when web is sufficient',
            ])));
            $citations = array_merge($webCitations, $citations);
        }

        if (! $eligibility->overallAnswerEligible) {
            $answer = $answer ?? $this->insufficientMessage($providerInput->language);
            $summary = $summary ?? $answer;
        }

        $providersUsed = array_values(array_unique(array_merge(
            $fusion->providersUsed,
            $providersAttempted,
            $searchReport->attemptedSources,
        )));

        $legacy = array_merge($synthesisReport->toArray(), [
            'query_understanding' => $understood->toArray(),
            'knowledge_query_plan' => $plan->toArray(),
            'scientific_search' => $searchReport->toArray(),
            'scientific_validation' => $validationReport->toArray(),
        ]);

        return new UniversalAnswerResult(
            eligibility: $eligibility,
            answer: $answer,
            conciseSummary: $summary,
            providersUsed: $providersUsed,
            limitations: $limitations,
            citations: $citations,
            webCitations: $webCitations,
            scientificCitations: $scientificCitations,
            evidenceSummary: [
                'web' => [
                    'eligible' => $eligibility->webAnswerEligible,
                    'consensus' => $fusion->webConsensus?->toArray(),
                    'count' => count($webCitations),
                ],
                'scientific' => [
                    'eligible' => $eligibility->scientificAnswerEligible,
                    'status' => $synthesisReport->status,
                    'count' => count($scientificCitations),
                ],
            ],
            fusion: $fusion,
            legacy: $legacy,
            observability: [
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'providers_attempted' => $providersAttempted,
                'required_capabilities' => $selection->requiredCapabilities,
                'selected_providers' => $selection->selectedIds(),
                'skipped_providers' => $selection->skipped,
                'scientific_sources' => $searchReport->selectedSources,
                'general_web_fallback' => $this->eligibilityResolver->assertGeneralWebFallbackWorks($eligibility),
            ],
            status: $eligibility->overallAnswerEligible
                ? ($eligibility->answerStatus === 'GENERAL_WEB' ? 'general_web_answer' : $synthesisReport->status)
                : 'insufficient_evidence',
        );
    }

    /**
     * Enrich an existing AgriculturalResearchAgent synthesis payload with eligibility fields.
     *
     * @param  array<string, mixed>  $synthesisPayload
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function enrichLegacySynthesis(array $synthesisPayload, array $input = []): array
    {
        $scientificEligible = (bool) (($synthesisPayload['research_metadata']['evidence_sufficient'] ?? false));
        $scientificPartial = ! $scientificEligible
            && (($synthesisPayload['answer'] ?? null) !== null)
            && ((($synthesisPayload['citations'] ?? []) !== []) || (($synthesisPayload['key_findings'] ?? []) !== []));

        $webOutcome = $this->webSearch->search(
            (string) ($input['query'] ?? $synthesisPayload['research_metadata']['query'] ?? ''),
            (int) ($input['limit'] ?? 5),
        );

        $webResults = [];
        if ($webOutcome->status === WebSearchOutcome::STATUS_SUCCESS) {
            $webResults = $webOutcome->results;
        }

        $canonical = [];
        if ($webResults !== []) {
            $canonical[] = new CanonicalAgriculturalResult(
                providerId: $webOutcome->providerId,
                status: 'success',
                webEvidence: $webResults,
                confidence: 0.45,
                source: $webOutcome->providerId,
                timestamp: now()->toIso8601String(),
            );
        }

        $fusion = $this->fusionService->fuse($canonical);
        $eligibility = $this->eligibilityResolver->resolve($fusion, $scientificEligible, $scientificPartial);

        $payload = array_merge($synthesisPayload, (new UniversalAnswerResult(
            eligibility: $eligibility,
            answer: $synthesisPayload['answer'] ?? null,
            conciseSummary: $synthesisPayload['concise_summary'] ?? null,
            providersUsed: array_values(array_unique(array_merge(
                $fusion->providersUsed,
                ['openalex', 'crossref', 'semantic_scholar'],
            ))),
            limitations: is_array($synthesisPayload['limitations'] ?? null) ? $synthesisPayload['limitations'] : [],
            citations: is_array($synthesisPayload['citations'] ?? null) ? $synthesisPayload['citations'] : [],
            webCitations: $webResults,
            scientificCitations: is_array($synthesisPayload['citations'] ?? null) ? $synthesisPayload['citations'] : [],
            evidenceSummary: [
                'web' => ['eligible' => $eligibility->webAnswerEligible, 'count' => count($webResults)],
                'scientific' => ['eligible' => $eligibility->scientificAnswerEligible],
            ],
            fusion: $fusion,
            legacy: [],
            status: (string) ($synthesisPayload['status'] ?? 'completed'),
        ))->toArray());

        // Web-eligible fallback must still display an answer without claiming scientific verification.
        if ($eligibility->webAnswerEligible && ! $eligibility->scientificAnswerEligible) {
            if (empty($payload['answer'])) {
                $lang = (string) ($input['language'] ?? 'en');
                $payload['answer'] = $this->composeGeneralWebAnswerFromItems($webResults, $lang);
                $payload['concise_summary'] = $payload['answer'];
            }
            $payload['status'] = $eligibility->answerStatus === AnswerStatus::WEB_SUPPORTED_SCIENTIFIC_LIMITED
                ? 'web_supported_scientific_limited'
                : 'general_web_answer';
            $payload['answer_status'] = $eligibility->answerStatus;
            $payload['overall_answer_eligible'] = true;
            $payload['web_answer_eligible'] = true;
            $payload['scientific_answer_eligible'] = false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildProviderInput(KnowledgeQueryPlan $plan, array $input): ProviderQueryInput
    {
        $nq = $plan->normalizedQuery;
        $entities = array_values(array_filter([
            is_array($nq->subject) ? (string) ($nq->subject['value'] ?? $nq->subject['label'] ?? '') : null,
            $nq->crop,
            $nq->scientificName,
            is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['value'] ?? $plan->subjectEntity['label'] ?? '') : null,
        ], static fn ($v): bool => is_string($v) && trim($v) !== ''));

        $capabilities = $this->capabilityResolver->resolve($plan, $input);

        return new ProviderQueryInput(
            query: $nq->originalQuestion !== '' ? $nq->originalQuestion : (string) ($input['query'] ?? ''),
            language: $nq->language ?: (string) ($input['language'] ?? 'en'),
            entities: $entities,
            requiredCapabilities: $capabilities,
            constraints: array_merge($nq->constraints ?? [], is_array($input['constraints'] ?? null) ? $input['constraints'] : []),
            context: is_array($input['context'] ?? null) ? $input['context'] : [],
            limit: (int) ($input['limit'] ?? 10),
            intent: $plan->researchIntent,
        );
    }

    private function retrieveWeb(ProviderQueryInput $input): ?CanonicalAgriculturalResult
    {
        try {
            $outcome = $this->webSearch->search($input->query, $input->limit, $input->constraints);
        } catch (\Throwable $e) {
            Log::warning('Web search isolated failure', ['error' => $e::class]);

            return CanonicalAgriculturalResult::failed('web_search', 'isolated_failure');
        }

        if ($outcome->status === WebSearchOutcome::STATUS_NOT_CONFIGURED) {
            return CanonicalAgriculturalResult::notConfigured('web_search');
        }
        if ($outcome->status === WebSearchOutcome::STATUS_FAILED) {
            return CanonicalAgriculturalResult::failed('web_search', $outcome->error ?? 'failed');
        }
        if ($outcome->status !== WebSearchOutcome::STATUS_SUCCESS || $outcome->results === []) {
            return CanonicalAgriculturalResult::empty('web_search', $outcome->error ?? 'empty');
        }

        return new CanonicalAgriculturalResult(
            providerId: $outcome->providerId,
            status: 'success',
            webEvidence: $outcome->results,
            confidence: 0.45,
            source: $outcome->providerId,
            timestamp: now()->toIso8601String(),
            limitations: ['general_web_evidence_not_scientifically_verified'],
            meta: ['source_role' => SourceRole::WEB_SOURCE],
        );
    }

    /**
     * @param  array<string, mixed>  $search
     * @param  array<string, mixed>  $validation
     */
    private function scientificToCanonical(array $search, array $validation): CanonicalAgriculturalResult
    {
        $scientific = [];
        $official = [];
        foreach ($validation['validated_evidence'] ?? $search['results'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $source = strtolower((string) ($item['source'] ?? $item['source_key'] ?? $item['provider_id'] ?? ''));
            $role = $this->sourceRoleForScientificSource($source, $item);
            $family = match ($role) {
                SourceRole::OFFICIAL_AGRICULTURAL_DATA => 'official',
                SourceRole::CITATION_METADATA => 'citation_metadata',
                default => 'scientific',
            };
            $tagged = array_merge($item, [
                'evidence_family' => $family,
                'source_role' => $role,
            ]);
            if ($role === SourceRole::OFFICIAL_AGRICULTURAL_DATA) {
                $official[] = $tagged;
            } else {
                $scientific[] = $tagged;
            }
        }

        $status = ($scientific !== [] || $official !== []) ? 'success' : 'empty';

        return new CanonicalAgriculturalResult(
            providerId: 'scientific_pipeline',
            status: $status,
            scientificEvidence: $scientific,
            stats: $official,
            confidence: $scientific !== [] ? 0.8 : ($official !== [] ? 0.7 : null),
            source: 'scientific_pipeline',
            timestamp: now()->toIso8601String(),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function sourceRoleForScientificSource(string $source, array $item): string
    {
        if (isset($item['source_role']) && is_string($item['source_role']) && $item['source_role'] !== '') {
            return $item['source_role'];
        }

        return match ($source) {
            'fao_stat', 'fao', 'faostat' => SourceRole::OFFICIAL_AGRICULTURAL_DATA,
            'crossref' => SourceRole::CITATION_METADATA,
            default => SourceRole::SCIENTIFIC_EVIDENCE,
        };
    }

    private function composeGeneralWebAnswer(KnowledgeQueryPlan $plan, $fusion, string $language): string
    {
        $items = [];
        foreach ($fusion->results as $result) {
            foreach ($result->webEvidence as $w) {
                if (is_array($w)) {
                    $items[] = $w;
                }
            }
        }

        return $this->composeGeneralWebAnswerFromItems($items, $language, $fusion->webConsensus?->representativeValue);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function composeGeneralWebAnswerFromItems(array $items, string $language, mixed $representative = null): string
    {
        $isAr = str_starts_with(strtolower($language), 'ar');
        $lines = [];
        if ($representative !== null && $representative !== '') {
            $lines[] = $isAr
                ? 'ملخص من مصادر الويب العامة: '.$representative
                : 'General web consensus summary: '.$representative;
        }

        $n = 0;
        foreach ($items as $item) {
            if ($n >= 3) {
                break;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $snippet = trim((string) ($item['snippet'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            $bit = trim($title.($snippet !== '' ? ' — '.$snippet : ''));
            if ($bit !== '') {
                $lines[] = $bit.($url !== '' ? ' ('.$url.')' : '');
                $n++;
            }
        }

        if ($lines === []) {
            return $isAr
                ? 'تتوفر معلومات عامة من الويب، لكنها غير متحققة علمياً.'
                : 'General web information is available but not scientifically verified.';
        }

        $header = $isAr
            ? "إجابة عامة من الويب (غير متحققة علمياً):\n"
            : "General web answer (not scientifically verified):\n";

        return $header.implode("\n", $lines);
    }

    private function insufficientMessage(string $language): string
    {
        return str_starts_with(strtolower($language), 'ar')
            ? 'الأدلة غير كافية لتقديم إجابة موثوقة.'
            : 'Insufficient evidence to provide a reliable answer.';
    }
}
