<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatEvidenceType;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatObservationRelevanceGate;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase3FixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.base_url' => 'https://faostatservices.fao.org/api/v1',
            'agricultural_intelligence.faostat.allowed_host' => 'faostatservices.fao.org',
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.lang' => 'en',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.fao.enabled' => false,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
    }

    public function test_openalex_agri_domain_does_not_reach_faostat_and_qcl_is_used(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => function ($request) {
                $this->assertStringContainsString('/data/QCL', $request->url());
                $this->assertStringNotContainsString('/data/AGRI', strtoupper($request->url()));
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $this->assertSame('106', $query['area'] ?? null);
                $this->assertSame('15', $query['item'] ?? null);
                $this->assertSame('2510', $query['element'] ?? null);
                $this->assertSame('2022', $query['year'] ?? null);
                $this->assertNotSame('agri', strtolower((string) ($query['domain'] ?? '')));

                return Http::response([
                    'metadata' => ['output_type' => 'OBJECTS'],
                    'data' => [$this->italyWheatRow()],
                ], 200);
            },
        ]));

        $plan = $this->livePlan();
        $openAlexOptions = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);
        $this->assertSame('agri', $openAlexOptions['domain'] ?? null);

        $faostatOptions = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame('QCL', $faostatOptions['domain'] ?? null);
        $this->assertNotSame('agri', strtolower((string) ($faostatOptions['domain'] ?? '')));

        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        $this->assertContains('fao_stat', $report->successfulSources);
        $this->assertNotContains('fao_stat', $report->emptySources);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/data/QCL'));
        Http::assertNotSent(fn ($request): bool => str_contains(strtoupper($request->url()), '/DATA/AGRI'));
    }

    public function test_location_year_and_qcl_codes_resolve_for_italy_wheat_production(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'What was wheat production in Italy in 2022?',
        ]);
        $this->assertSame('Italy', $understood->location);
        $this->assertSame('2022', $understood->constraints['year'] ?? null);
        $this->assertSame('wheat', $understood->cropId);

        $plan = $this->livePlan();
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame('106', $options['area'] ?? null);
        $this->assertSame('15', $options['item'] ?? null);
        $this->assertSame('2510', $options['element'] ?? null);
        $this->assertSame('2022', $options['year'] ?? null);
        $this->assertSame('QCL', $options['domain'] ?? null);
    }

    public function test_generic_location_parser_does_not_consume_trailing_in(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'What was wheat production in France in 2022?',
        ]);
        $this->assertSame('France', $understood->location);
        $this->assertNotSame('France in', $understood->location);
    }

    public function test_statistical_need_and_claim_support_without_scholarly_metadata(): void
    {
        $plan = $this->livePlan();
        $this->assertTrue(app(FaoStatObservationRelevanceGate::class)->hasStatisticalInformationNeed($plan));
        $this->assertTrue(FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan));
        $this->assertFalse(FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed(
            app(ResearchPlanner::class)->planKnowledgeQuery([
                'query' => 'What hydroponics production system is best for tomato?',
            ]),
        ));

        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]));

        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        $this->assertNotEmpty($report->deduplicatedResults);
        $row = $report->deduplicatedResults[0];
        $this->assertTrue(app(FaoStatObservationRelevanceGate::class)->assess($plan, $row)['relevant']);
        $this->assertSame(FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE, $row->relevanceMetadata['evidence_type'] ?? null);
        $this->assertTrue($row->relevanceMetadata['not_literature'] ?? false);
        $this->assertSame('2510', $row->relevanceMetadata['query_element_code'] ?? null);
        $this->assertSame('5510', $row->relevanceMetadata['response_element_code'] ?? null);
        $this->assertNotSame($row->relevanceMetadata['query_element_code'], $row->relevanceMetadata['response_element_code']);
        $this->assertNull($row->doi);
        $this->assertSame([], $row->authors);
        $this->assertNull($row->journal);
        $this->assertNull($row->publicationYear);
        $this->assertStringContainsString('faostatservices.fao.org', (string) $row->canonicalUrl);

        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $row);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $support['relationship']);
        $this->assertSame(FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE, $support['factors']['evidence_type'] ?? null);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $report);
        $this->assertNotEmpty($validation->validatedEvidence);
        $item = $validation->validatedEvidence[0];
        $this->assertSame('fao_stat', $item->sourceKey);
        $this->assertSame('official_statistics', $item->sourceType);
        $this->assertNull($item->doi);
        $this->assertNull($item->journal);
        $this->assertSame([], $item->authors);
        $this->assertTrue($item->qualityFactors['not_literature'] ?? false);
    }

    public function test_portal_remains_canonical_when_developer_portal_is_disabled(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.fao.enabled' => true,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->assertInstanceOf(
            FaoStatDeveloperPortalAdapter::class,
            app(ScientificSourceAdapterRegistry::class)->get('fao_stat'),
        );
        $this->assertFalse(filter_var(config('agricultural_intelligence.faostat.enabled', false), FILTER_VALIDATE_BOOL));
        $this->assertNotContains('fao_stat', app(ScientificSourceSelector::class)->selectSources($this->livePlan()));
    }

    public function test_portal_adapter_is_used_only_when_enabled(): void
    {
        $this->assertInstanceOf(
            FaoStatDeveloperPortalAdapter::class,
            app(ScientificSourceAdapterRegistry::class)->get('fao_stat'),
        );
    }

    public function test_scholarly_openalex_options_still_use_agri_domain(): void
    {
        $plan = $this->livePlan();
        $options = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);
        $this->assertSame('agri', $options['domain'] ?? null);
        $this->assertArrayNotHasKey('area', $options);
        $this->assertArrayNotHasKey('item', $options);
        $this->assertArrayNotHasKey('element', $options);
    }

    private function livePlan(): KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was wheat production in Italy in 2022?',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function authAnd(array $extra): array
    {
        return array_merge([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response([
                'AuthenticationResult' => [
                    'AccessToken' => 'test-access-token',
                    'RefreshToken' => 'test-refresh-token',
                    'ExpiresIn' => 3600,
                    'TokenType' => 'Bearer',
                ],
            ], 200),
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function italyWheatRow(): array
    {
        return [
            'Domain Code' => 'QCL',
            'Domain' => 'Crops and livestock products',
            'Area Code' => '106',
            'Area' => 'Italy',
            'Element Code' => '5510',
            'Element' => 'Production',
            'Item Code' => '15',
            'Item' => 'Wheat',
            'Year Code' => '2022',
            'Year' => '2022',
            'Unit' => 't',
            'Value' => '6609520',
            'Flag' => 'A',
            'Flag Description' => 'Official value',
            'Note' => '',
        ];
    }
}
