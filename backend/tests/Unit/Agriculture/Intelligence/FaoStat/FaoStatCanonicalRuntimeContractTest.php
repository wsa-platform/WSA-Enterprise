<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConsiderationPolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatReadinessReporter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatReadinessState;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatCanonicalRuntimeContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.fao.enabled' => false,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
    }

    public function test_canonical_flag_is_faostat_enabled_not_fao_enabled(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.fao.enabled' => true,
        ]);
        $this->assertFalse(FaoStatRuntimePolicy::isEnabled());
        $this->assertSame(FaoStatConsiderationPolicy::DISABLED, FaoStatConsiderationPolicy::decision());
        $this->assertNotContains('fao_stat', app(ScientificSourceSelector::class)->selectSources($this->plan()));
    }

    public function test_registry_and_universal_bridge_always_use_developer_portal(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.fao.enabled' => true,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
        $this->assertInstanceOf(
            FaoStatDeveloperPortalAdapter::class,
            app(ScientificSourceAdapterRegistry::class)->get('fao_stat'),
        );
        $bridge = app(AgriculturalProviderRegistry::class)->get('fao_stat');
        $this->assertNotNull($bridge);
        $this->assertSame('fao_stat', $bridge->descriptor()->id);
        $this->assertFalse($bridge->descriptor()->enabled);
    }

    public function test_disabled_provider_is_unavailable_not_fenix(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false, 'agricultural_intelligence.fao.enabled' => true]);
        Http::fake();
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('production quantity', 5, [
            'domain' => 'QCL',
            'area' => '10',
            'item' => '20',
            'element' => '2510',
            'year' => '2020',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::DISABLED, $outcome->error);
        Http::assertNothingSent();
    }

    public function test_missing_credentials_are_not_authenticated_without_http(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.username' => '',
            'agricultural_intelligence.faostat.password' => '',
        ]);
        Http::fake();
        $preflight = app(FaoStatReadinessReporter::class)->searchPreflight();
        $this->assertFalse($preflight['ok']);
        $this->assertSame(FaoStatReadinessState::NOT_AUTHENTICATED, $preflight['readiness']);
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('production quantity', 5, [
            'domain' => 'QCL',
            'area' => '10',
            'item' => '20',
            'element' => '2510',
            'year' => '2020',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::NOT_AUTHENTICATED, $outcome->error);
        Http::assertNothingSent();
    }

    /**
     * @dataProvider structuredFilterProvider
     * @param  array<string, string>  $constraints
     * @param  array<string, string>  $expected
     */
    public function test_structured_filters_preserve_domain_item_area_element_year(
        array $constraints,
        array $expected,
    ): void {
        $options = FaoStatSearchOptionsResolver::fromPlan($this->plan($constraints, $expected['crop'] ?? 'crop', $expected['location'] ?? 'place'));
        $this->assertSame('QCL', $options['domain']);
        $this->assertSame($expected['item'], $options['item']);
        $this->assertSame($expected['area'], $options['area']);
        $this->assertSame($expected['element'], $options['element']);
        $this->assertSame($expected['year'], $options['year']);
        $this->assertSame($expected['item'], $options['item_code'] ?? null);
        $this->assertSame($expected['element'], $options['query_element_code'] ?? null);
        $this->assertArrayNotHasKey('page_size', $options);
        $this->assertNotSame('agri', strtolower((string) $options['domain']));
    }

    public function test_missing_filter_stays_incomplete_and_does_not_invent_codes(): void
    {
        Http::fake($this->authAnd([]));
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('production quantity', 5, [
            'domain' => 'QCL',
            'item' => '56',
            'element' => '2510',
            'year' => '2019',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::INCOMPLETE_FILTERS, $outcome->error);
        $this->assertContains('area', $outcome->observability['missing'] ?? []);
    }

    public function test_scholarly_agri_domain_does_not_become_faostat_http_parameter(): void
    {
        $options = FaoStatSearchOptionsResolver::fromPlan($this->plan([
            'domain' => 'agri',
            'item' => '403',
            'area' => '21',
            'element' => '2510',
            'year' => '2018',
        ]));
        $this->assertSame('QCL', $options['domain']);
        $this->assertArrayNotHasKey('page_size', $options);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: array<string, string>}>
     */
    public static function structuredFilterProvider(): array
    {
        return [
            'crop production' => [
                ['item' => '56', 'area' => '21', 'element' => '2510', 'year' => '2019', 'domain' => 'QCL'],
                ['item' => '56', 'area' => '21', 'element' => '2510', 'year' => '2019', 'crop' => 'maize', 'location' => 'brazil'],
            ],
            'livestock product' => [
                ['item' => '867', 'area' => '231', 'element' => '2510', 'year' => '2020', 'domain' => 'QCL'],
                ['item' => '867', 'area' => '231', 'element' => '2510', 'year' => '2020', 'crop' => 'cattle', 'location' => 'united states'],
            ],
            'fishery-adjacent item' => [
                ['item' => '1036', 'area' => '68', 'element' => '2510', 'year' => '2021', 'domain' => 'QCL'],
                ['item' => '1036', 'area' => '68', 'element' => '2510', 'year' => '2021', 'crop' => 'fish', 'location' => 'france'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(array $constraints = [], string $crop = 'crop', string $location = 'place'): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'production quantity',
            normalizedQuestion: 'production quantity',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $crop],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: array_merge([
                'question_type' => 'quantity',
                'scientific_sense' => 'production_quantity',
            ], $constraints),
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $crop],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat', 'openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    /** @return array<string, mixed> */
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
}
