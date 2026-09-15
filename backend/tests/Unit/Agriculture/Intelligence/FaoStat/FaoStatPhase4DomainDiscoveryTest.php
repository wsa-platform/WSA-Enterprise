<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainCatalog;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainStatus;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPortalException;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase4DomainDiscoveryTest extends TestCase
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

    public function test_discovery_does_not_activate_or_verify_domains(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response($this->discoveryPayload(), 200),
        ]));

        $discovered = FaoStatDomainCatalog::discoveredFromPayload(
            app(FaoStatDeveloperPortalClient::class)->getGroupsAndDomains()
        );
        $codes = array_column($discovered, 'domain_code');
        $this->assertContains('QCL', $codes);
        $this->assertContains('RFN', $codes);
        $this->assertContains('RP', $codes);
        $this->assertContains('RL', $codes);
        $this->assertNotContains('AGRI', $codes);
        foreach ($discovered as $row) {
            $this->assertSame(FaoStatDomainStatus::DISCOVERED, $row['status']);
        }
        $this->assertSame(['QCL'], FaoStatDomainCatalog::activatedDomains());
        $this->assertTrue(FaoStatDomainCatalog::isVerified('QCL'));
        $this->assertFalse(FaoStatDomainCatalog::isVerified('RFN'));
        $this->assertFalse(FaoStatDomainCatalog::isActivated('RFN'));
    }

    public function test_unknown_domain_is_not_discovered(): void
    {
        $discovered = FaoStatDomainCatalog::discoveredFromPayload($this->discoveryPayload());
        $this->assertFalse(FaoStatDomainCatalog::isDiscovered('ZZZ', $discovered));
        $this->assertFalse(FaoStatDomainCatalog::isDiscovered('AGRI', $discovered));
    }

    public function test_openalex_agri_is_rejected_as_faostat_domain(): void
    {
        $this->expectException(FaoStatPortalException::class);
        try {
            app(FaoStatDeveloperPortalClient::class)->assertInspectableDomain('agri');
        } catch (FaoStatPortalException $e) {
            $this->assertSame(FaoStatErrorCategory::DOMAIN_NOT_ALLOWED, $e->category);
            throw $e;
        }
    }

    public function test_openalex_domain_agri_does_not_leak_into_faostat_options(): void
    {
        $plan = $this->phase4Plan(
            'What was wheat production in Italy in 2022?',
            'agricultural_economics',
            ['domain' => 'agri'],
        );
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame('QCL', $options['domain']);
        $this->assertNotSame('agri', strtolower((string) $options['domain']));
        $this->assertSame('106', $options['area'] ?? null);
        $this->assertSame('15', $options['item'] ?? null);
        $this->assertSame('2510', $options['element'] ?? null);
    }

    public function test_default_activation_remains_qcl_only(): void
    {
        $config = file_get_contents(config_path('agricultural_intelligence.php'));
        $this->assertNotFalse($config);
        $this->assertStringContainsString("env('FAOSTAT_ENABLED', false)", $config);
        $this->assertStringContainsString("env('FAOSTAT_ALLOWED_DOMAINS', 'QCL')", $config);
        $this->assertStringContainsString("env('FAOSTAT_VERIFIED_DOMAINS', 'QCL')", $config);
        $this->assertSame(['QCL'], FaoStatDomainCatalog::activatedDomains());
    }
}
