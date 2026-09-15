<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainCatalog;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainStatus;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainVerificationReport;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainVerifier;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPortalException;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSupportState;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase4DomainVerificationTest extends TestCase
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
            'agricultural_intelligence.faostat.verified_domains' => ['QCL'],
        ]);
    }

    public function test_qcl_reaches_activatable_with_verified_probe(): void
    {
        Http::fake($this->qclVerificationRoutes());
        $report = app(FaoStatDomainVerifier::class)->verify('QCL');
        $this->assertSame(FaoStatDomainStatus::ACTIVATABLE, $report->status);
        $this->assertTrue($report->activatable);
        $this->assertSame('6609520', $report->live['value'] ?? null);
        $this->assertSame('2510', $report->live['query_element_code'] ?? null);
        $this->assertSame('5510', $report->live['response_element_code'] ?? null);
        $this->assertNotSame($report->live['query_element_code'], $report->live['response_element_code']);
        $this->assertSame(FaoStatSupportState::SUPPORTED, $report->claimMapping['support_state'] ?? null);
        $this->assertSame('faostatservices.fao.org', $report->provenance['host'] ?? null);
        $this->assertArrayNotHasKey('AccessToken', $report->toArray());
        $this->assertStringNotContainsString('portal-pass', json_encode($report->toArray()) ?: '');
    }

    public function test_unknown_domain_is_not_verified(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response($this->discoveryPayload(), 200),
        ]));
        $report = app(FaoStatDomainVerifier::class)->verify('ZZZ');
        $this->assertSame(FaoStatDomainStatus::NOT_VERIFIED, $report->status);
        $this->assertSame('domain_not_in_discovery', $report->reason);
        $this->assertFalse($report->activatable);
    }

    public function test_unverified_domain_cannot_activate(): void
    {
        $report = new FaoStatDomainVerificationReport(
            domain: 'RFN',
            status: FaoStatDomainStatus::CODES_VERIFIED,
            reason: 'no_safe_live_probe',
            discovered: true,
            metadataVerified: true,
            dimensionsVerified: true,
            codesVerified: true,
            liveQueryVerified: false,
            claimMappingVerified: false,
            activatable: false,
        );
        $this->expectException(FaoStatPortalException::class);
        try {
            FaoStatDomainCatalog::assertActivatable('RFN', $report);
        } catch (FaoStatPortalException $e) {
            $this->assertSame(FaoStatErrorCategory::DOMAIN_NOT_VERIFIED, $e->category);
            throw $e;
        }
    }

    public function test_candidate_without_live_probe_is_not_activatable(): void
    {
        Http::fake($this->rfnInspectionRoutes());
        $report = app(FaoStatDomainVerifier::class)->verify('RFN');
        $this->assertTrue($report->discovered);
        $this->assertTrue($report->metadataVerified);
        $this->assertTrue($report->dimensionsVerified);
        $this->assertTrue($report->codesVerified);
        $this->assertFalse($report->liveQueryVerified);
        $this->assertFalse($report->activatable);
        $this->assertSame(FaoStatDomainStatus::CODES_VERIFIED, $report->status);
        $this->assertSame('no_safe_live_probe', $report->reason);
        $this->assertFalse(FaoStatDomainCatalog::isActivated('RFN'));
    }

    public function test_candidate_with_verified_live_probe_can_reach_activatable_without_default_activation(): void
    {
        Http::fake($this->rfnInspectionRoutes([
            'faostatservices.fao.org/api/v1/en/data/RFN*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [[
                    'Domain Code' => 'RFN',
                    'Domain' => 'Fertilizers by Nutrient',
                    'Area Code' => '999',
                    'Area' => 'Italy',
                    'Element Code' => '6110',
                    'Element' => 'Agricultural Use',
                    'Item Code' => '3102',
                    'Item' => 'Nutrient nitrogen N (total)',
                    'Year Code' => '2022',
                    'Year' => '2022',
                    'Unit' => 't',
                    'Value' => '42',
                    'Flag' => 'A',
                    'Flag Description' => 'Official value',
                    'Note' => '',
                ]],
            ], 200),
        ]));

        $report = app(FaoStatDomainVerifier::class)->verify('RFN', [
            'area' => '999',
            'item' => '3102',
            'element' => '3110',
            'year' => '2022',
        ]);
        $this->assertSame(FaoStatDomainStatus::ACTIVATABLE, $report->status);
        $this->assertTrue($report->activatable);
        $this->assertSame(['QCL'], FaoStatDomainCatalog::activatedDomains());
        $this->expectException(FaoStatPortalException::class);
        app(FaoStatDeveloperPortalClient::class)->getData('RFN', ['area' => '999']);
    }

    public function test_empty_live_result_is_empty_result_not_evidence(): void
    {
        Http::fake($this->rfnInspectionRoutes([
            'faostatservices.fao.org/api/v1/en/data/RFN*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [],
            ], 200),
        ]));
        $report = app(FaoStatDomainVerifier::class)->verify('RFN', [
            'area' => '999',
            'year' => '2022',
        ]);
        $this->assertSame(FaoStatErrorCategory::EMPTY_RESULT, $report->reason);
        $this->assertFalse($report->activatable);
        $this->assertSame(0, $report->live['observation_count'] ?? null);
    }

    public function test_authentication_failure_is_not_empty_result(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'User does not exist.'], 400),
        ]);
        $report = app(FaoStatDomainVerifier::class)->verify('QCL');
        $this->assertSame(FaoStatDomainStatus::NOT_VERIFIED, $report->status);
        $this->assertSame(FaoStatErrorCategory::AUTHENTICATION_ERROR, $report->reason);
        $this->assertNotSame(FaoStatErrorCategory::EMPTY_RESULT, $report->reason);
        $this->assertStringNotContainsString('portal-pass', $report->reason);
    }

    public function test_authorization_failure_is_not_empty_result(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response(['message' => 'denied'], 401),
        ]);
        $report = app(FaoStatDomainVerifier::class)->verify('QCL');
        $this->assertSame(FaoStatErrorCategory::AUTHORIZATION_ERROR, $report->reason);
        $this->assertNotSame(FaoStatErrorCategory::EMPTY_RESULT, $report->reason);
    }

    public function test_search_still_rejects_unactivated_domain(): void
    {
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'RFN',
            'area' => '999',
            'item' => '3102',
            'element' => '3110',
            'year' => '2022',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::DOMAIN_NOT_ALLOWED, $outcome->error);
    }

    public function test_tokens_are_not_persisted_during_verification(): void
    {
        Http::fake($this->qclVerificationRoutes());
        app(FaoStatDomainVerifier::class)->verify('QCL');
        foreach (Cache::get('faostat.groupsanddomains.en') ?? [] as $ignored) {
            // cache may store discovery payload only
        }
        $encoded = json_encode(Cache::get('faostat.groupsanddomains.en'));
        $this->assertStringNotContainsString('test-access-token', (string) $encoded);
        $this->assertStringNotContainsString('test-refresh-token', (string) $encoded);
        $this->assertNull(Cache::get('faostat.token'));
        $this->assertNull(Cache::get('faostat.access_token'));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function rfnInspectionRoutes(array $extra = []): array
    {
        return $this->authAnd(array_merge([
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response($this->discoveryPayload(), 200),
            'faostatservices.fao.org/api/v1/en/metadata/RFN' => Http::response(['metadata' => ['domain' => 'RFN'], 'data' => [['item' => 'ok']]], 200),
            'faostatservices.fao.org/api/v1/en/dimensions/RFN*' => Http::response([
                'data' => [
                    ['id' => 'countries', 'label' => 'Area'],
                    ['id' => 'items', 'label' => 'Item'],
                    ['id' => 'elements', 'label' => 'Element'],
                    ['id' => 'years', 'label' => 'Year'],
                ],
            ], 200),
            'faostatservices.fao.org/api/v1/en/codes/countries/RFN*' => Http::response($this->codeListPayload([
                ['code' => '999', 'label' => 'Italy'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/items/RFN*' => Http::response($this->codeListPayload([
                ['code' => '3102', 'label' => 'Nutrient nitrogen N (total)'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/elements/RFN*' => Http::response($this->codeListPayload([
                ['code' => '3110', 'label' => 'Agricultural Use'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/years/RFN*' => Http::response($this->codeListPayload([
                ['code' => '2022', 'label' => '2022'],
            ]), 200),
        ], $extra));
    }
}
