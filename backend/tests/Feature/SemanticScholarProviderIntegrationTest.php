<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests A–S: Semantic Scholar provider, OpenAlex resilience, query drift, answer eligibility.
 */
class SemanticScholarProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function semanticPaper(array $overrides = []): array
    {
        return array_merge([
            'paperId' => 'ss-potato-cultivars-1',
            'title' => 'Potato cultivars and variety classification of Solanum tuberosum',
            'abstract' => 'Comparative study of potato cultivars and commercial varieties for classification.',
            'authors' => [['name' => 'A Researcher']],
            'year' => 2021,
            'url' => 'https://www.semanticscholar.org/paper/ss-potato-cultivars-1',
            'venue' => 'Potato Research',
            'publicationTypes' => ['JournalArticle'],
            'citationCount' => 12,
            'externalIds' => ['DOI' => '10.1000/ss-potato-cultivars'],
            'openAccessPdf' => ['url' => 'https://example.org/ss-potato.pdf'],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function openAlexWork(string $title, string $doi): array
    {
        return [
            'id' => 'https://openalex.org/W'.$doi,
            'display_name' => $title,
            'doi' => 'https://doi.org/'.$doi,
            'publication_year' => 2020,
            'primary_location' => [
                'landing_page_url' => 'https://doi.org/'.$doi,
                'source' => ['display_name' => 'OpenAlex Journal'],
            ],
            'authorships' => [],
            'abstract_inverted_index' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function crossRefWork(string $title, string $doi): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $title,
            'container-title' => ['Crossref Journal'],
            'issued' => ['date-parts' => [[2020]]],
            'author' => [['given' => 'A', 'family' => 'Author']],
            'URL' => 'https://doi.org/'.$doi,
        ];
    }

    /** A — Semantic Scholar success path */
    public function test_a_semantic_scholar_success_normalizes_provider(): void
    {
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
                'data' => [$this->semanticPaper()],
            ], 200),
        ]);

        $outcome = app(SemanticScholarScientificSourceAdapter::class)->search('potato cultivars', 5);
        $this->assertSame('success', $outcome->status);
        $this->assertSame('semantic_scholar', $outcome->sourceKey);
        $this->assertCount(1, $outcome->results);
        $this->assertSame('semantic_scholar', $outcome->results[0]->sourceKey);
        $this->assertSame('10.1000/ss-potato-cultivars', $outcome->results[0]->doi);
        $this->assertSame(12, $outcome->results[0]->relevanceMetadata['citation_count'] ?? null);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.semanticscholar.org/graph/v1/paper/search')
                && str_contains($request->url(), 'query=')
                && str_contains($request->url(), 'fields=')
                && str_contains($request->url(), 'limit=');
        });
    }

    /** B — API key header when configured */
    public function test_b_api_key_sent_as_x_api_key(): void
    {
        Config::set('wsa.semantic_scholar_api_key', 'test-ss-key-not-real');
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 200),
        ]);

        app(SemanticScholarScientificSourceAdapter::class)->search('potato', 3);

        Http::assertSent(function ($request) {
            return ($request->header('x-api-key')[0] ?? null) === 'test-ss-key-not-real';
        });
    }

    /** C — missing API key does not crash */
    public function test_c_no_api_key_still_searches(): void
    {
        Config::set('wsa.semantic_scholar_api_key', '');
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response([
                'data' => [$this->semanticPaper()],
            ], 200),
        ]);

        $outcome = app(SemanticScholarScientificSourceAdapter::class)->search('potato cultivars', 3);
        $this->assertSame('success', $outcome->status);
        Http::assertSent(function ($request) {
            return empty($request->header('x-api-key'));
        });
    }

    /** D — API key never appears in logs / observability */
    public function test_d_api_key_not_logged_or_in_observability(): void
    {
        Config::set('wsa.semantic_scholar_api_key', 'super-secret-ss-key-xyz');
        Log::spy();
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 429, [
                'Retry-After' => '0',
            ]),
        ]);

        $outcome = app(SemanticScholarScientificSourceAdapter::class)->search('potato', 2);
        $payload = json_encode($outcome->toArray());
        $this->assertStringNotContainsString('super-secret-ss-key-xyz', (string) $payload);
        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    /** E — 429 bounded retry max 3 */
    public function test_e_semantic_scholar_429_retries_max_three(): void
    {
        $calls = 0;
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => function () use (&$calls) {
                $calls++;

                return Http::response(['error' => 'rate'], 429, ['Retry-After' => '0']);
            },
        ]);

        $outcome = app(SemanticScholarScientificSourceAdapter::class)->search('potato', 2);
        $this->assertSame(3, $calls);
        $this->assertSame('unavailable', $outcome->status);
        $this->assertSame('rate_limited', $outcome->error);
        $this->assertSame(2, $outcome->observability['retry_count'] ?? null);
    }

    /** F — SS failure does not stop OA+CR */
    public function test_f_semantic_scholar_failure_other_providers_continue(): void
    {
        Http::fake([
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['error' => 'boom'], 503),
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork('Ginger cultivation practices', '10.1000/oa-ginger'),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [
                $this->crossRefWork('Ginger cultivation field study', '10.1000/cr-ginger'),
            ]]], 200),
        ]);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $report = app(AgriculturalScientificSearchService::class)->search($plan);

        $this->assertContains('openalex', $report->successfulSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertNotSame('all_sources_failed', $report->status);
        $this->assertGreaterThan(0, count($report->results));
    }

    /** G — OpenAlex 429 retries then other providers continue */
    public function test_g_openalex_429_bounded_retry_others_continue(): void
    {
        $oaCalls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$oaCalls) {
                $oaCalls++;

                return Http::response(['results' => []], 429, ['Retry-After' => '0']);
            },
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [
                $this->crossRefWork('Zingiber officinale cultivation', '10.1000/cr-oa429'),
            ]]], 200),
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => [
                $this->semanticPaper([
                    'title' => 'Zingiber officinale cultivation requirements',
                    'externalIds' => ['DOI' => '10.1000/ss-oa429'],
                ]),
            ]], 200),
        ]);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $report = app(AgriculturalScientificSearchService::class)->search($plan);

        $this->assertSame(3, $oaCalls, 'OpenAlex adapter retries up to 3 attempts on 429');
        $this->assertContains('openalex', $report->failedSources);
        $this->assertTrue(
            in_array('crossref', $report->successfulSources, true)
            || in_array('semantic_scholar', $report->successfulSources, true)
        );
        $this->assertNotSame('all_sources_failed', $report->status);
    }

    /** H — provider isolation matrix */
    public function test_h_provider_isolation_matrix(): void
    {
        $cases = [
            ['oa' => 503, 'cr' => 200, 'ss' => 200],
            ['oa' => 200, 'cr' => 503, 'ss' => 200],
            ['oa' => 200, 'cr' => 200, 'ss' => 503],
        ];

        foreach ($cases as $case) {
            Http::fake([
                'api.openalex.org/works*' => Http::response(
                    $case['oa'] === 200
                        ? ['results' => [$this->openAlexWork('Tomato heat stress physiology', '10.1000/iso-oa')]]
                        : ['error' => 'fail'],
                    $case['oa']
                ),
                'api.crossref.org/works*' => Http::response(
                    $case['cr'] === 200
                        ? ['message' => ['items' => [$this->crossRefWork('Tomato heat stress physiology', '10.1000/iso-cr')]]]
                        : ['error' => 'fail'],
                    $case['cr']
                ),
                'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(
                    $case['ss'] === 200
                        ? ['data' => [$this->semanticPaper([
                            'title' => 'Tomato heat stress physiology',
                            'externalIds' => ['DOI' => '10.1000/iso-ss'],
                        ])]]
                        : ['error' => 'fail'],
                    $case['ss']
                ),
            ]);

            $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
                'query' => 'ما تأثير الحرارة على الطماطم؟',
            ]);
            $report = app(AgriculturalScientificSearchService::class)->search($plan);
            $this->assertNotSame('all_sources_failed', $report->status, json_encode($case));
            $this->assertGreaterThan(0, count($report->results), json_encode($case));
        }
    }

    /** I — dedup across three providers */
    public function test_i_deduplication_across_three_providers(): void
    {
        $doi = '10.1000/shared-tri-provider';
        $results = [
            new ScientificSearchResult('openalex', 'oa1', 'Shared paper title', [], 2020, $doi, 'https://doi.org/'.$doi, null, null, ['openalex']),
            new ScientificSearchResult('crossref', 'cr1', 'Shared paper title', [], 2020, $doi, 'https://doi.org/'.$doi, null, null, ['crossref']),
            new ScientificSearchResult('semantic_scholar', 'ss1', 'Shared paper title', [], 2020, $doi, 'https://doi.org/'.$doi, null, null, ['semantic_scholar']),
        ];
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate($results);
        $this->assertCount(1, $deduped);
        $this->assertEqualsCanonicalizing(
            ['openalex', 'crossref', 'semantic_scholar'],
            $deduped[0]->foundBySources
        );
    }

    /** J — potato varieties intent preserved */
    public function test_j_potato_varieties_query_intent(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي اصناف البطاطا',
        ]);
        $this->assertTrue(
            $plan->researchIntent === 'varieties'
            || ($plan->normalizedQuery->constraints['question_type'] ?? null) === 'classification'
            || $plan->normalizedQuery->cropId !== null
        );
        $joined = mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
        $this->assertTrue(
            str_contains($joined, 'variet') || str_contains($joined, 'cultivar'),
            $joined
        );
        $this->assertFalse(str_contains($joined, 'potato oil'));
        $this->assertFalse(str_contains($joined, 'potato disease') && ! str_contains($joined, 'cultivar'));
    }

    /** K — Cucurbitaceae family members */
    public function test_k_cucurbitaceae_family_variants(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي نبات العائلة القرعية',
        ]);
        $joined = mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
        $this->assertStringContainsString('cucurbitaceae', $joined);
        $this->assertTrue(
            str_contains($joined, 'species')
            || str_contains($joined, 'plants')
            || str_contains($joined, 'members')
            || str_contains($joined, 'classification'),
            $joined
        );
        $this->assertFalse(str_contains($joined, 'hydroponic cucumber'));
        $this->assertFalse(str_contains($joined, 'greenhouse cucumber'));
    }

    /** L — Egypt land classification national-first */
    public function test_l_egypt_land_national_first_variants(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي انواع الاراضي الزراعيه فى مصر',
        ]);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertSame('land_classification', $plan->normalizedQuery->constraints['scientific_sense'] ?? $plan->researchIntent);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('egypt', $joined);
        $this->assertTrue(
            str_contains($joined, 'land') || str_contains($joined, 'soil'),
            $joined
        );
        $this->assertStringNotContainsString('south sinai', $joined);
    }

    /** M — English equivalent Egypt land */
    public function test_m_english_egypt_land_equivalent_intent(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertTrue(
            ($plan->normalizedQuery->constraints['scientific_sense'] ?? null) === 'land_classification'
            || $plan->researchIntent === 'land_classification'
        );
    }

    /** N — supporting answer eligible when multiple strong supporting */
    public function test_n_supporting_answer_eligible(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $composer = app(AnswerComposer::class);
        $items = [
            $this->usableEvidence(
                'sup-1',
                'Agricultural land types in Egypt include alluvial Nile soils and desert sandy soils.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Agricultural land types across Egypt national inventory',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'doi' => '10.1000/egypt-land-sup-1',
                    'url' => 'https://doi.org/10.1000/egypt-land-sup-1',
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'sense_coverage' => true,
                    'factor_coverage' => 0.8,
                    'answer_eligible' => true,
                ],
            ),
            $this->usableEvidence(
                'sup-2',
                'Soil classification of Egyptian agricultural lands covers calcareous and saline soils.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Soil classification of Egyptian agricultural lands',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'doi' => '10.1000/egypt-land-sup-2',
                    'url' => 'https://doi.org/10.1000/egypt-land-sup-2',
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'sense_coverage' => true,
                    'factor_coverage' => 0.75,
                    'answer_eligible' => true,
                ],
            ),
        ];
        $report = $composer->compose($plan, $this->validationReport($items));
        $this->assertTrue($report->performed);
        $this->assertSame('supported_answer', $report->researchMetadata['sufficiency_mode'] ?? null);
        $this->assertGreaterThanOrEqual(1, count($report->citations));
        $this->assertStringNotContainsString('لم يتم العثور على دليل علمي مباشر كافٍ', $report->conciseSummary);
    }

    /** O — one weak supporting is insufficient */
    public function test_o_supporting_insufficient(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $composer = app(AnswerComposer::class);
        $report = $composer->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'weak-1',
                'Greenhouse cucumber production notes without national land typology.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Greenhouse notes',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'entity_matched' => false,
                    'topic_matched' => false,
                    'sense_coverage' => false,
                    'factor_coverage' => 0.05,
                    'answer_eligible' => false,
                ],
            ),
        ]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertSame(0, (int) ($report->researchMetadata['direct_evidence_count'] ?? 0));
    }

    /** P — potato oil irrelevant for varieties */
    public function test_p_irrelevant_potato_oil_for_varieties(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي اصناف البطاطا',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $oil = $assessor->assess(
            $plan,
            'Potato essential oil extraction and starch chemistry',
            'This study analyzes potato oil and starch without cultivar classification.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $oil['directness']);
        $this->assertTrue(in_array($oil['directness'], [
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
        ], true));
    }

    /** Q — geographic mismatch */
    public function test_q_geographic_mismatch_not_primary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);
        $evl = app(EvidenceVerificationLayer::class);
        $result = new ScientificSearchResult(
            'semantic_scholar',
            'geo-india',
            'Agricultural land types and soil classification in India',
            [],
            2020,
            '10.1000/india-land',
            'https://doi.org/10.1000/india-land',
            'National inventory of agricultural land types across India; study geography is India only.',
            'Soil Science',
            ['semantic_scholar'],
            ['countries_of_study' => ['in', 'India']],
        );
        $assessed = $evl->assess($plan, $result);
        $this->assertTrue(in_array($assessed['directness'], [
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
        ], true), 'directness='.$assessed['directness']);
        $this->assertFalse($evl->isPrimaryCitationEligible($assessed['directness']));
    }

    /** R — citation count secondary to relevance */
    public function test_r_citation_count_secondary_to_relevance(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي اصناف البطاطا',
        ]);
        $highCite = new ScientificSearchResult(
            'semantic_scholar',
            'high',
            'Potato starch and industrial oil chemistry review',
            [],
            2019,
            '10.1000/high-cite',
            'https://doi.org/10.1000/high-cite',
            'Potato starch and oil with 500 citations but no cultivar inventory.',
            'Food Chem',
            ['semantic_scholar'],
            ['citation_count' => 500],
        );
        $lowCite = new ScientificSearchResult(
            'semantic_scholar',
            'low',
            'Potato cultivars and variety classification of Solanum tuberosum',
            [],
            2021,
            '10.1000/low-cite',
            'https://doi.org/10.1000/low-cite',
            'Comparative potato cultivars and commercial varieties classification.',
            'Potato Research',
            ['semantic_scholar'],
            ['citation_count' => 10],
        );
        $ranked = app(ScientificResultRanker::class)->rank(
            'potato cultivars varieties',
            [$highCite, $lowCite],
            $plan,
        );
        $this->assertNotEmpty($ranked);
        $this->assertSame('10.1000/low-cite', $ranked[0]->doi);
    }

    /** S — no evidence → insufficient, no fabrication */
    public function test_s_no_evidence_insufficient(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence', 'no_search_results']);
        $this->assertSame([], $report->citations);
        $this->assertSame([], $report->keyFindings);
        $this->assertStringContainsString(
            'غير كافية',
            $report->conciseSummary.' '.$report->answer
        );
    }

    public function test_selector_uses_semantic_scholar_not_consensus(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $sources = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertSame(['openalex', 'crossref', 'semantic_scholar'], $sources);
        $this->assertNotContains('consensus', $sources);
        $keys = app(ScientificSourceAdapterRegistry::class)->registeredSourceKeys();
        $this->assertContains('semantic_scholar', $keys);
        $this->assertContains('consensus', $keys);
    }

    private function usableEvidence(
        string $id,
        string $text,
        string $relationship,
        array $opts = [],
    ): ScientificEvidenceItem {
        $directness = $opts['directness'] ?? ScientificEvidenceDirectnessAssessor::SUPPORTING;
        $factors = [
            'evidence_directness' => $directness,
            'entity_matched' => $opts['entity_matched'] ?? null,
            'topic_matched' => $opts['topic_matched'] ?? null,
            'sense_coverage' => $opts['sense_coverage'] ?? null,
            'factor_coverage' => $opts['factor_coverage'] ?? null,
            'answer_eligible' => $opts['answer_eligible'] ?? null,
            'verification_label' => $opts['verification_label'] ?? null,
        ];
        $factors = array_filter($factors, static fn ($v) => $v !== null);

        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'semantic_scholar',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $opts['publicationTitle'] ?? 'Evidence '.$id,
            authors: ['A Researcher'],
            institution: 'University of Agriculture',
            journal: 'Ag Journal',
            doi: $opts['doi'] ?? ('10.1000/'.$id),
            url: $opts['url'] ?? ('https://doi.org/10.1000/'.$id),
            publicationYear: 2022,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'soil',
            claimTopic: $opts['claimTopic'] ?? 'classification',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 80.0,
            qualityFactors: $factors,
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'peer_reviewed_journal',
                'evidence_directness' => $directness,
            ],
            hasConflict: false,
            conditions: null,
        );
    }

    /** @param list<ScientificEvidenceItem> $items */
    private function validationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $items === [] ? 'no_valid_evidence' : 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $items !== [],
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }
}
