<?php

namespace Tests\Unit\Agriculture\Research\Search\Registry;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Coexistence\Stage3CghiaCoexistenceBoundary;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ConfigFlagSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDecision;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityPolicy;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistryInvariantViolation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificQueryCompiler;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ADR-023 §8.35 (R2): capability-enforced runtime selection. Unknown never means supported.
 */
class ScientificSourceCapabilityPolicyTest extends TestCase
{
    private const VERIFIED_AT = '2026-10-01T00:00:00+00:00';

    private const EXPIRES_AT = '2026-11-01T00:00:00+00:00';

    public function test_production_sources_search_use_legacy_or_verified_governed_basis(): void
    {
        $policy = new ScientificSourceCapabilityPolicy();
        foreach (ScientificSourceCatalog::default()->all() as $key => $registration) {
            $search = $policy->decide($registration, ScientificSourceOperation::SEARCH);
            $this->assertTrue($search->allowed, $key);

            if ($registration->lifecycle === ScientificSourceLifecycle::GOVERNED_ONBOARDED) {
                // G5-04 GBIF: governed registration with unexpired VERIFIED_SUPPORTED SEARCH.
                $this->assertSame(ScientificSourceCapabilityDecision::REASON_VERIFIED_CURRENT, $search->reason, $key);
                $this->assertSame(ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED, $search->status, $key);
                $this->assertNotNull($search->evidenceReference, $key);
            } else {
                $this->assertSame(ScientificSourceCapabilityDecision::REASON_LEGACY_OPERATIONAL_BASIS, $search->reason, $key);
                $this->assertSame(ScientificSourceCatalog::LEGACY_BASIS_EVIDENCE, $search->evidenceReference, $key);
            }

            foreach (ScientificSourceOperation::cases() as $operation) {
                if ($operation === ScientificSourceOperation::SEARCH) {
                    continue;
                }
                $other = $policy->decide($registration, $operation);
                $this->assertFalse($other->allowed, "{$key} {$operation->value}");
                $this->assertSame(ScientificSourceCapabilityDecision::REASON_NOT_DECLARED, $other->reason);
            }
        }
    }

    public function test_an_undeclared_operation_is_denied(): void
    {
        $decision = (new ScientificSourceCapabilityPolicy())
            ->decide($this->registration('capability_example', []), ScientificSourceOperation::SEARCH);

        $this->assertFalse($decision->allowed);
        $this->assertSame(ScientificSourceCapabilityDecision::REASON_NOT_DECLARED, $decision->reason);
        $this->assertNull($decision->status);
    }

    /** @return array<string, array{ScientificSourceCapabilityStatus}> */
    public static function denyingStatuses(): array
    {
        $cases = [];
        foreach (ScientificSourceCapabilityStatus::cases() as $status) {
            if ($status !== ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED
                && $status !== ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS) {
                $cases[$status->value] = [$status];
            }
        }

        return $cases;
    }

    #[DataProvider('denyingStatuses')]
    public function test_every_status_other_than_current_support_or_legacy_basis_denies(ScientificSourceCapabilityStatus $status): void
    {
        $declaration = new ScientificSourceCapabilityDeclaration(
            ScientificSourceOperation::SEARCH,
            $status,
            'policy test evidence',
            verifiedAt: $status->isVerified() ? self::VERIFIED_AT : null,
        );

        $decision = (new ScientificSourceCapabilityPolicy())
            ->decide($this->registration('capability_example', [$declaration]), ScientificSourceOperation::SEARCH);

        $this->assertFalse($decision->allowed);
        $this->assertSame($status->value, $decision->reason);
        $this->assertSame($status, $decision->status);
        $this->assertSame('policy test evidence', $decision->evidenceReference);
    }

    public function test_verified_support_authorizes_until_its_evidence_expires(): void
    {
        $registration = $this->registration('capability_example', [$this->verifiedSearch()]);
        $policy = new ScientificSourceCapabilityPolicy();

        $current = $policy->decide($registration, ScientificSourceOperation::SEARCH, new DateTimeImmutable('2026-10-31T23:59:59+00:00'));
        $this->assertTrue($current->allowed);
        $this->assertSame(ScientificSourceCapabilityDecision::REASON_VERIFIED_CURRENT, $current->reason);

        $atExpiry = $policy->decide($registration, ScientificSourceOperation::SEARCH, new DateTimeImmutable(self::EXPIRES_AT));
        $this->assertFalse($atExpiry->allowed);
        $this->assertSame(ScientificSourceCapabilityDecision::REASON_EVIDENCE_STALE, $atExpiry->reason);

        $this->travelTo(new DateTimeImmutable('2026-12-01T00:00:00+00:00'));
        $stale = $policy->decide($registration, ScientificSourceOperation::SEARCH);
        $this->assertFalse($stale->allowed);
        $this->assertSame(ScientificSourceCapabilityDecision::REASON_EVIDENCE_STALE, $stale->reason);
    }

