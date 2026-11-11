<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureClassifier;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonCredential;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonSourceProfile;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistryInvariantViolation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ADR-023 §8.39 increment R5: the REST/JSON source family. A new REST source is a profile and a
 * registration; shared orchestration, selection and evaluation are not changed for it.
 */
class RestJsonSourceFamilyTest extends TestCase
{
    private const ENDPOINT = 'https://catalogue.example.org/api/search';

    private const SECRET = 'family-secret-value-123';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_a_fixture_page_maps_to_results_with_provenance_and_the_query_is_sent_unchanged(): void
    {
        Http::fake([self::ENDPOINT.'*' => Http::response($this->fixture(), 200)]);
        $adapter = new RestJsonScientificSourceAdapter(self::profile());

        $outcome = $adapter->searchRequest(new ScientificSourceSearchRequest('barley production quantity Brazil 2019', 50));

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertCount(2, $outcome->results, 'the item without a title is not a result');
        Http::assertSent(static fn (Request $request): bool => str_starts_with($request->url(), self::ENDPOINT)
            && self::query($request) === ['format' => 'json', 'q' => 'barley production quantity Brazil 2019', 'rows' => '20']);

        $first = $outcome->results[0];
        $this->assertSame('agri_catalogue', $first->sourceKey);
        $this->assertSame('Barley production quantity Brazil 2019', $first->title);
        $this->assertSame('10.54321/rest.1', $first->doi);
        $this->assertSame('10.54321/rest.1', $first->sourceIdentifier);
        $this->assertSame('https://catalogue.example.org/records/REC-1', $first->canonicalUrl);
        $this->assertSame(['Researcher, Ada', 'Scientist, Ben'], $first->authors);
        $this->assertSame(2020, $first->publicationYear);
        $this->assertSame('Example Statistical Bulletin', $first->journal);

        $record = $first->primaryProvenance();
        $this->assertNotNull($record);
        $this->assertSame('REC-1', $record->originalIdentifier);
        $this->assertSame('key', $record->originalIdentifierField);
        $this->assertSame('https://catalogue.example.org/records/REC-1', $record->recordUrl);
        $this->assertSame('2020-02-14', $record->publishedAt);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DOI, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_PROVIDER, $record->canonicalUrlBasis);
        $this->assertSame(
            ['title_tags_and_whitespace_stripped', 'doi_resolver_prefix_removed', 'doi_lowercased'],
            $record->transformations,
        );
        $this->assertSame('rest_api', $record->accessFamily);
        $this->assertTrue($record->effectiveLicensing()->isFullyUnresolved());
    }

    public function test_missing_metadata_stays_unknown_and_nothing_is_invented(): void
    {
        Http::fake([self::ENDPOINT.'*' => Http::response($this->fixture(), 200)]);

        $second = (new RestJsonScientificSourceAdapter(self::profile()))->search('barley', 5)->results[1];

        $this->assertSame('202', $second->sourceIdentifier, 'an integer key is kept as the provider gave it');
        $this->assertNull($second->doi);
        $this->assertNull($second->canonicalUrl);
        $this->assertNull($second->abstract);
        $this->assertNull($second->publicationYear);
        $this->assertNull($second->journal);
        $this->assertSame([], $second->authors);
        $record = $second->primaryProvenance();
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER, $record?->identifierBasis);
        $this->assertNull($record->canonicalUrlBasis);
        $this->assertNull($record->recordUrl);
        $this->assertSame([], $record->transformations);
    }

    public function test_a_missing_credential_is_not_configured_and_sends_nothing(): void
    {
        Http::fake();
        config(['services.agri_catalogue.key' => null]);

        $outcome = (new RestJsonScientificSourceAdapter(self::profile(credential: self::credential())))->search('barley', 5);

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('missing_api_key', $outcome->error);
        $this->assertSame(ScientificSourceFailureCategory::NOT_CONFIGURED, ScientificSourceFailureClassifier::classifyOutcome($outcome));
        Http::assertNothingSent();
    }

    public function test_a_credential_is_sent_where_declared_and_never_reported(): void
    {
        config(['services.agri_catalogue.key' => self::SECRET]);
        Http::fake([self::ENDPOINT.'*' => Http::response($this->fixture(), 200)]);
        $profile = self::profile(credential: self::credential());

        $outcome = (new RestJsonScientificSourceAdapter($profile))->search('barley', 5);

        Http::assertSent(static fn (Request $request): bool => $request->hasHeader('X-Api-Key', self::SECRET)
            && ! str_contains($request->url(), self::SECRET));
        $this->assertStringNotContainsString(self::SECRET, (string) json_encode($outcome->toArray()));
        $this->assertStringNotContainsString(self::SECRET, (string) json_encode($profile->toArray()));
        $this->assertSame('services.agri_catalogue.key', $profile->toArray()['credential']['config_key']);
    }

    /** @return array<string, array{\Closure(): void, string, ?string, ScientificSourceFailureCategory|null}> */
    public static function responses(): array
    {
        return [
            'rate limited' => [static fn () => Http::fake([self::ENDPOINT.'*' => Http::response([], 429)]), ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, 'rate_limited', ScientificSourceFailureCategory::RATE_LIMITED],
            'upstream error' => [static fn () => Http::fake([self::ENDPOINT.'*' => Http::response([], 503)]), ScientificSourceSearchOutcome::STATUS_FAILED, 'http_error', ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE],
            'list not where the profile says' => [static fn () => Http::fake([self::ENDPOINT.'*' => Http::response(['results' => []], 200)]), ScientificSourceSearchOutcome::STATUS_FAILED, 'malformed_or_unusable_results', ScientificSourceFailureCategory::MALFORMED_RESPONSE],
            'empty list' => [static fn () => Http::fake([self::ENDPOINT.'*' => Http::response(['data' => ['items' => []]], 200)]), ScientificSourceSearchOutcome::STATUS_EMPTY, null, null],
            'connection failure' => [static fn () => Http::fake(static fn () => throw new ConnectionException('refused '.self::SECRET)), ScientificSourceSearchOutcome::STATUS_FAILED, 'request_exception', ScientificSourceFailureCategory::TRANSPORT],
        ];
    }

    #[DataProvider('responses')]
    public function test_each_source_failure_is_a_typed_outcome(\Closure $fake, string $status, ?string $error, ?ScientificSourceFailureCategory $category): void
    {
        $fake();

        $outcome = (new RestJsonScientificSourceAdapter(self::profile()))->search('barley', 5);

        $this->assertSame($status, $outcome->status);
        $this->assertSame($error, $outcome->error);
        $this->assertSame($category, ScientificSourceFailureClassifier::classifyOutcome($outcome));
        $this->assertSame([], $outcome->results);
        $this->assertStringNotContainsString(self::SECRET, (string) json_encode($outcome->toArray()));
    }

    public function test_a_defect_is_not_turned_into_a_source_failure(): void
    {
        Http::fake(static fn () => throw new \LogicException('defect inside the request pipeline'));

        $this->expectException(\LogicException::class);
        (new RestJsonScientificSourceAdapter(self::profile()))->search('barley', 5);
    }

    public function test_an_empty_query_sends_nothing(): void
    {
        Http::fake();

        $outcome = (new RestJsonScientificSourceAdapter(self::profile()))->search('   ', 5);

        $this->assertSame('empty_query', $outcome->error);
        Http::assertNothingSent();
    }

    public function test_the_descriptor_states_only_what_the_profile_declares(): void
    {
        $descriptor = (new RestJsonScientificSourceAdapter(self::profile()))->descriptor();

        $this->assertSame('agri_catalogue', $descriptor->sourceKey);
        $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $descriptor->contractVersion);
        $this->assertSame(20, $descriptor->maxResultsPerRequest);
        $this->assertNull($descriptor->paginationSupported);
        $this->assertNull($descriptor->requestsPerMinute);
        $this->assertTrue($descriptor->licensing->isFullyUnresolved());
    }

    /** @return array<string, array{\Closure(): RestJsonSourceProfile}> */
    public static function invalidProfiles(): array
    {
        return [
            'plain http' => [static fn () => self::profile(endpoint: 'http://catalogue.example.org/api')],
            'credentials in the URL' => [static fn () => self::profile(endpoint: 'https://user:pass@catalogue.example.org/api')],
            'query string in the URL' => [static fn () => self::profile(endpoint: self::ENDPOINT.'?api_key=x')],
            'no title path' => [static fn () => self::profile(fieldPaths: ['identifier' => 'key'])],
            'unknown field' => [static fn () => self::profile(fieldPaths: ['identifier' => 'key', 'title' => 'title', 'licence' => 'rights'])],
            'fixed parameter overrides the query' => [static fn () => self::profile(fixedParameters: ['q' => 'wheat'])],
            'credential parameter overrides the limit' => [static fn () => self::profile(credential: new RestJsonCredential('services.agri_catalogue.key', RestJsonCredential::PLACEMENT_QUERY, 'rows'))],
        ];
    }

    #[DataProvider('invalidProfiles')]
    public function test_an_invalid_profile_is_rejected(\Closure $make): void
    {
        $this->expectException(ScientificSourceContractViolation::class);
        $make();
    }

    public function test_the_registry_builds_one_family_adapter_per_registered_profile(): void
    {
        $catalog = new ScientificSourceCatalog([
            self::registration(self::profile()),
            self::registration(self::profile(sourceKey: 'agri_catalogue_two', endpoint: 'https://second.example.org/api/search')),
        ]);

        $registry = new ScientificSourceAdapterRegistry(app(), $catalog);

        $first = $registry->get('agri_catalogue');
        $second = $registry->get('agri_catalogue_two');
        $this->assertInstanceOf(RestJsonScientificSourceAdapter::class, $first);
        $this->assertInstanceOf(RestJsonScientificSourceAdapter::class, $second);
        $this->assertSame(self::ENDPOINT, $first->profile()->endpoint);
        $this->assertSame('https://second.example.org/api/search', $second->profile()->endpoint);
        $this->assertSame('rest_api', $catalog->get('agri_catalogue')?->toArray()['family_profile']['access_family']);
        $this->assertNull(ScientificSourceCatalog::default()->get('crossref')?->toArray()['family_profile']);
    }

    /** @return array<string, array{\Closure(): ScientificSourceRegistration}> */
    public static function invalidRegistrations(): array
    {
        return [
            'family adapter without a profile' => [static fn () => self::registration(null)],
            'profile on a source-specific adapter' => [static fn () => self::registration(self::profile(), adapterClass: CrossRefScientificSourceAdapter::class)],
            'profile of another source' => [static fn () => self::registration(self::profile(), sourceKey: 'other_source')],
            'profile of another family' => [static fn () => self::registration(new OaiPmhSourceProfile('agri_catalogue', 'Agri catalogue', 'https://catalogue.example.org/oai'))],
            'registration of another family' => [static fn () => self::registration(self::profile(), accessFamily: ScientificSourceAccessFamily::OAI_PMH)],
        ];
    }

    #[DataProvider('invalidRegistrations')]
    public function test_a_registration_rejects_a_profile_that_does_not_fit(\Closure $make): void
    {
        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        $make();
    }

    public function test_two_family_sources_run_through_the_production_orchestrator_and_an_unverified_one_is_denied(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15T09:00:00+00:00'));
        Http::fake([
            self::ENDPOINT.'*' => Http::response($this->fixture(), 200),
            'https://second.example.org/*' => Http::response(['data' => ['items' => [[
                'key' => 'S-9',
                'title' => 'Barley production quantity Brazil 2019 regional estimates',
                'identifiers' => ['doi' => '10.54321/second.9'],
            ]]]], 200),
        ]);
        $catalog = new ScientificSourceCatalog([
            self::registration(self::profile()),
            self::registration(self::profile(sourceKey: 'agri_catalogue_two', endpoint: 'https://second.example.org/api/search')),
            self::registration(
                self::profile(sourceKey: 'agri_unverified', endpoint: 'https://unverified.example.org/api/search'),
                capability: new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::NOT_VERIFIED,
                    'test: no verification recorded',
                ),
            ),
        ]);

        $report = $this->orchestrator($catalog)->execute($this->plan(), 5, ['agri_catalogue', 'agri_catalogue_two', 'agri_unverified']);

        $this->assertSame(['agri_catalogue', 'agri_catalogue_two'], $report->attemptedSources);
        $this->assertSame(['agri_catalogue', 'agri_catalogue_two'], $report->successfulSources);
        Http::assertSent(static fn (Request $request): bool => (self::query($request)['q'] ?? null) === 'variant-a');
        Http::assertNotSent(static fn (Request $request): bool => str_starts_with($request->url(), 'https://unverified.example.org'));
        $this->assertNotSame([], $report->results);
        foreach ($report->results as $result) {
            $record = $result->primaryProvenance();
            $this->assertNotNull($record);
            $this->assertSame('2026-10-15T09:00:00+00:00', $record->retrievedAt);
            $this->assertSame(RestJsonScientificSourceAdapter::class, $record->adapterClass);
            $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $record->contractVersion);
            $this->assertSame('rest_api', $record->accessFamily);
        }
        $denied = $report->planSummary['search_observability']['source_capability']['denied'];
        $this->assertSame(['agri_unverified'], array_column($denied, 'source_key'));
        $this->assertSame(['not_verified'], array_column($denied, 'reason'));
    }

    private function orchestrator(ScientificSourceCatalog $catalog): MultiSourceScientificSearchOrchestrator
    {
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['variant-a']);
        $builder->shouldReceive('buildFromPlan')->andReturn('variant-a');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );
    }

    /** @return array<string, mixed> */
    private static function query(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/rest_json_search_page.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, string>|null  $fieldPaths
     * @param  array<string, string>  $fixedParameters
     */
    private static function profile(
        string $sourceKey = 'agri_catalogue',
        string $endpoint = self::ENDPOINT,
        ?array $fieldPaths = null,
        array $fixedParameters = ['format' => 'json'],
        ?RestJsonCredential $credential = null,
    ): RestJsonSourceProfile {
        return new RestJsonSourceProfile(
            sourceKey: $sourceKey,
            displayName: 'Agricultural catalogue (fixture)',
            endpoint: $endpoint,
            queryParameter: 'q',
            fieldPaths: $fieldPaths ?? [
                'identifier' => 'key',
                'title' => 'title',
                'abstract' => 'summary',
                'doi' => 'identifiers.doi',
                'url' => 'links.record',
                'published' => 'issued',
                'authors' => 'people',
                'journal' => 'container',
            ],
            itemsPath: 'data.items',
            limitParameter: 'rows',
            maxResultsPerRequest: 20,
            fixedParameters: $fixedParameters,
            authorNamePath: 'name',
            credential: $credential,
        );
    }

    private static function credential(): RestJsonCredential
    {
        return new RestJsonCredential('services.agri_catalogue.key', RestJsonCredential::PLACEMENT_HEADER, 'X-Api-Key');
    }

    private static function registration(
        ?object $profile,
        ?string $sourceKey = null,
        string $adapterClass = RestJsonScientificSourceAdapter::class,
        ScientificSourceAccessFamily $accessFamily = ScientificSourceAccessFamily::REST_API,
        ?ScientificSourceCapabilityDeclaration $capability = null,
    ): ScientificSourceRegistration {
        return new ScientificSourceRegistration(
            sourceKey: $sourceKey ?? ($profile !== null ? $profile->sourceKey() : 'agri_catalogue'),
            adapterClass: $adapterClass,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: $accessFamily,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [$capability ?? new ScientificSourceCapabilityDeclaration(
                ScientificSourceOperation::SEARCH,
                ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                'test: fixture-verified search',
                '2026-10-01T00:00:00+00:00',
                '2099-01-01T00:00:00+00:00',
            )],
            familyProfile: $profile,
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley production quantity Brazil 2019',
            normalizedQuestion: 'barley production quantity Brazil 2019',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: ['question_type' => 'statistical', 'year' => '2019'],
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['agri_catalogue', 'agri_catalogue_two'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
