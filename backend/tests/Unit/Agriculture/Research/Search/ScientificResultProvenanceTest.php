<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatObservation;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * ADR-023 §8.38 increment R4: source provenance, licensing status and source errors survive
 * normalization, deduplication, validation and presentation, and nothing unknown is invented.
 */
class ScientificResultProvenanceTest extends TestCase
{
    private const RETRIEVED_AT = '2026-10-15T01:02:03+00:00';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_openalex_record_keeps_the_provider_identity_and_names_each_transformation(): void
    {
        $result = $this->normalizer()->fromOpenAlexWork([
            'id' => 'https://openalex.org/W123',
            'display_name' => '<i>Wheat</i> yield under drought',
            'doi' => 'https://doi.org/10.1000/ABC.Def',
            'publication_year' => 2021,
            'publication_date' => '2021-03-04',
            'abstract_inverted_index' => ['Wheat' => [0], 'yield' => [1]],
        ]);

        $record = $result?->primaryProvenance();
        $this->assertNotNull($record);
        $this->assertSame('https://openalex.org/W123', $record->originalIdentifier);
        $this->assertSame('id', $record->originalIdentifierField);
        $this->assertSame('https://openalex.org/W123', $record->recordUrl);
        $this->assertSame('2021-03-04', $record->publishedAt);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_PROVIDER, $record->canonicalUrlBasis);
        $this->assertSame([
            'title_tags_and_whitespace_stripped',
            'doi_resolver_prefix_removed',
            'doi_lowercased',
            'abstract_reconstructed_from_inverted_index',
        ], $record->transformations);
        $this->assertSame('10.1000/abc.def', $result->doi);
        $this->assertNull($record->retrievedAt, 'retrieval is stamped by the orchestrator, not the normalizer');
    }

    public function test_missing_metadata_stays_unknown_and_nothing_is_invented(): void
    {
        $result = $this->normalizer()->fromCrossRefWork(['title' => ['Soil carbon under no-till']]);

        $this->assertNotNull($result);
        $this->assertNull($result->sourceIdentifier);
        $this->assertNull($result->doi);
        $this->assertNull($result->canonicalUrl);
        $record = $result->primaryProvenance();
        $this->assertNotNull($record);
        $this->assertNull($record->originalIdentifier);
        $this->assertNull($record->recordUrl);
        $this->assertNull($record->publishedAt);
        $this->assertNull($record->identifierBasis);
        $this->assertNull($record->canonicalUrlBasis);
        $this->assertSame([], $record->transformations);
        $this->assertTrue($record->effectiveLicensing()->isFullyUnresolved());
    }

    public function test_crossref_record_keeps_date_precision_and_discloses_a_subtitle_used_as_abstract(): void
    {
        $result = $this->normalizer()->fromCrossRefWork([
            'title' => ['Maize nitrogen response'],
            'DOI' => '10.5555/XYZ',
            'URL' => 'http://dx.doi.org/10.5555/xyz',
            'issued' => ['date-parts' => [[2020, 5]]],
            'subtitle' => ['A field trial'],
        ]);

        $record = $result?->primaryProvenance();
        $this->assertNotNull($record);
        $this->assertSame('10.5555/XYZ', $record->originalIdentifier, 'the DOI as Crossref returned it');
        $this->assertSame('DOI', $record->originalIdentifierField);
        $this->assertSame('http://dx.doi.org/10.5555/xyz', $record->recordUrl);
        $this->assertSame('2020-05', $record->publishedAt);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DOI, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_DOI_RESOLVER, $record->canonicalUrlBasis);
        $this->assertSame(['doi_lowercased', 'abstract_taken_from_subtitle'], $record->transformations);
        $this->assertSame('A field trial', $result->abstract);
    }

    public function test_a_derived_identifier_is_disclosed_as_derived_and_never_as_the_providers(): void
    {
        $consensus = $this->normalizer()->fromConsensusWork(['title' => 'Rice blast resistance', 'publish_year' => 2019]);
        $record = $consensus?->primaryProvenance();

        $this->assertNotNull($record);
        $this->assertStringStartsWith('consensus:', (string) $consensus->sourceIdentifier);
        $this->assertNull($record->originalIdentifier);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DERIVED_HASH, $record->identifierBasis);
        $this->assertNull($record->canonicalUrlBasis);

        $scholar = $this->normalizer()->fromSemanticScholarWork([
            'paperId' => 'abc123',
            'title' => 'Cassava mosaic',
            'externalIds' => ['DOI' => '10.1/Cas'],
            'url' => 'https://www.semanticscholar.org/paper/abc123',
        ]);
        $record = $scholar?->primaryProvenance();

        $this->assertNotNull($record);
        $this->assertSame('10.1/cas', $scholar->sourceIdentifier);
        $this->assertSame('abc123', $record->originalIdentifier, 'the provider id is kept even when the DOI keys the result');
        $this->assertSame('paperId', $record->originalIdentifierField);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DOI, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_PROVIDER, $record->canonicalUrlBasis);
    }

    public function test_faostat_identifier_and_url_are_disclosed_as_derived_from_codes(): void
    {
        $observation = new FaoStatObservation(
            domainCode: 'QCL', domain: 'Crops', areaCode: '106', area: 'Italy', itemCode: '15', item: 'Wheat',
            queryElementCode: '2510', responseElementCode: '5510', element: 'Production', yearCode: '2022', year: '2022',
            unit: 't', value: '6609520', flag: 'A', flagDescription: 'Official figure', note: '', raw: [],
            provenance: ['query_url' => 'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106', 'retrieved_at' => self::RETRIEVED_AT],
        );
        $method = new \ReflectionMethod(FaoStatDeveloperPortalAdapter::class, 'toSearchResult');

        /** @var ScientificSearchResult $result */
        $result = $method->invoke(app(FaoStatDeveloperPortalAdapter::class), $observation, 'wheat production italy');
        $record = $result->primaryProvenance();

        $this->assertNotNull($record);
        $this->assertNull($record->originalIdentifier);
        $this->assertNull($record->recordUrl);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DERIVED_CODES, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_CONSTRUCTED, $record->canonicalUrlBasis);
        $this->assertSame(self::RETRIEVED_AT, $record->retrievedAt);
        $this->assertSame(['title_composed_from_dimension_labels', 'abstract_composed_from_value_and_unit'], $record->transformations);
    }

    public function test_orchestrator_stamps_retrieval_adapter_access_family_and_unresolved_licensing(): void
    {
        $this->travelTo(Carbon::parse(self::RETRIEVED_AT));
        $openAlex = new ProvenanceV2Adapter('openalex', fn (): array => [$this->normalizer()->fromOpenAlexWork([
            'id' => 'https://openalex.org/W9',
            'display_name' => 'Barley production quantity Brazil 2019',
        ])]);
        $crossref = new ProvenanceV1Adapter('crossref', static fn (): ScientificSourceSearchOutcome => self::bareSuccess('crossref'));

        $report = $this->orchestrator([$openAlex, $crossref])->execute($this->plan(), 5, ['openalex', 'crossref']);

        $byKey = [];
        foreach ($report->results as $result) {
            $byKey[$result->sourceKey] = $result->primaryProvenance();
        }
        $this->assertSame(['openalex', 'crossref'], array_keys($byKey));

        $openAlexRecord = $byKey['openalex'];
        $this->assertSame(self::RETRIEVED_AT, $openAlexRecord->retrievedAt);
        $this->assertSame(ProvenanceV2Adapter::class, $openAlexRecord->adapterClass);
        $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $openAlexRecord->contractVersion);
        $this->assertSame('rest_api', $openAlexRecord->accessFamily);
        $this->assertSame('https://openalex.org/W9', $openAlexRecord->originalIdentifier);

        $crossrefRecord = $byKey['crossref'];
        $this->assertNotNull($crossrefRecord, 'a result the adapter built without provenance still gains a record');
        $this->assertSame(self::RETRIEVED_AT, $crossrefRecord->retrievedAt);
        $this->assertSame(ScientificSourceAdapterInterface::CONTRACT_VERSION, $crossrefRecord->contractVersion);
        $this->assertNull($crossrefRecord->originalIdentifier);

        foreach ([$openAlexRecord, $crossrefRecord] as $record) {
            foreach (ScientificSourceUsage::cases() as $usage) {
                $this->assertFalse($record->effectiveLicensing()->permits($usage), 'unknown licensing permits nothing');
            }
        }
        $outcome = $report->toArray()['source_outcomes'][0];
        $this->assertSame('openalex', $outcome['source']);
        $this->assertSame(self::RETRIEVED_AT, $outcome['results'][0]['provenance'][0]['retrieved_at']);
        $this->assertSame('unresolved', $outcome['results'][0]['provenance'][0]['licensing']['usages']['redistribution']);
    }

    public function test_a_failed_source_is_reported_and_the_other_sources_provenance_survives(): void
    {
        $openAlex = new ProvenanceV1Adapter('openalex', static fn (): ScientificSourceSearchOutcome => new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            error: 'rate_limited',
            httpStatus: 429,
        ));
        $crossref = new ProvenanceV1Adapter('crossref', static fn (): ScientificSourceSearchOutcome => self::bareSuccess('crossref'));

        $report = $this->orchestrator([$openAlex, $crossref])->execute($this->plan(), 5, ['openalex', 'crossref']);

        $this->assertSame(['openalex'], $report->failedSources);
        $this->assertNotSame([], $report->results);
        foreach ($report->results as $result) {
            $this->assertSame('crossref', $result->sourceKey);
            $this->assertNotNull($result->primaryProvenance()?->retrievedAt);
        }

        $coverage = ScientificUserPresentation::sourceCoverage($report);
        $this->assertFalse($coverage['complete']);
        $this->assertSame(['crossref'], $coverage['responded_sources']);
        $this->assertSame(['openalex'], $coverage['failed_sources']);
        $this->assertSame(['openalex' => ['rate_limited']], $coverage['failure_categories']);
        $this->assertSame('partial_source_coverage', $coverage['notice_code']);
    }

    public function test_coverage_with_no_selected_source_is_not_complete(): void
    {
        $report = new ScientificSearchExecutionReport(
            status: 'no_sources_selected', searchQuery: 'q', selectedSources: [], attemptedSources: [],
            successfulSources: [], failedSources: [], emptySources: [], sourceOutcomes: [], results: [],
            deduplicatedResults: [], planSummary: [],
        );

        $coverage = ScientificUserPresentation::sourceCoverage($report);

        $this->assertFalse($coverage['complete']);
        $this->assertSame('no_sources_searched', $coverage['notice_code']);
    }

    public function test_deduplication_keeps_every_source_record_and_the_first_retrieval_of_a_repeat(): void
    {
        $first = self::record('openalex', 'https://openalex.org/W1', '2026-10-15T01:00:00+00:00');
        $repeat = self::record('openalex', 'https://openalex.org/W1', '2026-10-15T01:00:09+00:00');
        $crossref = self::record('crossref', '10.1/x', '2026-10-15T01:00:05+00:00');

        $merged = (new ScientificResultDeduplicator)->deduplicate([
            self::searchResult('openalex', '10.1/x', [$first]),
            self::searchResult('crossref', '10.1/x', [$crossref]),
            self::searchResult('openalex', '10.1/x', [$repeat]),
        ]);

        $this->assertCount(1, $merged);
        $this->assertSame(['openalex', 'crossref'], $merged[0]->foundBySources);
        $this->assertSame(
            [['openalex', '2026-10-15T01:00:00+00:00'], ['crossref', '2026-10-15T01:00:05+00:00']],
            array_map(static fn (ScientificSourceRecordProvenance $r): array => [$r->sourceKey, $r->retrievedAt], $merged[0]->provenance),
        );
    }

    public function test_validation_carries_the_source_retrieval_time_and_records_into_evidence(): void
    {
        $stamped = self::searchResult('openalex', '10.1/wheat', [self::record('openalex', 'https://openalex.org/W5', self::RETRIEVED_AT)]);
        $unstamped = self::searchResult('crossref', '10.1/other', []);

        $items = $this->validate([$stamped, $unstamped]);

        $byKey = [];
        foreach ($items as $item) {
            $byKey[$item->sourceKey] = $item;
        }
        $this->assertSame(self::RETRIEVED_AT, $byKey['openalex']->retrievedAt);
        $provenance = $byKey['openalex']->sourceAttribution['provenance'];
        $this->assertSame('source_retrieval', $provenance['retrieved_at_basis']);
        $this->assertSame('https://openalex.org/W5', $provenance['records'][0]['original_identifier']);
        $this->assertSame('validation_time', $byKey['crossref']->sourceAttribution['provenance']['retrieved_at_basis']);
        $this->assertSame([], $byKey['crossref']->sourceAttribution['provenance']['records']);
    }

    public function test_presentation_reports_unresolved_licensing_for_a_result_without_declarations(): void
    {
        $item = self::evidence([
            'found_by_sources' => ['openalex'],
            'retrieved_at_basis' => 'source_retrieval',
            'records' => [self::record('openalex', 'https://openalex.org/W7', self::RETRIEVED_AT)->toArray()],
        ]);

        $rows = ScientificUserPresentation::researchResultsFromValidatedEvidence([$item]);

        $this->assertCount(1, $rows);
        $disclosure = $rows[0]['provenance'];
        $this->assertSame('unresolved', $disclosure['licensing_status']);
        $this->assertSame(array_fill_keys(self::usageKeys(), 'unresolved'), $disclosure['usage']);
        $this->assertSame('https://openalex.org/W7', $disclosure['records'][0]['original_identifier']);
        $this->assertSame('https://openalex.org/W7', $disclosure['records'][0]['record_url']);
        $this->assertSame(self::RETRIEVED_AT, $disclosure['records'][0]['retrieved_at']);
        $this->assertSame('source_retrieval', $disclosure['retrieved_at_basis']);
        $this->assertNull($rows[0]['abstract'], 'unresolved licensing does not permit excerpt display');
        $this->assertSame('excerpt_display_unresolved', $rows[0]['abstract_withheld_reason']);
        $this->assertSame('https://openalex.org/W7', $rows[0]['result_id'], 'withholding changes no identity');
    }

    public function test_abstract_is_shown_only_when_every_contributing_source_permits_excerpt_display(): void
    {
        $permitted = self::licensedRecord('openalex', 'CC0-1.0', ['excerpt_display' => 'permitted']);
        $prohibited = self::licensedRecord('crossref', 'publisher-terms', ['excerpt_display' => 'prohibited']);

        $shown = ScientificUserPresentation::researchResultsFromValidatedEvidence([self::evidence(['records' => [$permitted]])]);
        $mixed = ScientificUserPresentation::researchResultsFromValidatedEvidence([self::evidence(['records' => [$permitted, $prohibited]])]);

        $this->assertSame('Wheat yield declined.', $shown[0]['abstract']);
        $this->assertNull($shown[0]['abstract_withheld_reason']);
        $this->assertNull($mixed[0]['abstract']);
        $this->assertSame('excerpt_display_prohibited', $mixed[0]['abstract_withheld_reason']);
    }

    public function test_a_result_without_an_abstract_reports_nothing_withheld(): void
    {
        $item = new ScientificEvidenceItem(
            evidenceId: 'ev-title', sourceId: 'https://openalex.org/W8', sourceKey: 'openalex', sourceType: null,
            publicationTitle: 'Same text', authors: [], institution: null, journal: null, doi: null, url: null,
            publicationYear: null, retrievedAt: self::RETRIEVED_AT, agriculturalDomain: null, claimTopic: null,
            evidenceText: 'Same text', validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE, validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED, confidence: 0.9, qualityScore: 80.0,
            qualityFactors: [], sourceAttribution: [],
        );

        $rows = ScientificUserPresentation::researchResultsFromValidatedEvidence([$item]);

        $this->assertNull($rows[0]['abstract']);
        $this->assertNull($rows[0]['abstract_withheld_reason']);
    }

    public function test_presentation_reports_the_most_restrictive_declared_usage_across_records(): void
    {
        $open = self::licensedRecord('openalex', 'CC0-1.0', array_fill_keys(self::usageKeys(), 'permitted'));
        $restricted = self::licensedRecord('crossref', 'publisher-terms', [
            'excerpt_display' => 'prohibited',
            'redistribution' => 'conditional',
            'retrieval' => 'permitted',
            'metadata_storage' => 'permitted',
            'full_text_storage' => 'permitted',
            'citation_linking' => 'permitted',
        ]);

        $disclosure = ScientificUserPresentation::provenanceDisclosure(self::evidence(['records' => [$open, $restricted]]));

        $this->assertSame('declared', $disclosure['licensing_status']);
        $this->assertSame('prohibited', $disclosure['usage']['excerpt_display']);
        $this->assertSame('conditional', $disclosure['usage']['redistribution']);
        $this->assertSame('permitted', $disclosure['usage']['citation_linking']);
        $this->assertSame(['CC0-1.0', 'publisher-terms'], array_column($disclosure['records'], 'licence'));
    }

    public function test_presentation_without_any_provenance_is_unresolved_and_invents_nothing(): void
    {
        $disclosure = ScientificUserPresentation::provenanceDisclosure(null);

        $this->assertSame('unresolved', $disclosure['licensing_status']);
        $this->assertSame([], $disclosure['records']);
        $this->assertNull($disclosure['source']);
        $this->assertNull($disclosure['retrieved_at']);
        $this->assertSame('unknown', $disclosure['retrieved_at_basis']);
    }

    /**
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificEvidenceItem>
     */
    private function validate(array $results): array
    {
        $report = new ScientificSearchExecutionReport(
            status: 'search_completed', searchQuery: 'wheat yield', selectedSources: ['openalex', 'crossref'],
            attemptedSources: ['openalex', 'crossref'], successfulSources: ['openalex', 'crossref'], failedSources: [],
            emptySources: [], sourceOutcomes: [], results: $results, deduplicatedResults: $results, planSummary: [],
        );
        $validation = app(AgriculturalScientificValidationService::class)->validate($this->plan(), $report);

        return [...$validation->validatedEvidence, ...$validation->rejectedEvidence, ...$validation->retainedEvidence];
    }

    /** @return list<string> */
    private static function usageKeys(): array
    {
        return array_map(static fn (ScientificSourceUsage $usage): string => $usage->value, ScientificSourceUsage::cases());
    }

    private function normalizer(): ScientificResultNormalizer
    {
        return app(ScientificResultNormalizer::class);
    }

    /** @param  list<ScientificSourceAdapterInterface>  $adapters */
    private function orchestrator(array $adapters): MultiSourceScientificSearchOrchestrator
    {
        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturnUsing(
            static fn (array $keys): array => array_values(array_filter(
                $adapters,
                static fn (ScientificSourceAdapterInterface $adapter): bool => in_array($adapter->sourceKey(), $keys, true),
            )),
        );
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['variant-a']);
        $builder->shouldReceive('buildFromPlan')->andReturn('variant-a');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    public static function bareSuccess(string $sourceKey): ScientificSourceSearchOutcome
    {
        return new ScientificSourceSearchOutcome(
            sourceKey: $sourceKey,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [new ScientificSearchResult(
                sourceKey: $sourceKey,
                sourceIdentifier: null,
                title: 'Barley production quantity Brazil 2019 '.$sourceKey,
                authors: [],
                publicationYear: 2019,
                doi: null,
                canonicalUrl: null,
                abstract: null,
                journal: null,
                foundBySources: [$sourceKey],
            )],
        );
    }

    private static function record(string $sourceKey, string $identifier, string $retrievedAt): ScientificSourceRecordProvenance
    {
        return new ScientificSourceRecordProvenance(
            sourceKey: $sourceKey,
            originalIdentifier: $identifier,
            recordUrl: str_starts_with($identifier, 'https://') ? $identifier : null,
            identifierBasis: ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER,
            retrievedAt: $retrievedAt,
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
        );
    }

    /**
     * @param  array<string, string>  $usages
     * @return array<string, mixed>
     */
    private static function licensedRecord(string $sourceKey, string $licence, array $usages): array
    {
        return [
            'source_key' => $sourceKey,
            'licensing' => ['licence_identifier' => $licence, 'usages' => $usages],
        ];
    }

    /** @param  list<ScientificSourceRecordProvenance>  $provenance */
    private static function searchResult(string $sourceKey, string $doi, array $provenance): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: $sourceKey,
            sourceIdentifier: $provenance[0]->originalIdentifier ?? null,
            title: 'Wheat yield response to drought in Italy',
            authors: ['A Researcher'],
            publicationYear: 2021,
            doi: $doi,
            canonicalUrl: 'https://doi.org/'.$doi,
            abstract: 'Wheat yield declined under drought stress in field trials.',
            journal: 'Field Crops Research',
            foundBySources: [$sourceKey],
            provenance: $provenance,
        );
    }

    /** @param  array<string, mixed>  $provenance */
    private static function evidence(array $provenance): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: 'ev-1',
            sourceId: 'https://openalex.org/W7',
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat yield response',
            authors: [],
            institution: null,
            journal: null,
            doi: null,
            url: null,
            publicationYear: 2021,
            retrievedAt: self::RETRIEVED_AT,
            agriculturalDomain: 'agronomy',
            claimTopic: 'yield',
            evidenceText: 'Wheat yield declined.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 80.0,
            qualityFactors: [],
            sourceAttribution: ['provenance' => $provenance],
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
            sourcePriorities: ['openalex', 'crossref'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}

class ProvenanceV1Adapter implements ScientificSourceAdapterInterface
{
    public function __construct(
        private readonly string $key,
        private readonly \Closure $behaviour,
    ) {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return 'Provenance v1 '.$this->key;
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        return ($this->behaviour)();
    }
}

class ProvenanceV2Adapter implements ScientificSourceAdapterV2Interface
{
    public function __construct(
        private readonly string $key,
        private readonly \Closure $results,
    ) {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return 'Provenance v2 '.$this->key;
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        throw new \LogicException('The orchestrator must call searchRequest() on a v2 adapter.');
    }

    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome
    {
        return new ScientificSourceSearchOutcome(
            sourceKey: $this->key,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: ($this->results)(),
        );
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->key,
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }
}
