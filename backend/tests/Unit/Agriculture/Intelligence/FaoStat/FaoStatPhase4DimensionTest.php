<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainScopedCodeResolver;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatOfficialCodeDimensions;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPortalException;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase4DimensionTest extends TestCase
{
    use FaoStatPhase4Fixtures;

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
        ]);
    }

    public function test_qcl_italy_wheat_codes_remain_verified(): void
    {
        $resolver = app(FaoStatDomainScopedCodeResolver::class);
        $this->assertSame('106', $resolver->resolve('QCL', 'countries', 'Italy')['code']);
        $this->assertSame('15', $resolver->resolve('QCL', 'items', 'Wheat')['code']);
        $this->assertSame('2510', $resolver->resolve('QCL', 'elements', 'Production Quantity')['code']);
        $this->assertSame('2022', $resolver->resolve('QCL', 'years', '2022')['code']);
    }

    public function test_italy_code_is_domain_scoped_and_not_copied_to_rfn(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/codes/countries/RFN*' => Http::response($this->codeListPayload([
                ['code' => '999', 'label' => 'Italy'],
            ]), 200),
        ]));
        $resolver = app(FaoStatDomainScopedCodeResolver::class);
        $qcl = $resolver->resolve('QCL', 'countries', 'Italy');
        $rfn = $resolver->resolve('RFN', 'countries', 'Italy');
        $this->assertSame('106', $qcl['code']);
        $this->assertSame('999', $rfn['code']);
        $this->assertNotSame($qcl['code'], $rfn['code']);
    }

    public function test_areas_endpoint_remains_forbidden(): void
    {
        $this->expectException(FaoStatPortalException::class);
        FaoStatOfficialCodeDimensions::assertOfficial('areas');
    }

    public function test_inspection_allows_discovered_domain_without_activation(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/metadata/RFN' => Http::response(['data' => [['item' => 'ok']]], 200),
            'faostatservices.fao.org/api/v1/en/dimensions/RFN*' => Http::response([
                'data' => [['id' => 'years', 'label' => 'Year']],
            ], 200),
            'faostatservices.fao.org/api/v1/en/codes/years/RFN*' => Http::response($this->codeListPayload([
                ['code' => '2022', 'label' => '2022'],
            ]), 200),
        ]));
        $client = app(FaoStatDeveloperPortalClient::class);
        $this->assertArrayHasKey('data', $client->getMetadata('RFN'));
        $this->assertNotEmpty($client->getDimensions('RFN')['data']);
        $this->assertSame('2022', $client->resolveUniqueCode('years', 'RFN', '2022')['code']);
        $this->expectException(FaoStatPortalException::class);
        $client->getData('RFN', ['year' => '2022']);
    }

    public function test_non_qcl_domain_does_not_inherit_qcl_wheat_maps(): void
    {
        config(['agricultural_intelligence.faostat.allowed_domains' => ['QCL', 'RFN']]);
        $plan = $this->phase4Plan(
            'Wheat production in Italy 2022',
            'agricultural_economics',
            ['domain' => 'RFN', 'area' => '999', 'element' => '3110', 'year' => '2022'],
        );
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame('RFN', $options['domain']);
        $this->assertSame('999', $options['area']);
        $this->assertArrayNotHasKey('item', $options);
        $this->assertSame('3110', $options['element']);
        $this->assertNotSame('15', $options['item'] ?? null);
        $this->assertNotSame('2510', $options['element']);
    }

    public function test_provider_option_isolation_openalex_agri_vs_faostat_qcl(): void
    {
        $plan = $this->phase4Plan('What was wheat production in Italy in 2022?');
        $openAlex = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);
        $faostat = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame('agri', $openAlex['domain'] ?? null);
        $this->assertSame('QCL', $faostat['domain']);
        $this->assertArrayNotHasKey('area', $openAlex);
        $this->assertArrayNotHasKey('item', $openAlex);
        $this->assertArrayHasKey('area', $faostat);
    }
}
