<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalResultNormalizer;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatEvidenceType;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPortalException;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FaoStatDeveloperPortalTest extends TestCase
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
            'agricultural_intelligence.faostat.timeout' => 15,
        ]);
    }

    public function test_authentication_request_and_successful_login(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ]);

        $got = app(FaoStatDeveloperPortalTokenManager::class)->obtainToken();
        $this->assertSame('Bearer', $got['token_type']);
        $this->assertTrue($got['refresh_token_present']);
        $this->assertSame(3600, $got['expires_in']);
        $this->assertArrayNotHasKey('token', $got);
        $this->assertArrayNotHasKey('AccessToken', $got);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/auth/login')
                && str_contains($request->body(), 'username=')
                && str_contains($request->body(), 'password=');
        });
    }

    public function test_failed_login(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'User does not exist.'], 400),
        ]);

        try {
            app(FaoStatDeveloperPortalTokenManager::class)->obtainToken();
            $this->fail('Expected FaoStatPortalException');
        } catch (FaoStatPortalException $e) {
            $this->assertSame(FaoStatErrorCategory::AUTHENTICATION_ERROR, $e->category);
            $this->assertSame(400, $e->httpStatus);
            $this->assertStringNotContainsString('portal-pass', $e->getMessage());
            $this->assertStringNotContainsString('test-access-token', $e->getMessage());
        }
    }

    public function test_token_expiry_uses_eighty_percent_and_reuses_memory_token(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ]);
        $manager = app(FaoStatDeveloperPortalTokenManager::class);
        $manager->obtainToken();
        $expiresAt = $manager->expiresAtUnix();
        $this->assertNotNull($expiresAt);
        $this->assertLessThanOrEqual(time() + 2880, $expiresAt);
        $this->assertGreaterThan(time() + 2000, $expiresAt);
        $manager->obtainToken();
        Http::assertSentCount(1);
    }

    public function test_no_secret_leakage_in_logs_on_failure(): void
    {
        Log::spy();
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response(['error' => 'boom'], 503),
        ]);

        $outcome = $this->adapter()->search('wheat production Italy', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::UPSTREAM_SERVER_ERROR, $outcome->error);
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context) ?: '';

            return str_contains($message, 'FAOSTAT')
                && ! str_contains($encoded, 'portal-pass')
                && ! str_contains($encoded, 'test-access-token')
                && ! str_contains($encoded, 'Bearer ');
        });
    }

    public function test_domain_and_qcl_discovery(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response([
                'metadata' => [],
                'data' => [
                    ['domain_code' => 'QCL', 'domain_name' => 'Crops and livestock products', 'group_code' => 'Q'],
                    ['domain_code' => 'FBS', 'domain_name' => 'Food Balances (2010-)'],
                ],
            ], 200),
        ]));

        $payload = app(FaoStatDeveloperPortalClient::class)->getGroupsAndDomains();
        $codes = array_column($payload['data'], 'domain_code');
        $this->assertContains('QCL', $codes);
        $this->assertSame('Crops and livestock products', $payload['data'][0]['domain_name']);
        $this->assertArrayNotHasKey('DomainaCode', $payload['data'][0]);
    }

    public function test_unsupported_domain_fails_closed(): void
    {
        $this->expectException(FaoStatPortalException::class);
        app(FaoStatDeveloperPortalClient::class)->assertDomainAllowed('FBS');
    }

    public function test_item_element_country_region_specialgroup_year_code_resolution(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/codes/items/QCL*' => Http::response($this->codeList([
                ['code' => '15', 'label' => 'Wheat'],
                ['code' => '515', 'label' => 'Apples'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/elements/QCL*' => Http::response($this->codeList([
                ['code' => '2510', 'label' => 'Production Quantity'],
                ['code' => '2413', 'label' => 'Yield'],
                ['code' => '2312', 'label' => 'Area harvested'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/countries/QCL*' => Http::response($this->codeList([
                ['code' => '106', 'label' => 'Italy'],
                ['code' => '2', 'label' => 'Afghanistan'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/regions/QCL*' => Http::response($this->codeList([
                ['code' => '5000', 'label' => 'World + (Total)'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/specialgroups/QCL*' => Http::response($this->codeList([
                ['code' => '5707', 'label' => 'European Union (27) + (Total)'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/years/QCL*' => Http::response($this->codeList([
                ['code' => '2022', 'label' => '2022'],
            ]), 200),
        ]));

        $client = app(FaoStatDeveloperPortalClient::class);
        $this->assertSame('15', $client->resolveUniqueCode('items', 'QCL', 'Wheat')['code']);
        $this->assertSame('2510', $client->resolveUniqueCode('elements', 'QCL', 'Production Quantity')['code']);
        $this->assertSame('106', $client->resolveUniqueCode('countries', 'QCL', 'Italy')['code']);
        $this->assertSame('5000', $client->resolveUniqueCode('regions', 'QCL', 'World + (Total)')['code']);
        $this->assertSame('5707', $client->resolveUniqueCode('specialgroups', 'QCL', 'European Union (27) + (Total)')['code']);
        $this->assertSame('2022', $client->resolveUniqueCode('years', 'QCL', '2022')['code']);
    }

    public function test_ambiguous_label_does_not_invent_code(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/codes/items/QCL*' => Http::response($this->codeList([
                ['code' => '15', 'label' => 'Wheat'],
                ['code' => '16', 'label' => 'Wheat'],
            ]), 200),
        ]));
        $got = app(FaoStatDeveloperPortalClient::class)->resolveUniqueCode('items', 'QCL', 'Wheat');
        $this->assertSame('ambiguous', $got['status']);
        $this->assertNull($got['code']);
    }

    public function test_areas_endpoint_is_never_invoked(): void
    {
        $this->expectException(FaoStatPortalException::class);
        $ref = new \ReflectionClass(FaoStatDeveloperPortalClient::class);
        $method = $ref->getMethod('codeList');
        $method->setAccessible(true);
        $method->invoke(app(FaoStatDeveloperPortalClient::class), 'areas', 'QCL');
    }

    public function test_dual_element_code_and_live_field_normalization(): void
    {
        $obs = app(FaoStatDeveloperPortalResultNormalizer::class)->normalize(
            $this->italyWheatRow(),
            '2510',
            'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106&item=15&element=2510&year=2022',
        );
        $this->assertNotNull($obs);
        $this->assertSame('2510', $obs->queryElementCode);
        $this->assertSame('5510', $obs->responseElementCode);
        $this->assertSame('106', $obs->areaCode);
        $this->assertSame('15', $obs->itemCode);
        $this->assertSame('t', $obs->unit);
        $this->assertSame('6609520', $obs->value);
        $this->assertSame('A', $obs->flag);
        $this->assertStringContainsString('faostatservices.fao.org', $obs->provenance['query_url']);
        $this->assertStringNotContainsString('www.fao.org/faostat/', $obs->provenance['query_url']);

        $yield = app(FaoStatDeveloperPortalResultNormalizer::class)->normalize(
            array_merge($this->italyWheatRow(), ['Element Code' => '5412', 'Element' => 'Yield']),
            '2413',
            'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106&item=15&element=2413&year=2022',
        );
        $this->assertSame('2413', $yield->queryElementCode);
        $this->assertSame('5412', $yield->responseElementCode);

        $area = app(FaoStatDeveloperPortalResultNormalizer::class)->normalize(
            array_merge($this->italyWheatRow(), ['Element Code' => '5312', 'Element' => 'Area harvested']),
            '2312',
            'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106&item=15&element=2312&year=2022',
        );
        $this->assertSame('2312', $area->queryElementCode);
        $this->assertSame('5312', $area->responseElementCode);
    }

    public function test_empty_200_is_empty_result_not_failure(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response(['metadata' => ['output_type' => 'OBJECTS'], 'data' => []], 200),
        ]));
        $outcome = $this->adapter()->search('wheat', 5, $this->italyWheatFilters(['item' => '99999999']));
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::EMPTY_RESULT, $outcome->error);
        $this->assertSame(200, $outcome->httpStatus);
    }

    public function test_successful_data_query_uses_portal_host_and_not_fenix(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $this->assertSame('106', $query['area'] ?? null);
                $this->assertSame('15', $query['item'] ?? null);
                $this->assertSame('2510', $query['element'] ?? null);
                $this->assertSame('2022', $query['year'] ?? null);
                $this->assertArrayNotHasKey('page_size', $query);
                $this->assertArrayNotHasKey('itemCode', $query);
                $this->assertStringContainsString('faostatservices.fao.org', $request->url());
                $this->assertStringNotContainsString('fenixservices.fao.org', $request->url());

                return Http::response([
                    'metadata' => ['output_type' => 'OBJECTS', 'datasource' => 'PRODUCTION'],
                    'data' => [$this->italyWheatRow()],
                ], 200);
            },
        ]));

        $outcome = $this->adapter()->search('wheat production Italy', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertCount(1, $outcome->results);
        $row = $outcome->results[0];
        $this->assertSame(FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE, $row->relevanceMetadata['evidence_type'] ?? null);
        $this->assertTrue($row->relevanceMetadata['not_literature'] ?? false);
        $this->assertSame('2510', $row->relevanceMetadata['query_element_code'] ?? null);
        $this->assertSame('5510', $row->relevanceMetadata['response_element_code'] ?? null);
        $this->assertStringContainsString('faostatservices.fao.org', (string) $row->canonicalUrl);
        $this->assertStringNotContainsString('www.fao.org/faostat/', (string) $row->canonicalUrl);
    }

    public function test_incomplete_filters_are_empty_not_invented(): void
    {
        $outcome = $this->adapter()->search('what is boron deficiency in tomato', 5, []);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::INCOMPLETE_FILTERS, $outcome->error);
        $this->assertTrue($outcome->observability['considered'] ?? false);
        $this->assertFalse($outcome->observability['forced'] ?? true);
        Http::assertNothingSent();
    }

    public function test_unauthorized_relogs_once(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/data/*' => Http::sequence()
                ->push(['message' => 'Missing Authorization Header'], 401)
                ->push(['metadata' => ['output_type' => 'OBJECTS'], 'data' => [$this->italyWheatRow()]], 200),
        ]);

        $outcome = $this->adapter()->search('wheat', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
    }

    public function test_persistent_401_fails_after_single_relogin(): void
    {
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response(['message' => 'Missing Authorization Header'], 401),
        ]);

        $outcome = $this->adapter()->search('wheat', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::AUTHORIZATION_ERROR, $outcome->error);
    }

    public function test_upstream_500_is_classified(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response(['error' => 'upstream'], 500),
        ]));
        $outcome = $this->adapter()->search('wheat', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::UPSTREAM_SERVER_ERROR, $outcome->error);
        $this->assertSame(500, $outcome->httpStatus);
    }

    public function test_metadata_and_dimensions_use_portal_paths(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/metadata/QCL*' => Http::response([
                'metadata' => [],
                'data' => [['code' => '0', 'label' => 'QCL']],
            ], 200),
            'faostatservices.fao.org/api/v1/en/dimensions/QCL*' => Http::response([
                'metadata' => [],
                'data' => [
                    ['id' => 'area', 'label' => 'Area'],
                    ['id' => 'item', 'label' => 'Item'],
                    ['id' => 'element', 'label' => 'Element'],
                    ['id' => 'year', 'label' => 'Year'],
                ],
            ], 200),
        ]));
        $client = app(FaoStatDeveloperPortalClient::class);
        $this->assertArrayHasKey('data', $client->getMetadata('QCL'));
        $dims = $client->getDimensions('QCL');
        $ids = array_column($dims['data'], 'id');
        $this->assertContains('item', $ids);
        $this->assertContains('element', $ids);
    }

    public function test_disabled_flag_makes_adapter_unavailable(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);
        $outcome = $this->adapter()->search('wheat', 5, $this->italyWheatFilters());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame(FaoStatErrorCategory::DISABLED, $outcome->error);
    }

    public function test_csv_response_handling(): void
    {
        $csv = "Domain Code,Domain,Area Code,Area,Element Code,Element,Item Code,Item,Year Code,Year,Unit,Value,Flag,Flag Description,Note\n";
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response($csv, 200, ['Content-Type' => 'application/octet-stream;charset=utf-8']),
        ]));
        $got = app(FaoStatDeveloperPortalClient::class)->getData('QCL', [
            'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
        ], csv: true);
        $this->assertSame(200, $got['status']);
        $this->assertStringContainsString('Domain Code', (string) $got['csv']);
        $this->assertNull($got['payload']);
    }

    public function test_timeout_is_classified(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });
        try {
            app(FaoStatDeveloperPortalTokenManager::class)->obtainToken();
            $this->fail('Expected FaoStatPortalException');
        } catch (FaoStatPortalException $e) {
            $this->assertSame(FaoStatErrorCategory::TIMEOUT, $e->category);
        }
    }

    public function test_network_failure_is_classified(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 7: Failed to connect');
        });
        try {
            app(FaoStatDeveloperPortalTokenManager::class)->obtainToken();
            $this->fail('Expected FaoStatPortalException');
        } catch (FaoStatPortalException $e) {
            $this->assertSame(FaoStatErrorCategory::NETWORK_ERROR, $e->category);
        }
    }

    public function test_health_distinguishes_disabled_and_ping(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);
        $disabled = $this->adapter()->health();
        $this->assertSame(ProviderHealthState::NOT_CONFIGURED, $disabled->state);

        config(['agricultural_intelligence.faostat.enabled' => true]);
        Http::fake([
            'faostatservices.fao.org/api/v1/ping' => Http::response('', 200),
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ]);
        $ok = $this->adapter()->health();
        $this->assertSame(ProviderHealthState::HEALTHY, $ok->state);
        $this->assertSame('authentication_successful', $ok->message);
    }

    public function test_registry_keeps_portal_when_disabled(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $registry = app(ScientificSourceAdapterRegistry::class);
        $this->assertInstanceOf(FaoStatDeveloperPortalAdapter::class, $registry->get('fao_stat'));
    }

    public function test_registry_uses_portal_when_enabled(): void
    {
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->assertInstanceOf(FaoStatDeveloperPortalAdapter::class, app(ScientificSourceAdapterRegistry::class)->get('fao_stat'));
    }

    public function test_config_default_flag_is_false(): void
    {
        $config = file_get_contents(config_path('agricultural_intelligence.php'));
        $this->assertNotFalse($config);
        $this->assertStringContainsString("env('FAOSTAT_ENABLED', false)", $config);
        $this->assertStringContainsString('https://faostatservices.fao.org/api/v1', $config);
        $this->assertStringNotContainsString('FAOSTAT_USERNAME=', $config);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function italyWheatFilters(array $extra = []): array
    {
        return array_merge([
            'domain' => 'QCL',
            'area' => '106',
            'item' => '15',
            'element' => '2510',
            'year' => '2022',
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

    /** @return array<string, mixed> */
    private function loginPayload(): array
    {
        return [
            'AuthenticationResult' => [
                'AccessToken' => 'test-access-token',
                'IdToken' => 'test-id-token',
                'RefreshToken' => 'test-refresh-token',
                'ExpiresIn' => 3600,
                'TokenType' => 'Bearer',
            ],
            'ChallengeParameters' => [],
        ];
    }

    /**
     * @param  list<array{code: string, label: string}>  $rows
     * @return array{metadata: array<string, mixed>, data: list<array{code: string, label: string}>}
     */
    private function codeList(array $rows): array
    {
        return ['metadata' => [], 'data' => $rows];
    }

    /**
     * @param  array<string, mixed>  $routes
     * @return array<string, mixed>
     */
    private function authAnd(array $routes): array
    {
        return array_merge([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ], $routes);
    }

    private function adapter(): FaoStatDeveloperPortalAdapter
    {
        return app(FaoStatDeveloperPortalAdapter::class);
    }
}
