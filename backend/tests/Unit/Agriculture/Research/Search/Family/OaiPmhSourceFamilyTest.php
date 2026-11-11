<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvestPage;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ADR-023 §8.39 increment R5: the OAI-PMH 2.0 harvesting family. Fixtures follow the
 * protocol's ListRecords structure; no live repository is contacted.
 */
class OaiPmhSourceFamilyTest extends TestCase
{
    private const BASE_URL = 'https://repository.example.org/oai';

    private const HARVESTED_AT = '2026-10-15T08:00:05+00:00';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(Carbon::parse(self::HARVESTED_AT));
    }

    public function test_a_first_page_sends_the_protocol_arguments_and_maps_dublin_core_with_provenance(): void
    {
        Http::fake([self::BASE_URL.'*' => Http::response(self::fixture('oai_pmh_list_records_page.xml'), 200, ['Content-Type' => 'text/xml'])]);

        $page = (new OaiPmhHarvester)->listRecords(self::profile(), from: '2026-01-01');

        Http::assertSent(static fn (Request $request): bool => self::query($request) === [
            'verb' => 'ListRecords',
            'metadataPrefix' => 'oai_dc',
            'set' => 'agri',
            'from' => '2026-01-01',
        ]);
        $this->assertSame(OaiPmhHarvestPage::STATUS_SUCCESS, $page->status);
        $this->assertCount(2, $page->records);
        $this->assertSame(['oai:repository.example.org:102'], $page->deletedIdentifiers);
        $this->assertSame(1, $page->skippedRecords, 'the record without a title is counted, not dropped silently');
        $this->assertSame('agri-2026-page-2', $page->resumptionToken);
        $this->assertSame(7, $page->completeListSize);
        $this->assertSame('2026-10-15T08:00:00Z', $page->responseDate);
        $this->assertFalse($page->isComplete());

        $first = $page->records[0];
        $this->assertSame('repository_example', $first->sourceKey);
        $this->assertSame('Barley yield under deficit irrigation', $first->title);
        $this->assertSame('10.12345/example.101', $first->doi);
        $this->assertSame('10.12345/example.101', $first->sourceIdentifier);
        $this->assertSame('https://repository.example.org/article/view/101', $first->canonicalUrl);
        $this->assertSame(['Researcher, Ada', 'Scientist, Ben'], $first->authors);
        $this->assertSame(2025, $first->publicationYear);
        $this->assertSame('Field trials of barley under deficit irrigation in three seasons.', $first->abstract);
        $this->assertNull($first->journal, 'dc:source is not assumed to be a journal title');
        $this->assertSame(['Copyright (c) 2025 The authors'], $first->rawMetadata['repository_example']['dc']['rights']);

        $record = $first->primaryProvenance();
        $this->assertNotNull($record);
        $this->assertSame('oai:repository.example.org:101', $record->originalIdentifier);
        $this->assertSame('header.identifier', $record->originalIdentifierField);
        $this->assertSame('https://repository.example.org/article/view/101', $record->recordUrl);
        $this->assertSame('2025-11-20', $record->publishedAt);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_DOI, $record->identifierBasis);
        $this->assertSame(ScientificSourceRecordProvenance::URL_PROVIDER, $record->canonicalUrlBasis);
        $this->assertSame(
            ['title_tags_and_whitespace_stripped', 'doi_resolver_prefix_removed', 'doi_lowercased'],
            $record->transformations,
        );
        $this->assertSame(self::HARVESTED_AT, $record->retrievedAt);
        $this->assertSame(OaiPmhHarvester::class, $record->adapterClass);
        $this->assertSame(OaiPmhHarvester::CONTRACT_VERSION, $record->contractVersion);
        $this->assertSame('oai_pmh', $record->accessFamily);
        $this->assertTrue($record->effectiveLicensing()->isFullyUnresolved(), 'a record rights statement is not a licence decision');

        $bare = $page->records[1];
        $this->assertSame('oai:repository.example.org:103', $bare->sourceIdentifier);
        $this->assertNull($bare->doi);
        $this->assertNull($bare->canonicalUrl);
        $this->assertNull($bare->publicationYear);
        $this->assertSame(ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER, $bare->primaryProvenance()?->identifierBasis);
    }

    public function test_a_following_page_sends_only_the_resumption_token_and_an_empty_token_ends_the_list(): void
    {
        Http::fake([self::BASE_URL.'*' => Http::response(self::fixture('oai_pmh_last_page.xml'), 200)]);

        $page = (new OaiPmhHarvester)->listRecords(self::profile(), resumptionToken: 'agri-2026-page-2');

        Http::assertSent(static fn (Request $request): bool => self::query($request) === [
            'verb' => 'ListRecords',
            'resumptionToken' => 'agri-2026-page-2',
        ]);
        $this->assertTrue($page->isComplete());
        $this->assertNull($page->resumptionToken);
        $record = $page->records[0]->primaryProvenance();
        $this->assertSame('10.12345/example.105', $page->records[0]->doi);
        $this->assertSame('https://doi.org/10.12345/example.105', $page->records[0]->canonicalUrl);
        $this->assertSame(ScientificSourceRecordProvenance::URL_DOI_RESOLVER, $record?->canonicalUrlBasis);
        $this->assertSame(['doi_resolver_prefix_removed'], $record->transformations);
    }

    public function test_no_records_match_is_an_empty_complete_list_not_a_failure(): void
    {
        Http::fake([self::BASE_URL.'*' => Http::response(self::fixture('oai_pmh_no_records_match.xml'), 200)]);

        $page = (new OaiPmhHarvester)->listRecords(self::profile(), from: '2030-01-01');

        $this->assertSame(OaiPmhHarvestPage::STATUS_EMPTY, $page->status);
        $this->assertNull($page->failureCategory);
        $this->assertSame(['noRecordsMatch'], $page->errorCodes);
        $this->assertTrue($page->isComplete());
    }

    /** @return array<string, array{\Closure(): void, ScientificSourceFailureCategory, string, bool}> */
    public static function failures(): array
    {
        return [
            'protocol error' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response(self::fixture('oai_pmh_bad_resumption_token.xml'), 200)]), ScientificSourceFailureCategory::REQUEST_REJECTED, 'oai_pmh_error', false],
            'flow control' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response('', 503, ['Retry-After' => '30'])]), ScientificSourceFailureCategory::RATE_LIMITED, 'http_error', true],
            'upstream error' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response('', 500)]), ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE, 'http_error', true],
            'not XML' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response('<html><body>maintenance', 200)]), ScientificSourceFailureCategory::MALFORMED_RESPONSE, 'malformed_response', false],
            'not OAI-PMH' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response('<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom"/>', 200)]), ScientificSourceFailureCategory::MALFORMED_RESPONSE, 'malformed_response', false],
            'entity declaration' => [static fn () => Http::fake([self::BASE_URL.'*' => Http::response(
                '<?xml version="1.0"?><!DOCTYPE OAI-PMH [<!ENTITY x SYSTEM "file:///etc/passwd">]><OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"><ListRecords>&x;</ListRecords></OAI-PMH>',
                200,
            )]), ScientificSourceFailureCategory::MALFORMED_RESPONSE, 'malformed_response', false],
            'connection failure' => [static fn () => Http::fake(static fn () => throw new ConnectionException('refused')), ScientificSourceFailureCategory::TRANSPORT, 'request_exception', true],
        ];
    }

    #[DataProvider('failures')]
    public function test_each_failure_keeps_its_typed_category_and_retryability(\Closure $fake, ScientificSourceFailureCategory $category, string $error, bool $retryable): void
    {
        $fake();

        $page = (new OaiPmhHarvester)->listRecords(self::profile());

        $this->assertSame(OaiPmhHarvestPage::STATUS_FAILED, $page->status);
        $this->assertSame($category, $page->failureCategory);
        $this->assertSame($error, $page->error);
        $this->assertSame($retryable, $page->toArray()['retryable']);
        $this->assertSame([], $page->records);
        $this->assertFalse($page->isComplete(), 'a failed page never claims the list ended');
    }

    public function test_the_repository_error_code_is_kept(): void
    {
        Http::fake([self::BASE_URL.'*' => Http::response(self::fixture('oai_pmh_bad_resumption_token.xml'), 200)]);

        $page = (new OaiPmhHarvester)->listRecords(self::profile(), resumptionToken: 'expired');

        $this->assertSame(['badResumptionToken'], $page->errorCodes);
    }

    public function test_a_defect_is_not_turned_into_a_source_failure(): void
    {
        Http::fake(static fn () => throw new \LogicException('defect inside the request pipeline'));

        $this->expectException(\LogicException::class);
        (new OaiPmhHarvester)->listRecords(self::profile());
    }

    /** @return array<string, array{?string, ?string, ?string}> */
    public static function invalidArguments(): array
    {
        return [
            'token with from' => ['2026-01-01', null, 'token'],
            'empty token' => [null, null, ' '],
            'unsupported date format' => ['01/01/2026', null, null],
            'mixed granularity' => ['2026-01-01', '2026-02-01T00:00:00Z', null],
            'from after until' => ['2026-03-01', '2026-02-01', null],
        ];
    }

    #[DataProvider('invalidArguments')]
    public function test_invalid_protocol_arguments_are_rejected_before_any_request(?string $from, ?string $until, ?string $token): void
    {
        Http::fake();

        try {
            (new OaiPmhHarvester)->listRecords(self::profile(), $from, $until, $token);
            $this->fail('expected a contract violation');
        } catch (ScientificSourceContractViolation) {
            Http::assertNothingSent();
        }
    }

    /** @return array<string, array{string, ?string}> */
    public static function invalidProfiles(): array
    {
        return [
            'plain http' => ['http://repository.example.org/oai', null],
            'query string' => [self::BASE_URL.'?verb=Identify', null],
            'set with spaces' => [self::BASE_URL, 'agri crops'],
        ];
    }

    #[DataProvider('invalidProfiles')]
    public function test_an_invalid_profile_is_rejected(string $baseUrl, ?string $setSpec): void
    {
        $this->expectException(ScientificSourceContractViolation::class);
        new OaiPmhSourceProfile('repository_example', 'Example repository', $baseUrl, $setSpec);
    }

    public function test_the_profile_exports_its_family_and_unresolved_licensing(): void
    {
        $exported = self::profile()->toArray();

        $this->assertSame('oai_pmh', $exported['access_family']);
        $this->assertSame('oai_dc', $exported['metadata_prefix']);
        $this->assertNull($exported['licensing']['licence_identifier']);
        $this->assertSame('unresolved', $exported['licensing']['usages']['redistribution']);
    }

    private static function profile(): OaiPmhSourceProfile
    {
        return new OaiPmhSourceProfile('repository_example', 'Example repository (fixture)', self::BASE_URL, 'agri');
    }

    private static function fixture(string $name): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/'.$name));
    }

    /** @return array<string, mixed> */
    private static function query(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }
}
