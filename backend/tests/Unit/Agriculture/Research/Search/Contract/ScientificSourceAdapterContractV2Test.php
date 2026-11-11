<?php

namespace Tests\Unit\Agriculture\Research\Search\Contract;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Contract\DelegatesSearchRequestToSearch;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterV1Bridge;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureClassifier;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryDecision;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificSourceFailure;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\StrayRequestException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ADR-023 §8.36 increment R3: source adapter contract v2.
 */
class ScientificSourceAdapterContractV2Test extends TestCase
{
    /** v1 adapters whose error strings the v1 bridge must classify. */
    private const V1_EMITTERS = [
        'app/Services/Agriculture/Research/Search/Adapters/OpenAlexScientificSourceAdapter.php',
        'app/Services/Agriculture/Research/Search/Adapters/CrossRefScientificSourceAdapter.php',
        'app/Services/Agriculture/Research/Search/Adapters/SemanticScholarScientificSourceAdapter.php',
        'app/Services/Agriculture/Research/Search/Adapters/ConsensusScientificSourceAdapter.php',
        'app/Services/Agriculture/Intelligence/Adapters/Scientific/FaoStat/FaoStatDeveloperPortalAdapter.php',
        'app/Services/Agriculture/Research/Search/MultiSourceScientificSearchOrchestrator.php',
    ];

    public function test_only_transient_categories_are_retryable(): void
    {
        $retryable = array_values(array_map(
            static fn (ScientificSourceFailureCategory $c): string => $c->value,
            array_filter(ScientificSourceFailureCategory::cases(), static fn (ScientificSourceFailureCategory $c): bool => $c->isRetryable()),
        ));

        $this->assertSame(['transport', 'timeout', 'rate_limited', 'upstream_unavailable'], $retryable);
    }

    /** @return array<string, array{\Throwable, ScientificSourceFailureCategory}> */
    public static function expectedSourceFailures(): array
    {
        return [
            'connection' => [new ConnectionException('cURL error 7'), ScientificSourceFailureCategory::TRANSPORT],
            'guzzle transfer' => [new TransferException('too many redirects'), ScientificSourceFailureCategory::TRANSPORT],
            'http 429' => [new RequestException(new Response(new PsrResponse(429))), ScientificSourceFailureCategory::RATE_LIMITED],
            'http 503' => [new RequestException(new Response(new PsrResponse(503))), ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE],
            'http 401' => [new RequestException(new Response(new PsrResponse(401))), ScientificSourceFailureCategory::AUTHENTICATION],
            'http 404' => [new RequestException(new Response(new PsrResponse(404))), ScientificSourceFailureCategory::REQUEST_REJECTED],
            'source failure default' => [new ScientificSourceFailure('unusable payload'), ScientificSourceFailureCategory::SOURCE],
            'source failure typed' => [
                new ScientificSourceFailure('bad json', ScientificSourceFailureCategory::MALFORMED_RESPONSE),
                ScientificSourceFailureCategory::MALFORMED_RESPONSE,
            ],
        ];
    }

    #[DataProvider('expectedSourceFailures')]
    public function test_expected_source_failures_are_classified(\Throwable $failure, ScientificSourceFailureCategory $category): void
    {
        $this->assertSame($category, ScientificSourceFailureClassifier::classifyThrowable($failure));
        $this->assertFalse(ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure($failure));
    }

    /** @return array<string, array{\Throwable}> */
    public static function defectsAndTestSignals(): array
    {
        return [
            'runtime' => [new \RuntimeException('defect')],
            'logic' => [new \LogicException('defect')],
            'type error' => [new \TypeError('defect')],
            'plain exception' => [new \Exception('defect')],
            'phpunit assertion' => [new AssertionFailedError('assertion')],
            'stray request' => [new StrayRequestException('https://example.test')],
            'contract violation' => [new ScientificSourceContractViolation('bad declaration')],
        ];
    }

    #[DataProvider('defectsAndTestSignals')]
    public function test_defects_and_test_signals_are_never_source_failures(\Throwable $throwable): void
    {
        $this->assertNull(ScientificSourceFailureClassifier::classifyThrowable($throwable));
        $this->assertFalse(ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure($throwable));
    }

