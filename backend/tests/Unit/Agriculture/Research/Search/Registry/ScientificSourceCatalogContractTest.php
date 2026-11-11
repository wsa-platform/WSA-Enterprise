<?php

namespace Tests\Unit\Agriculture\Research\Search\Registry;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Coexistence\CoexistenceBindingClass;
use App\Services\Agriculture\Research\Coexistence\Stage3CghiaCoexistenceBoundary;
use App\Services\Agriculture\Research\Coexistence\Stage3SourceKeyIdentityBridge;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ConfigFlagSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistryInvariantViolation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificQueryCompiler;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceProfileCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Tests\TestCase;

/**
 * ADR-023 P1-EXIT-06 increment R1: one catalog governs runtime source resolution.
 */
class ScientificSourceCatalogContractTest extends TestCase
{
    private const LEGACY_KEYS = ['openalex', 'crossref', 'semantic_scholar', 'consensus', 'fao_stat'];

    private const PRODUCTION_KEYS = ['openalex', 'crossref', 'semantic_scholar', 'consensus', 'fao_stat', 'agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'];

    public function test_production_catalog_declares_the_stage3_sources_in_registry_order(): void
    {
        $this->assertSame(self::PRODUCTION_KEYS, ScientificSourceCatalog::default()->sourceKeys());
        $this->assertSame(self::PRODUCTION_KEYS, (new ScientificSourceCatalog())->sourceKeys());
    }