    /** @return array<string, array{\Closure(): mixed, string}> */
    public static function invalidDeclarations(): array
    {
        return [
            'empty evidence reference' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::NOT_TESTED, '  '),
                "Capability declaration for 'search' requires an evidence reference.",
            ],
            'legacy basis beyond search' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::FULL_TEXT_RETRIEVAL, ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS, 'x'),
                "The legacy operational basis covers the search operation only; got 'full_text_retrieval'.",
            ],
            'verified without verification time' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::VERIFIED_UNSUPPORTED, 'x'),
                "Verified capability 'search' requires verifiedAt.",
            ],
            'supported without expiry' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED, 'x', self::VERIFIED_AT),
                "Supported capability 'search' requires an expiresAt later than verifiedAt.",
            ],
            'supported expiring before verification' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED, 'x', self::VERIFIED_AT, '2026-09-01T00:00:00+00:00'),
                "Supported capability 'search' requires an expiresAt later than verifiedAt.",
            ],
            'date without time and offset' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::VERIFIED_UNSUPPORTED, 'x', '2026-10-01'),
                "Capability 'search' verifiedAt must be an ISO-8601 timestamp with offset; got '2026-10-01'.",
            ],
            'overflowing date' => [
                static fn () => new ScientificSourceCapabilityDeclaration(ScientificSourceOperation::SEARCH, ScientificSourceCapabilityStatus::VERIFIED_UNSUPPORTED, 'x', '2026-13-01T00:00:00+00:00'),
                "Capability 'search' verifiedAt must be an ISO-8601 timestamp with offset; got '2026-13-01T00:00:00+00:00'.",
            ],
        ];
    }

    #[DataProvider('invalidDeclarations')]
    public function test_invalid_declarations_are_rejected(\Closure $make, string $message): void
    {
        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $this->expectExceptionMessage($message);

        $make();
    }

    public function test_registration_rejects_duplicate_or_foreign_capability_entries(): void
    {
        try {
            $this->registration('capability_example', [$this->verifiedSearch(), $this->verifiedSearch()]);
            $this->fail('Duplicate declarations must be rejected.');
        } catch (ScientificSourceRegistryInvariantViolation $e) {
            $this->assertSame("Duplicate capability declaration 'search' for 'capability_example'.", $e->getMessage());
        }

        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $this->expectExceptionMessage("Capabilities of 'capability_example' must be ScientificSourceCapabilityDeclaration instances.");
        $this->registration('capability_example', ['search']);
    }

    public function test_selector_selects_only_authorized_sources_and_reports_each_denial(): void
    {
        config(['capability_policy_test.disabled' => false]);
        $selector = new ScientificSourceSelector($this->mixedCatalog());

        $this->travelTo(new DateTimeImmutable('2026-10-15T00:00:00+00:00'));
        $this->assertSame(['cap_legacy', 'cap_verified'], $selector->selectSources($this->plan()));

        $trace = $selector->selectionTrace($this->plan());
        $this->assertSame(['cap_legacy', 'cap_verified'], $trace['selected']);
        $this->assertSame(['cap_disabled'], $trace['skipped_inactive']);
        $this->assertSame(
            [
                ['cap_not_declared', ScientificSourceCapabilityDecision::REASON_NOT_DECLARED],
                ['cap_auth', ScientificSourceCapabilityStatus::AUTHENTICATION_REQUIRED->value],
            ],
            array_map(static fn (array $d): array => [$d['source_key'], $d['reason']], $trace['capability_denied']),
        );

        $this->travelTo(new DateTimeImmutable('2026-11-02T00:00:00+00:00'));
        $this->assertSame(['cap_legacy'], $selector->selectSources($this->plan()));
        $this->assertContains(
            ['cap_verified', ScientificSourceCapabilityDecision::REASON_EVIDENCE_STALE],
            array_map(static fn (array $d): array => [$d['source_key'], $d['reason']], $selector->selectionTrace($this->plan())['capability_denied']),
        );
    }

    public function test_orchestrator_enforces_capabilities_for_explicitly_requested_sources(): void
    {
        config(['capability_policy_test.disabled' => false]);
        $this->travelTo(new DateTimeImmutable('2026-10-15T00:00:00+00:00'));
        CapabilityPolicyRecordingAdapter::$calls = [];
        $catalog = $this->mixedCatalog();
        $orchestrator = $this->orchestrator($catalog);

        $report = $orchestrator->execute($this->plan(), 5, ['cap_not_declared', 'cap_legacy', 'cap_auth', 'unregistered_key']);

        $this->assertSame(['cap_legacy'], $report->attemptedSources);
        $this->assertSame(['cap_legacy'], array_keys(CapabilityPolicyRecordingAdapter::$calls));
        $capability = $report->planSummary['search_observability']['source_capability'];
        $this->assertSame('search', $capability['required_operation']);
        $this->assertSame(['cap_legacy' => ScientificSourceCapabilityDecision::REASON_LEGACY_OPERATIONAL_BASIS], $capability['basis']);
        $this->assertSame(
            [
                ['cap_not_declared', ScientificSourceCapabilityDecision::REASON_NOT_DECLARED, false],
                ['cap_auth', ScientificSourceCapabilityStatus::AUTHENTICATION_REQUIRED->value, false],
            ],
            array_map(static fn (array $d): array => [$d['source_key'], $d['reason'], $d['allowed']], $capability['denied']),
        );
    }

    public function test_when_every_source_is_denied_nothing_is_searched(): void
    {
        CapabilityPolicyRecordingAdapter::$calls = [];
        $orchestrator = $this->orchestrator($this->mixedCatalog());

        $report = $orchestrator->execute($this->plan(), 5, ['cap_not_declared', 'cap_auth']);

        $this->assertSame('no_sources_selected', $report->status);
        $this->assertSame([], $report->attemptedSources);
        $this->assertSame([], CapabilityPolicyRecordingAdapter::$calls);
        $this->assertCount(2, $report->planSummary['search_observability']['source_capability']['denied']);
    }

    private function verifiedSearch(): ScientificSourceCapabilityDeclaration
    {
        return new ScientificSourceCapabilityDeclaration(
            ScientificSourceOperation::SEARCH,
            ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
            'policy test evidence',
            self::VERIFIED_AT,
            self::EXPIRES_AT,
        );
    }

    private function mixedCatalog(): ScientificSourceCatalog
    {
        $default = ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT;

        return new ScientificSourceCatalog([
            $this->registration('cap_legacy', [ScientificSourceCapabilityDeclaration::legacyOperationalBasis('policy test')], $default),
            $this->registration('cap_not_declared', [], $default),
            $this->registration('cap_verified', [$this->verifiedSearch()], $default),
            $this->registration('cap_auth', [new ScientificSourceCapabilityDeclaration(
                ScientificSourceOperation::SEARCH,
                ScientificSourceCapabilityStatus::AUTHENTICATION_REQUIRED,
                'policy test evidence',
            )], $default),
            $this->registration(
                'cap_disabled',
                [ScientificSourceCapabilityDeclaration::legacyOperationalBasis('policy test')],
                $default,
                new ConfigFlagSourceEnablement('capability_policy_test.disabled', false),
            ),
        ]);
    }

    /**
     * @param  list<mixed>  $capabilities
     */
    private function registration(
        string $key,
        array $capabilities,
        ScientificSourceSelectionRole $role = ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
        ?ScientificSourceEnablement $enablement = null,
    ): ScientificSourceRegistration {
        return new ScientificSourceRegistration(
            sourceKey: $key,
            adapterClass: CapabilityPolicyRecordingAdapter::class,
            adapterContractVersion: ScientificSourceAdapterInterface::CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: $role,
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
            enablement: $enablement ?? new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: $capabilities,
        );
    }

    private function orchestrator(ScientificSourceCatalog $catalog): MultiSourceScientificSearchOrchestrator
    {
        return new MultiSourceScientificSearchOrchestrator(
            new CapabilityPolicyTestRegistry($catalog),
            new ScientificSourceSelector($catalog),
            app(ScientificSearchQueryBuilder::class),
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            new ScientificQueryCompiler(),
            new Stage3CghiaCoexistenceBoundary(),
            $catalog,
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley water requirement',
            normalizedQuestion: 'barley water requirement',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: ['question_type' => 'quantity', 'scientific_sense' => 'crop_water_requirement'],
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}

/**
 * Serves one recording adapter per catalog key, so many test registrations share one adapter class.
 */
final class CapabilityPolicyTestRegistry extends ScientificSourceAdapterRegistry
{
    /** @var array<string, CapabilityPolicyRecordingAdapter> */
    private array $byKey = [];

    public function __construct(ScientificSourceCatalog $catalog)
    {
        foreach ($catalog->sourceKeys() as $key) {
            $this->byKey[$key] = new CapabilityPolicyRecordingAdapter($key);
        }
    }

    public function registeredSourceKeys(): array
    {
        return array_keys($this->byKey);
    }

    public function get(string $sourceKey): ?ScientificSourceAdapterInterface
    {
        return $this->byKey[$sourceKey] ?? null;
    }
}

final class CapabilityPolicyRecordingAdapter implements ScientificSourceAdapterInterface
{
    /** @var array<string, list<string>> */
    public static array $calls = [];

    public function __construct(private readonly string $key = 'cap_legacy') {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return 'Capability policy recording adapter';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        self::$calls[$this->key][] = $query;

        return new ScientificSourceSearchOutcome($this->key, ScientificSourceSearchOutcome::STATUS_EMPTY);
    }
}
