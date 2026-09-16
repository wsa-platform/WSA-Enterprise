<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatActivationPolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatCircuitBreaker;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimEvidenceFusion;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSemantics;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConfigurationValidator;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainActivationState;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainCatalog;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatEvidenceType;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatLiveValidationRegistry;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatOperationalLogger;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatOperationalStatus;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPortalException;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatReadinessReporter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatReadinessState;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSupportState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FaoStatPhase5ProductionReadinessTest extends TestCase
{
    use FaoStatPhase4Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->app->forgetInstance(FaoStatDeveloperPortalTokenManager::class);
        $this->app->forgetInstance(FaoStatCircuitBreaker::class);
        $this->app->forgetInstance(FaoStatDeveloperPortalClient::class);
        $this->app->forgetInstance(FaoStatReadinessReporter::class);
        $this->app->forgetInstance(FaoStatDeveloperPortalAdapter::class);
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        app(FaoStatCircuitBreaker::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.base_url' => 'https://faostatservices.fao.org/api/v1',
            'agricultural_intelligence.faostat.allowed_host' => 'faostatservices.fao.org',
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.lang' => 'en',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.faostat.verified_domains' => ['QCL'],
            'agricultural_intelligence.faostat.max_transient_retries' => 1,
            'agricultural_intelligence.faostat.circuit_failure_threshold' => 5,
            'agricultural_intelligence.faostat.circuit_open_seconds' => 30,
        ]);
    }

    public function test_configuration_defaults_remain_safe(): void
    {
        $config = file_get_contents(config_path('agricultural_intelligence.php'));
        $this->assertNotFalse($config);
        $this->assertStringContainsString("env('FAOSTAT_ENABLED', false)", $config);
        $this->assertStringContainsString("env('FAOSTAT_ALLOWED_DOMAINS', 'QCL')", $config);
        $this->assertStringContainsString("env('FAOSTAT_VERIFIED_DOMAINS', 'QCL')", $config);
        $this->assertStringContainsString("'live_validated_domains' => ['QCL']", $config);
        $this->assertSame(['QCL'], FaoStatActivationPolicy::activeDomains());
        $this->assertFalse(FaoStatActivationPolicy::isActive('RFN'));
        $this->assertSame('PENDING_BLOCKED', FaoStatLiveValidationRegistry::rfnLiveValidationStatus());
    }

    public function test_unverified_and_invalid_domains_cannot_become_active(): void
    {
        config(['agricultural_intelligence.faostat.allowed_domains' => ['AGRI', 'ZZZ', 'UNKNOWN', 'not-a-domain', 'QCL']]);
        $this->assertSame(['QCL'], FaoStatActivationPolicy::activeDomains());
        $this->assertSame(FaoStatDomainActivationState::FAILED, FaoStatActivationPolicy::activationState('AGRI'));
        $this->assertSame(FaoStatDomainActivationState::DISABLED, FaoStatActivationPolicy::activationState('ZZZ'));
        $sanitized = FaoStatConfigurationValidator::sanitizeDomainList(['AGRI', 'agri', 'not-a-domain'], false);
        $this->assertSame([], $sanitized['accepted']);
        $this->assertContains('AGRI', $sanitized['rejected']);
    }

    public function test_rfn_remains_inactive_even_when_config_tries_to_activate_it(): void
    {
        config([
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL', 'RFN'],
            'agricultural_intelligence.faostat.verified_domains' => ['QCL', 'RFN'],
        ]);
        $this->assertSame(['QCL'], FaoStatActivationPolicy::activeDomains());
        $this->assertSame(FaoStatDomainActivationState::ACTIVE, FaoStatActivationPolicy::activationState('QCL'));
        $this->assertSame(FaoStatDomainActivationState::DISABLED, FaoStatActivationPolicy::activationState('RFN'));
        $this->assertFalse(FaoStatLiveValidationRegistry::isLiveValidated('RFN'));
        $this->assertSame(['QCL'], FaoStatDomainCatalog::activatedDomains());
        $this->expectException(FaoStatPortalException::class);
        app(FaoStatDeveloperPortalClient::class)->getData('RFN', ['area' => '999']);
    }

    public function test_qcl_search_still_works_when_rfn_is_pending(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]));
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame(FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE, $outcome->observability['evidence_type'] ?? null);
        $this->assertSame('2510', $outcome->results[0]->relevanceMetadata['query_element_code'] ?? null);
        $this->assertSame('5510', $outcome->results[0]->relevanceMetadata['response_element_code'] ?? null);
        $this->assertNotSame(
            $outcome->results[0]->relevanceMetadata['query_element_code'],
            $outcome->results[0]->relevanceMetadata['response_element_code'],
        );
        $this->assertSame('FAOSTAT', $outcome->results[0]->rawMetadata['provenance']['source'] ?? null);
    }

    public function test_empty_result_is_not_upstream_failure(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [],
            ], 200),
        ]));
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::EMPTY_RESULT, $outcome->error);
    }

    public function test_authentication_failures_are_classified(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'User does not exist.'], 400),
        ]);
        $auth = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $auth->status);
        $this->assertSame(FaoStatErrorCategory::AUTHENTICATION_ERROR, $auth->error);
    }

    public function test_authorization_failures_are_classified(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response(['message' => 'denied'], 401),
        ]));
        $denied = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $denied->status);
        $this->assertSame(FaoStatErrorCategory::AUTHORIZATION_ERROR, $denied->error);
    }

    public function test_timeout_errors_are_classified(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/data/QCL*' => function () {
                throw new ConnectionException('cURL error 28: timed out');
            },
        ]);
        $timeout = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(FaoStatErrorCategory::TIMEOUT, $timeout->error);
    }

    public function test_network_errors_are_classified(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/data/QCL*' => function () {
                throw new ConnectionException('cURL error 7: Failed to connect');
            },
        ]);
        $network = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(FaoStatErrorCategory::NETWORK_ERROR, $network->error);
    }

    public function test_upstream_500_retries_once_and_does_not_storm(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response(['error' => 'boom'], 500),
        ]));
        $outcome = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $this->assertSame(FaoStatErrorCategory::UPSTREAM_SERVER_ERROR, $outcome->error);
        $dataCalls = collect(Http::recorded())->filter(function ($pair): bool {
            return str_contains((string) $pair[0]->url(), '/en/data/QCL');
        })->count();
        $this->assertSame(2, $dataCalls);
    }

    public function test_authentication_errors_are_not_retried(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'User does not exist.'], 400),
        ]);
        app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        $logins = collect(Http::recorded())->filter(function ($pair): bool {
            return str_contains((string) $pair[0]->url(), '/auth/login');
        })->count();
        $this->assertSame(1, $logins);
    }

    public function test_circuit_opens_after_bounded_upstream_failures(): void
    {
        config(['agricultural_intelligence.faostat.circuit_failure_threshold' => 2]);
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response(['error' => 'boom'], 500),
        ]));
        $filters = [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ];
        app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, $filters);
        app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, $filters);
        $this->assertTrue(app(FaoStatCircuitBreaker::class)->isOpen());
        $before = count(Http::recorded());
        $blocked = app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, $filters);
        $this->assertSame(FaoStatErrorCategory::CIRCUIT_OPEN, $blocked->error);
        $this->assertSame($before, count(Http::recorded()));
    }

    public function test_no_credential_or_token_logging_or_persistence(): void
    {
        Log::spy();
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response(['error' => 'boom'], 503),
        ]));
        app(FaoStatDeveloperPortalAdapter::class)->search('wheat', 5, [
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
        ]);
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context) ?: '';

            return ! str_contains($encoded, 'portal-pass')
                && ! str_contains($encoded, 'portal-user')
                && ! str_contains($encoded, 'test-access-token')
                && ! str_contains($encoded, 'test-refresh-token')
                && ! str_contains($encoded, 'Bearer ');
        });
        $this->assertNull(Cache::get('faostat.token'));
        $this->assertNull(Cache::get('faostat.access_token'));
        $sanitized = app(FaoStatOperationalLogger::class)->sanitize([
            'password' => 'portal-pass',
            'username' => 'portal-user',
            'Authorization' => 'Bearer test-access-token',
            'operation' => 'search',
            'domain' => 'QCL',
        ]);
        $this->assertArrayNotHasKey('password', $sanitized);
        $this->assertArrayNotHasKey('username', $sanitized);
        $this->assertArrayNotHasKey('Authorization', $sanitized);
        $this->assertSame('search', $sanitized['operation']);
    }

    public function test_health_disabled_reports_rfn_pending_without_blocking_qcl(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);
        $disabled = app(FaoStatReadinessReporter::class)->report();
        $this->assertSame(FaoStatReadinessState::DISABLED, $disabled['readiness']);
        $this->assertSame(ProviderHealthState::NOT_CONFIGURED, $disabled['provider_state']);
        $this->assertSame('PENDING_BLOCKED', $disabled['details']['rfn_live_validation']);
        $this->assertSame(FaoStatDomainActivationState::ACTIVE, $disabled['details']['qcl_activation']);
        $this->assertSame(FaoStatDomainActivationState::DISABLED, $disabled['details']['rfn_activation']);
    }

    public function test_health_not_configured_without_credentials(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.username' => '',
            'agricultural_intelligence.faostat.password' => '',
        ]);
        Http::fake(['faostatservices.fao.org/api/v1/ping' => Http::response('', 200)]);
        $missing = app(FaoStatReadinessReporter::class)->report();
        $this->assertSame(FaoStatReadinessState::NOT_CONFIGURED, $missing['readiness']);
    }

    public function test_health_upstream_unavailable_on_ping_failure(): void
    {
        Http::fake(['faostatservices.fao.org/api/v1/ping' => function () {
            throw new ConnectionException('cURL error 7: Failed to connect');
        }]);
        $down = app(FaoStatReadinessReporter::class)->report();
        $this->assertSame(FaoStatReadinessState::UPSTREAM_UNAVAILABLE, $down['readiness']);
    }

    public function test_health_authentication_failure_is_distinct(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/ping' => Http::response('', 200),
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'User does not exist.'], 400),
        ]);
        $authFail = app(FaoStatReadinessReporter::class)->report();
        $this->assertSame(FaoStatReadinessState::AUTHENTICATION_FAILURE, $authFail['readiness']);
    }

    public function test_health_ready_does_not_require_rfn(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/ping' => Http::response('', 200),
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ]);
        $ready = app(FaoStatDeveloperPortalAdapter::class)->health();
        $this->assertSame(ProviderHealthState::HEALTHY, $ready->state);
        $this->assertSame(FaoStatReadinessState::READY, $ready->details['readiness'] ?? null);
        $this->assertSame('PENDING_BLOCKED', $ready->details['rfn_live_validation'] ?? null);
    }

    public function test_portal_remains_canonical_when_portal_disabled(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->assertInstanceOf(
            FaoStatDeveloperPortalAdapter::class,
            app(ScientificSourceAdapterRegistry::class)->get('fao_stat'),
        );
    }

    public function test_openalex_agri_does_not_become_faostat_domain(): void
    {
        $plan = $this->phase4Plan('What was wheat production in Italy in 2022?');
        $openAlex = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);
        $this->assertSame('agri', $openAlex['domain'] ?? null);
        $this->assertNotContains('AGRI', FaoStatActivationPolicy::activeDomains());
        $this->expectException(FaoStatPortalException::class);
        app(FaoStatDeveloperPortalClient::class)->assertInspectableDomain('AGRI');
    }

    public function test_claim_support_and_fusion_boundaries(): void
    {
        $assessor = app(FaoStatClaimSupportAssessor::class);
        $supported = $assessor->assess(
            $this->phase4Plan(
                'Italy produced 6,609,520 tonnes of wheat in 2022.',
                'agricultural_economics',
                ['domain' => 'QCL', 'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022'],
                'Wheat',
                'Italy',
            ),
            $this->faostatResult(),
        );
        $this->assertSame(FaoStatSupportState::SUPPORTED, $supported['factors']['faostat_support_state']);

        $causal = $assessor->assess(
            $this->phase4Plan('Wheat production increased because rainfall increased.'),
            $this->faostatResult(),
        );
        $this->assertSame(FaoStatSupportState::NOT_APPLICABLE, $causal['factors']['faostat_support_state']);
        $this->assertSame(FaoStatClaimSemantics::CAUSAL, FaoStatClaimSemantics::inspect(
            $this->phase4Plan('Wheat production increased because rainfall increased.')
        )['kind']);

        $recommendation = $assessor->assess(
            $this->phase4Plan('The best fertilizer for wheat is X.'),
            $this->faostatResult(),
        );
        $this->assertSame(FaoStatSupportState::NOT_APPLICABLE, $recommendation['factors']['faostat_support_state']);

        $fusion = app(FaoStatClaimEvidenceFusion::class);
        $bundle = $fusion->fuse([
            $fusion->faostatStatsResult([
                'item' => 'Wheat',
                'value' => '6609520',
                'unit' => 't',
                'year' => '2022',
                'query_element_code' => '2510',
                'response_element_code' => '5510',
            ], 'https://faostatservices.fao.org/api/v1/en/data/QCL'),
            $fusion->scholarlyResult('openalex', [
                'title' => 'Wheat physiology paper',
                'doi' => '10.1/openalex',
                'publication_year' => 2018,
            ]),
        ]);
        $this->assertNotEmpty($bundle->results[0]->stats);
        $this->assertEmpty($bundle->results[0]->scientificEvidence);
        $this->assertNotEmpty($bundle->results[1]->scientificEvidence);
        $this->assertEmpty($bundle->results[1]->stats);
    }

    public function test_operational_status_does_not_claim_rfn_live_validation(): void
    {
        $snapshot = FaoStatOperationalStatus::snapshot();
        $this->assertFalse($snapshot['rfn_active']);
        $this->assertSame('PENDING_BLOCKED', $snapshot['rfn_live_validation']);
        $this->assertTrue($snapshot['qcl_default_active']);
        $this->assertFalse($snapshot['fenix_retained']);
        $this->assertFalse($snapshot['faostat_enabled_default']);
        $encoded = json_encode($snapshot) ?: '';
        $this->assertStringNotContainsString('portal-pass', $encoded);
        $this->assertStringNotContainsString('AccessToken', $encoded);
    }
}