    public function test_only_process_pool_failures_count_as_concurrency_infrastructure_failures(): void
    {
        $this->assertTrue(ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure(
            new \Exception('Concurrent process failed with exit code [255]. Message: Allowed memory size exhausted')
        ));
        $this->assertTrue(ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure(
            new \Symfony\Component\Process\Exception\RuntimeException('Unable to launch a new process.')
        ));
        $this->assertFalse(
            ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure(
                new \RuntimeException('Concurrent process failed with exit code [1].')
            ),
            'a re-created child exception of another class must propagate even with the same message',
        );
        $this->assertFalse(ScientificSourceFailureClassifier::isConcurrencyInfrastructureFailure(
            new \ErrorException('Trying to access array offset on value of type null')
        ));
    }

    /** @return array<string, array{ScientificSourceSearchOutcome, ?ScientificSourceFailureCategory}> */
    public static function v1Outcomes(): array
    {
        $failed = static fn (?string $error, ?int $http = null, ?array $obs = null): ScientificSourceSearchOutcome => new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_FAILED,
            error: $error,
            httpStatus: $http,
            observability: $obs,
        );

        return [
            'success' => [new ScientificSourceSearchOutcome('openalex', ScientificSourceSearchOutcome::STATUS_SUCCESS), null],
            'plain empty' => [new ScientificSourceSearchOutcome('openalex', ScientificSourceSearchOutcome::STATUS_EMPTY), null],
            'failed without error' => [$failed(null), ScientificSourceFailureCategory::UNCLASSIFIED],
            'request_exception' => [$failed('request_exception'), ScientificSourceFailureCategory::TRANSPORT],
            'timeout' => [$failed('timeout'), ScientificSourceFailureCategory::TIMEOUT],
            'rate_limited' => [$failed('rate_limited', 429), ScientificSourceFailureCategory::RATE_LIMITED],
            'http_error 503' => [$failed('http_error', 503), ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE],
            'http_error 404' => [$failed('http_error', 404), ScientificSourceFailureCategory::REQUEST_REJECTED],
            'http_error without status' => [$failed('http_error'), ScientificSourceFailureCategory::UNCLASSIFIED],
            'missing_api_key' => [$failed('missing_api_key'), ScientificSourceFailureCategory::NOT_CONFIGURED],
            'adapter_exception transport' => [$failed('adapter_exception', null, ['failure_category' => 'transport']), ScientificSourceFailureCategory::TRANSPORT],
            'adapter_exception unknown' => [$failed('adapter_exception', null, ['failure_category' => 'nonsense']), ScientificSourceFailureCategory::UNCLASSIFIED],
            'faostat empty result' => [$failed(FaoStatErrorCategory::EMPTY_RESULT), null],
            'faostat circuit open' => [$failed(FaoStatErrorCategory::CIRCUIT_OPEN), ScientificSourceFailureCategory::CIRCUIT_OPEN],
            'unknown string' => [$failed('something_new'), ScientificSourceFailureCategory::UNCLASSIFIED],
        ];
    }

    #[DataProvider('v1Outcomes')]
    public function test_v1_bridge_classifies_outcome_errors(ScientificSourceSearchOutcome $outcome, ?ScientificSourceFailureCategory $expected): void
    {
        $this->assertSame($expected, ScientificSourceFailureClassifier::classifyOutcome($outcome));
    }

    public function test_every_error_string_the_v1_sources_emit_has_a_category(): void
    {
        $errors = [];
        foreach (self::V1_EMITTERS as $path) {
            $source = file_get_contents(base_path($path));
            $this->assertIsString($source, $path);
            preg_match_all("/error: '([a-z_]+)'/", $source, $matches);
            foreach ($matches[1] as $error) {
                $errors[$error] = true;
            }
        }
        foreach ((new \ReflectionClass(FaoStatErrorCategory::class))->getConstants() as $value) {
            $errors[$value] = true;
        }
        $this->assertGreaterThanOrEqual(25, count($errors), 'the scan must find the v1 vocabulary');

        foreach (array_keys($errors) as $error) {
            $category = ScientificSourceFailureClassifier::classifyOutcome(new ScientificSourceSearchOutcome(
                sourceKey: 'any',
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: $error,
                httpStatus: $error === 'http_error' ? 500 : null,
            ));
            if ($error === FaoStatErrorCategory::EMPTY_RESULT) {
                $this->assertNull($category);

                continue;
            }
            $this->assertNotNull($category, $error);
            $this->assertNotSame(ScientificSourceFailureCategory::UNCLASSIFIED, $category, "v1 error '{$error}' has no category");
        }
    }

    public function test_default_retry_policy_never_retries(): void
    {
        $decision = ScientificSourceRetryPolicy::none()->decide(ScientificSourceFailureCategory::TRANSPORT, 1);

        $this->assertFalse($decision->retry);
        $this->assertSame(ScientificSourceRetryDecision::REASON_ATTEMPTS_EXHAUSTED, $decision->reason);
    }

    public function test_retry_policy_backs_off_and_respects_category_attempts_retry_after_and_budget(): void
    {
        $policy = new ScientificSourceRetryPolicy(maxAttempts: 4, baseDelayMs: 500, maxDelayMs: 1500);

        $this->assertSame(
            ['retry' => false, 'delay_ms' => 0, 'reason' => 'not_retryable'],
            $policy->decide(ScientificSourceFailureCategory::AUTHENTICATION, 1)->toArray(),
        );
        $this->assertSame(500, $policy->decide(ScientificSourceFailureCategory::TRANSPORT, 1)->delayMs);
        $this->assertSame(1000, $policy->decide(ScientificSourceFailureCategory::TRANSPORT, 2)->delayMs);
        $this->assertSame(1500, $policy->decide(ScientificSourceFailureCategory::TRANSPORT, 3)->delayMs, 'capped at maxDelayMs');
        $this->assertSame('attempts_exhausted', $policy->decide(ScientificSourceFailureCategory::TRANSPORT, 4)->reason);

        $this->assertSame(1200, $policy->decide(ScientificSourceFailureCategory::RATE_LIMITED, 1, retryAfterSeconds: 1.2)->delayMs);
        $this->assertSame(
            'retry_after_exceeds_max_delay',
            $policy->decide(ScientificSourceFailureCategory::RATE_LIMITED, 1, retryAfterSeconds: 30)->reason,
        );
        $this->assertSame(
            'insufficient_budget',
            $policy->decide(ScientificSourceFailureCategory::TIMEOUT, 1, remainingBudgetSeconds: 1.4)->reason,
        );
        $this->assertTrue($policy->decide(ScientificSourceFailureCategory::TIMEOUT, 1, remainingBudgetSeconds: 1.5)->retry);
    }

    public function test_retry_policy_rejects_invalid_parameters(): void
    {
        foreach ([[0, 0, 0], [2, -1, 10], [2, 100, 50]] as [$attempts, $base, $max]) {
            try {
                new ScientificSourceRetryPolicy($attempts, $base, $max);
                $this->fail("Expected rejection of [{$attempts}, {$base}, {$max}]");
            } catch (ScientificSourceContractViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_unknown_licensing_permits_nothing(): void
    {
        $licensing = ScientificSourceLicensingDeclaration::unresolved();

        $this->assertTrue($licensing->isFullyUnresolved());
        foreach (ScientificSourceUsage::cases() as $usage) {
            $this->assertFalse($licensing->permits($usage), $usage->value);
            $this->assertSame(ScientificSourceUsageStatus::UNRESOLVED, $licensing->statusOf($usage));
        }
        $this->assertSame(
            ['retrieval', 'metadata_storage', 'full_text_storage', 'excerpt_display', 'redistribution', 'citation_linking'],
            array_keys($licensing->toArray()['usages']),
        );
        $this->assertNull($licensing->toArray()['licence_identifier']);
    }

    public function test_only_an_evidenced_permitted_usage_permits(): void
    {
        $licensing = new ScientificSourceLicensingDeclaration(
            usages: [
                ScientificSourceUsage::CITATION_LINKING->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::EXCERPT_DISPLAY->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::REDISTRIBUTION->value => ScientificSourceUsageStatus::PROHIBITED,
            ],
            termsReference: 'provider terms page, version recorded in the onboarding contract',
            evidenceReference: 'test evidence',
        );

        $this->assertTrue($licensing->permits(ScientificSourceUsage::CITATION_LINKING));
        $this->assertFalse($licensing->permits(ScientificSourceUsage::EXCERPT_DISPLAY), 'conditional is not a blanket permission');
        $this->assertFalse($licensing->permits(ScientificSourceUsage::REDISTRIBUTION));
        $this->assertFalse($licensing->permits(ScientificSourceUsage::FULL_TEXT_STORAGE), 'undeclared stays unresolved');
        $this->assertFalse($licensing->isFullyUnresolved());
    }

    public function test_licensing_claims_require_terms_and_evidence(): void
    {
        $cases = [
            'A licence identifier or a resolved usage requires both a terms reference and an evidence reference.' => [
                fn () => new ScientificSourceLicensingDeclaration(licenceIdentifier: 'CC-BY-4.0'),
                fn () => new ScientificSourceLicensingDeclaration(
                    usages: ['retrieval' => ScientificSourceUsageStatus::PERMITTED],
                    termsReference: 'terms',
                ),
            ],
            "Unknown source usage 'scraping'." => [
                fn () => new ScientificSourceLicensingDeclaration(usages: ['scraping' => ScientificSourceUsageStatus::PERMITTED]),
            ],
            'Licensing evidenceReference must be null or non-empty.' => [
                fn () => new ScientificSourceLicensingDeclaration(evidenceReference: ' '),
            ],
        ];

        foreach ($cases as $message => $builders) {
            foreach ($builders as $build) {
                try {
                    $build();
                    $this->fail("Expected rejection: {$message}");
                } catch (ScientificSourceContractViolation $e) {
                    $this->assertSame($message, $e->getMessage());
                }
            }
        }
    }

    public function test_search_request_carries_builder_text_unchanged_and_validates_input(): void
    {
        $request = new ScientificSourceSearchRequest('  Barley "water requirement" ', 5, ['search_budget_remaining_seconds' => '7.5']);

        $this->assertSame('  Barley "water requirement" ', $request->queryText);
        $this->assertSame(7.5, $request->remainingBudgetSeconds());
        $this->assertNull((new ScientificSourceSearchRequest('q', 1))->remainingBudgetSeconds());

        foreach ([fn () => new ScientificSourceSearchRequest('q', 0), fn () => new ScientificSourceSearchRequest('q', 3, ['x'])] as $build) {
            try {
                $build();
                $this->fail('Expected an invalid request to be rejected');
            } catch (ScientificSourceContractViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_descriptor_leaves_unknown_limits_undeclared_and_validates(): void
    {
        $descriptor = new ScientificSourceAdapterDescriptor(
            sourceKey: 'example_source',
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );

        $array = $descriptor->toArray();
        $this->assertSame('scientific_source_adapter.v2', $array['contract_version']);
        $this->assertSame(1, $array['max_concurrent_requests']);
        $this->assertNull($array['max_results_per_request']);
        $this->assertNull($array['pagination_supported']);
        $this->assertNull($array['requests_per_minute']);

        $invalid = [
            fn () => new ScientificSourceAdapterDescriptor('Example', ScientificSourceLicensingDeclaration::unresolved(), ScientificSourceRetryPolicy::none()),
            fn () => new ScientificSourceAdapterDescriptor('example', ScientificSourceLicensingDeclaration::unresolved(), ScientificSourceRetryPolicy::none(), maxConcurrentRequests: 0),
            fn () => new ScientificSourceAdapterDescriptor('example', ScientificSourceLicensingDeclaration::unresolved(), ScientificSourceRetryPolicy::none(), contractVersion: 'scientific_source_adapter.v1'),
        ];
        foreach ($invalid as $build) {
            try {
                $build();
                $this->fail('Expected an invalid descriptor to be rejected');
            } catch (ScientificSourceContractViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_v1_bridge_delegates_exactly_and_declares_nothing_it_cannot_know(): void
    {
        $v1 = new ContractV2RecordingV1Adapter();
        $bridge = ScientificSourceAdapterV1Bridge::wrap($v1);

        $this->assertInstanceOf(ScientificSourceAdapterV2Interface::class, $bridge);
        $this->assertSame('contract_v1_example', $bridge->sourceKey());
        $this->assertSame('Contract v1 example', $bridge->displayName());

        $outcome = $bridge->searchRequest(new ScientificSourceSearchRequest('builder text', 4, ['domain' => 'agri']));
        $this->assertSame([['builder text', 4, ['domain' => 'agri']]], $v1->calls);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);

        $descriptor = $bridge->descriptor();
        $this->assertTrue($descriptor->licensing->isFullyUnresolved());
        $this->assertSame(1, $descriptor->retryPolicy->maxAttempts);
        $this->assertNull($descriptor->maxResultsPerRequest);
        $this->assertNull($descriptor->requestsPerMinute);

        $native = new ContractV2NativeAdapter();
        $this->assertSame($native, ScientificSourceAdapterV1Bridge::wrap($native), 'a v2 adapter is never re-wrapped');
    }

    public function test_delegating_trait_passes_the_request_to_v1_search_unchanged(): void
    {
        $adapter = new ContractV2DelegatingAdapter();

        $adapter->searchRequest(new ScientificSourceSearchRequest('builder text', 4, ['domain' => 'agri', 'search_budget_remaining_seconds' => 7.5]));

        $this->assertSame([['builder text', 4, ['domain' => 'agri', 'search_budget_remaining_seconds' => 7.5]]], $adapter->calls);
        $this->assertSame($adapter, ScientificSourceAdapterV1Bridge::wrap($adapter));
    }

    public function test_source_failure_keeps_message_and_v1_construction(): void
    {
        $plain = new ScientificSourceFailure('payload unusable');
        $this->assertSame('payload unusable', $plain->getMessage());
        $this->assertSame(ScientificSourceFailureCategory::SOURCE, $plain->category);

        $previous = new \RuntimeException('inner');
        $typed = new ScientificSourceFailure('throttled', ScientificSourceFailureCategory::RATE_LIMITED, $previous);
        $this->assertSame(ScientificSourceFailureCategory::RATE_LIMITED, $typed->category);
        $this->assertSame($previous, $typed->getPrevious());
    }

    public function test_registration_accepts_v2_only_for_v2_adapters(): void
    {
        $registration = new ScientificSourceRegistration(
            sourceKey: 'contract_v2_example',
            adapterClass: ContractV2NativeAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
        );

        $this->assertSame('scientific_source_adapter.v2', $registration->toArray()['adapter_contract_version']);
        $this->assertSame(
            [ScientificSourceAdapterInterface::CONTRACT_VERSION, ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION],
            ScientificSourceRegistration::SUPPORTED_CONTRACT_VERSIONS,
        );
    }
}

class ContractV2RecordingV1Adapter implements ScientificSourceAdapterInterface
{
    /** @var list<array{string, int, array<string, mixed>}> */
    public array $calls = [];

    public function sourceKey(): string
    {
        return 'contract_v1_example';
    }

    public function displayName(): string
    {
        return 'Contract v1 example';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        $this->calls[] = [$query, $limit, $options];

        return new ScientificSourceSearchOutcome($this->sourceKey(), ScientificSourceSearchOutcome::STATUS_EMPTY);
    }
}

class ContractV2DelegatingAdapter extends ContractV2RecordingV1Adapter implements ScientificSourceAdapterV2Interface
{
    use DelegatesSearchRequestToSearch;

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->sourceKey(),
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }
}

class ContractV2NativeAdapter implements ScientificSourceAdapterV2Interface
{
    /** @var list<ScientificSourceSearchRequest> */
    public array $requests = [];

    public function sourceKey(): string
    {
        return 'contract_v2_example';
    }

    public function displayName(): string
    {
        return 'Contract v2 example';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        return $this->searchRequest(new ScientificSourceSearchRequest($query, $limit, $options));
    }

    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome
    {
        $this->requests[] = $request;

        return new ScientificSourceSearchOutcome($this->sourceKey(), ScientificSourceSearchOutcome::STATUS_EMPTY);
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->sourceKey(),
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }
}