    public function test_legacy_registrations_remain_external_without_adr_seat(): void
    {
        foreach (self::LEGACY_KEYS as $key) {
            $registration = ScientificSourceCatalog::default()->get($key);
            $this->assertNotNull($registration, $key);
            $this->assertSame(ScientificSourceLifecycle::LEGACY_STAGE3, $registration->lifecycle, $key);
            $this->assertNull($registration->adrId, $key);
            $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $registration->adapterContractVersion, $key);
            $this->assertSame(ScientificSourceAccessFamily::REST_API, $registration->accessFamily, $key);
            $this->assertNull($registration->toArray()['adr_id'], $key);
        }
    }

    public function test_gbif_is_governed_onboarded_and_bound_to_g5_04(): void
    {
        $registration = ScientificSourceCatalog::default()->get('gbif');
        $this->assertNotNull($registration);
        $this->assertSame(ScientificSourceLifecycle::GOVERNED_ONBOARDED, $registration->lifecycle);
        $this->assertSame('G5-04', $registration->adrId?->value);
        $this->assertSame(ScientificSourceSelectionRole::GOVERNED_EXPANSION, $registration->selectionRole);
        $this->assertSame(ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED, $registration->capabilityFor(ScientificSourceOperation::SEARCH)?->status);
        $this->assertNotNull($registration->familyProfile);
    }

    public function test_governed_seats_are_resolvable_by_adr_id_with_authoritative_ic_cap_identity_refs(): void
    {
        $catalog = ScientificSourceCatalog::default();
        $byKey = $catalog->get('gbif');
        $byAdr = $catalog->getByAdrId('G5-04');
        $this->assertNotNull($byKey);
        $this->assertSame($byKey, $byAdr);
        $this->assertContains('G5-04', $catalog->adrIds());
        $this->assertNull($catalog->getByAdrId('G2-26'), 'Membership alone does not invent a runtime registration');

        $expected = [
            'gbif' => [
                'adr_id' => 'G5-04',
                'ic_record' => 'ic-r9-20261016-G5-04-rest',
                'cap_record' => 'cap-r9-20261016-G5-04-search',
                'identity_binding' => 'adr023.canonical.seat.G5-04',
            ],
            'agris' => [
                'adr_id' => 'G1-01',
                'ic_record' => 'ic-r12-20261018-G1-01-bulk-bounded',
                'cap_record' => 'cap-r12-20261018-G1-01-search',
                'identity_binding' => 'adr023.canonical.seat.G1-01',
            ],
            'dr_duke' => [
                'adr_id' => 'G1-17',
                'ic_record' => 'ic-r12-20261018-G1-17-static-limited',
                'cap_record' => 'cap-r12-20261018-G1-17-search',
                'identity_binding' => 'adr023.canonical.seat.G1-17',
            ],
            'organic_eprints' => [
                'adr_id' => 'G1-02',
                'ic_record' => 'ic-r11-20261018-G1-02-oai_pmh',
                'cap_record' => 'cap-r11-20261018-G1-02-search',
                'identity_binding' => 'adr023.canonical.seat.G1-02',
            ],
            'bhl' => [
                'adr_id' => 'G6-20',
                'ic_record' => 'ic-r11-20261018-G6-20-rest',
                'cap_record' => 'cap-r11-20261018-G6-20-search',
                'identity_binding' => 'adr023.canonical.seat.G6-20',
            ],
        ];

        foreach ($expected as $sourceKey => $refs) {
            $registration = $catalog->get($sourceKey);
            $this->assertNotNull($registration, $sourceKey);
            $this->assertSame($registration, $catalog->getByAdrId($refs['adr_id']), $sourceKey);
            $bundle = $registration->authoritativeRecordReferences();
            $this->assertNotNull($bundle, $sourceKey);
            $this->assertSame($refs['ic_record'], $bundle['ic_record'], $sourceKey);
            $this->assertSame($refs['cap_record'], $bundle['cap_record'], $sourceKey);
            $this->assertSame($refs['identity_binding'], $bundle['identity_binding'], $sourceKey);
            $this->assertNotNull($bundle['onboarding_decision_id'], $sourceKey);
            $this->assertNotSame([], $bundle['identity_evidence_refs'], $sourceKey);

            $payload = $registration->toArray();
            $this->assertSame($refs['ic_record'], $payload['ic_record'], $sourceKey);
            $this->assertSame($refs['cap_record'], $payload['cap_record'], $sourceKey);
            $this->assertSame($refs['identity_binding'], $payload['identity_binding'], $sourceKey);
        }

        foreach (self::LEGACY_KEYS as $legacyKey) {
            $legacy = $catalog->get($legacyKey);
            $this->assertNotNull($legacy, $legacyKey);
            $this->assertNull($legacy->authoritativeRecordReferences(), $legacyKey);
            $this->assertArrayNotHasKey('ic_record', $legacy->toArray(), $legacyKey);
        }
    }

    public function test_only_the_five_legacy_sources_hold_the_legacy_basis_and_only_for_search(): void
    {
        foreach (self::LEGACY_KEYS as $key) {
            $registration = ScientificSourceCatalog::default()->get($key);
            $this->assertNotNull($registration, $key);
            $this->assertCount(1, $registration->capabilities, $key);
            $declaration = $registration->capabilityFor(ScientificSourceOperation::SEARCH);
            $this->assertNotNull($declaration, $key);
            $this->assertSame(ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS, $declaration->status, $key);
            $this->assertFalse($declaration->status->isVerified(), $key);
            $this->assertSame(ScientificSourceCatalog::LEGACY_BASIS_EVIDENCE, $declaration->evidenceReference, $key);
        }
        $legacyBasisKeys = array_keys(array_filter(
            ScientificSourceCatalog::default()->all(),
            static fn (ScientificSourceRegistration $r): bool => $r->capabilityFor(ScientificSourceOperation::SEARCH)?->status
                === ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS,
        ));
        $this->assertSame(self::LEGACY_KEYS, $legacyBasisKeys);
    }

    public function test_adr_seat_cannot_be_registered_before_an_onboarding_contract(): void
    {
        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $this->expectExceptionMessage(
            "ADR seat 'G1-01' cannot be registered for 'openalex' under lifecycle 'legacy_stage3'; GOVERNED_ONBOARDED is required (ADR-023 D-10.11)."
        );

        $this->registration('openalex', OpenAlexScientificSourceAdapter::class, adrId: AdrMembershipId::fromString('G1-01'));
    }

    public function test_rejects_non_canonical_key_foreign_class_and_unsupported_contract_version(): void
    {
        $cases = [
            "sourceKey must be lower-case snake_case; got 'OpenAlex'." => fn () => $this->registration('OpenAlex', OpenAlexScientificSourceAdapter::class),
            "sourceKey must be lower-case snake_case; got ' openalex'." => fn () => $this->registration(' openalex', OpenAlexScientificSourceAdapter::class),
            "Adapter for 'openalex' must implement ScientificSourceAdapterInterface; got 'stdClass'." => fn () => $this->registration('openalex', \stdClass::class),
            "Unsupported adapter contract version 'v0' for 'openalex'; supported: 'scientific_source_adapter.v1', 'scientific_source_adapter.v2'." => fn () => $this->registration('openalex', OpenAlexScientificSourceAdapter::class, version: 'v0'),
            "Adapter for 'catalog_contract_example' declares contract 'scientific_source_adapter.v2' but does not implement ScientificSourceAdapterV2Interface." => fn () => $this->registration(CatalogContractExampleAdapter::KEY, CatalogContractExampleAdapter::class, version: 'scientific_source_adapter.v2'),
        ];

        foreach ($cases as $message => $build) {
            try {
                $build();
                $this->fail("Expected rejection: {$message}");
            } catch (ScientificSourceRegistryInvariantViolation $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public function test_duplicate_source_keys_are_rejected(): void
    {
        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $this->expectExceptionMessage("Duplicate registration for sourceKey 'openalex'.");

        new ScientificSourceCatalog([
            $this->registration('openalex', OpenAlexScientificSourceAdapter::class),
            $this->registration('openalex', OpenAlexScientificSourceAdapter::class),
        ]);
    }

    public function test_registry_resolves_every_catalog_adapter_in_catalog_order(): void
    {
        $registry = app(ScientificSourceAdapterRegistry::class);

        $this->assertSame(self::PRODUCTION_KEYS, $registry->registeredSourceKeys());
        foreach (ScientificSourceCatalog::default()->all() as $key => $registration) {
            $adapter = $registry->get($key);
            $this->assertInstanceOf($registration->adapterClass, $adapter, $key);
            $this->assertSame($key, $adapter->sourceKey());
        }
        $this->assertInstanceOf(
            \App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter::class,
            $registry->get('agris'),
            'Bounded AGRIS is a governed runtime registration.',
        );
        $this->assertSame([], $registry->resolveMany(['hortdb', 'unknown']));
    }

    public function test_registry_rejects_an_adapter_whose_source_key_differs_from_its_registration(): void
    {
        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $this->expectExceptionMessage(
            'Adapter '.CrossRefScientificSourceAdapter::class." reports sourceKey 'crossref' but is registered as 'openalex'."
        );

        new ScientificSourceAdapterRegistry(
            $this->app,
            new ScientificSourceCatalog([$this->registration('openalex', CrossRefScientificSourceAdapter::class)]),
        );
    }

    public function test_selector_follows_catalog_roles_and_flags(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);
        $selector = app(ScientificSourceSelector::class);
        $trace = $selector->selectionTrace($this->plan());

        $this->assertSame(
            ['fao_stat', 'openalex', 'semantic_scholar', 'agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'],
            $selector->selectSources($this->plan()),
        );
        $this->assertSame(
            ['fao_stat', 'openalex', 'semantic_scholar', 'agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'],
            $trace['selected'],
        );
        $this->assertSame(['crossref'], $trace['skipped_inactive']);
        $this->assertSame(['consensus'], $trace['optional_not_selected']);

        config(['agricultural_intelligence.faostat.enabled' => false, 'agricultural_intelligence.crossref.enabled' => true]);
        $trace = $selector->selectionTrace($this->plan());
        $this->assertSame(
            ['openalex', 'crossref', 'semantic_scholar', 'agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'],
            $trace['selected'],
        );
        $this->assertSame(['fao_stat'], $trace['skipped_inactive']);
    }

    public function test_selector_selects_nothing_when_the_plan_is_not_ready(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => true]);

        $this->assertSame([], app(ScientificSourceSelector::class)->selectSources($this->plan(ready: false)));
    }

    public function test_compatibility_projections_match_the_catalog(): void
    {
        $catalog = ScientificSourceCatalog::default();

        $this->assertSame(
            $catalog->keysWithRole(ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT),
            ScientificSourceSelector::DEFAULT_INTERNET_FIRST_SOURCES,
        );
        $this->assertSame(
            $catalog->keysWithRole(ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY),
            ScientificSourceSelector::OPTIONAL_SOURCES,
        );
        $this->assertSame($catalog->sourceKeys(), Stage3SourceKeyIdentityBridge::KNOWN_STAGE3_SOURCE_KEYS);
        $this->assertSame(
            ['openalex', 'crossref', 'semantic_scholar', 'agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'],
            $catalog->keysInExecutionGroup(ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY),
        );
        foreach ($catalog->sourceKeys() as $key) {
            $this->assertNotNull(ScientificSourceProfileCatalog::get($key), "Query profile missing for {$key}");
        }
    }

    public function test_identity_bridge_and_path_family_resolve_through_the_catalog(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();
        foreach (self::LEGACY_KEYS as $key) {
            $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $bridge->resolve($key)->bindingClass, $key);
        }
        $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $bridge->resolve('gbif')->bindingClass, 'Bindings require an explicit bridge map; catalog adr_id alone does not mint SAME_AS.');
        $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $bridge->resolve('agris')->bindingClass, 'Catalog presence without explicit bridge map remains EXTERNAL_ONLY.');
        $this->assertSame(CoexistenceBindingClass::UNRESOLVED, $bridge->resolve('hortdb')->bindingClass);

        $pathFamily = new \ReflectionMethod(Stage3CghiaCoexistenceBoundary::class, 'pathFamilyForSourceKey');
        $boundary = new Stage3CghiaCoexistenceBoundary();
        $expected = [
            'openalex' => PathFamily::P12,
            'crossref' => PathFamily::P11,
            'semantic_scholar' => PathFamily::P13,
            'consensus' => PathFamily::P12,
            'fao_stat' => PathFamily::P01,
            'unregistered' => PathFamily::P12,
        ];
        foreach ($expected as $key => $family) {
            $this->assertSame($family, $pathFamily->invoke($boundary, $key), $key);
        }
    }

    public function test_a_new_source_needs_one_catalog_entry_to_be_registered_selected_and_executed(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.openalex.enabled' => false,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            // Governed expansion must be disabled so this contract fixture stays isolated.
            'agricultural_intelligence.gbif.enabled' => false,
            'agricultural_intelligence.agris.enabled' => false,
            'agricultural_intelligence.dr_duke.enabled' => false,
            'agricultural_intelligence.organic_eprints.enabled' => false,
            'agricultural_intelligence.bhl.enabled' => false,
            'catalog_contract_test.example.enabled' => true,
        ]);
        CatalogContractExampleAdapter::$calls = [];
        $catalog = new ScientificSourceCatalog([
            ...array_values(ScientificSourceCatalog::default()->all()),
            new ScientificSourceRegistration(
                sourceKey: CatalogContractExampleAdapter::KEY,
                adapterClass: CatalogContractExampleAdapter::class,
                adapterContractVersion: ScientificSourceAdapterInterface::CONTRACT_VERSION,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
                executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
                enablement: new ConfigFlagSourceEnablement('catalog_contract_test.example.enabled', false),
                pathFamily: PathFamily::P12,
                capabilities: [new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'catalog contract test fixture',
                    verifiedAt: '2026-10-01T00:00:00+00:00',
                    expiresAt: '2099-01-01T00:00:00+00:00',
                )],
            ),
        ]);
        $registry = new ScientificSourceAdapterRegistry($this->app, $catalog);
        $selector = new ScientificSourceSelector($catalog);
        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            $registry,
            $selector,
            app(ScientificSearchQueryBuilder::class),
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            new ScientificQueryCompiler(),
            new Stage3CghiaCoexistenceBoundary(),
            $catalog,
        );

        $report = $orchestrator->execute($this->plan(), 5);

        $this->assertSame([CatalogContractExampleAdapter::KEY], $report->selectedSources);
        $this->assertSame([CatalogContractExampleAdapter::KEY], $report->attemptedSources);
        $this->assertNotSame([], CatalogContractExampleAdapter::$calls);

        config(['catalog_contract_test.example.enabled' => false]);
        $this->assertSame([], $selector->selectSources($this->plan()));
    }

    private function registration(
        string $key,
        string $adapterClass,
        string $version = ScientificSourceAdapterInterface::CONTRACT_VERSION,
        ?AdrMembershipId $adrId = null,
    ): ScientificSourceRegistration {
        return new ScientificSourceRegistration(
            sourceKey: $key,
            adapterClass: $adapterClass,
            adapterContractVersion: $version,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            adrId: $adrId,
        );
    }

    private function plan(bool $ready = true): KnowledgeQueryPlan
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
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: $ready,
        );
    }
}

final class CatalogContractExampleAdapter implements ScientificSourceAdapterInterface
{
    public const KEY = 'catalog_contract_example';

    /** @var list<array{query: string, limit: int}> */
    public static array $calls = [];

    public function sourceKey(): string
    {
        return self::KEY;
    }

    public function displayName(): string
    {
        return 'Catalog contract example';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        self::$calls[] = ['query' => $query, 'limit' => $limit];

        return new ScientificSourceSearchOutcome(self::KEY, ScientificSourceSearchOutcome::STATUS_EMPTY);
    }
}
