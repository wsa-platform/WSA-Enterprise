<?php

namespace Tests\Support;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyRecord;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Illuminate\Support\Facades\Http;

/**
 * Offline profiles, licences and fixtures of the local-index lifecycle (ADR-023 §8.40, §8.41),
 * shared by the PostgreSQL suite and its worker processes. Every source is a fixture on
 * example.org; every licence is accepted for tests only.
 */
trait LocalIndexFixtures
{
    protected static function fixtureLicensing(
        ScientificSourceUsageStatus $retrieval = ScientificSourceUsageStatus::PERMITTED,
        ScientificSourceUsageStatus $storage = ScientificSourceUsageStatus::PERMITTED,
        ScientificSourceUsageStatus $fullText = ScientificSourceUsageStatus::UNRESOLVED,
    ): ScientificSourceLicensingDeclaration {
        return new ScientificSourceLicensingDeclaration(
            [
                ScientificSourceUsage::RETRIEVAL->value => $retrieval,
                ScientificSourceUsage::METADATA_STORAGE->value => $storage,
                ScientificSourceUsage::FULL_TEXT_STORAGE->value => $fullText,
            ],
            termsReference: 'https://repository.example.org/terms (fixture)',
            evidenceReference: 'test fixture: terms accepted for this test only',
        );
    }

    protected static function fixtureOaiProfile(?ScientificSourceLicensingDeclaration $licensing = null): OaiPmhSourceProfile
    {
        return new OaiPmhSourceProfile('repository_example', 'Example repository (fixture)', 'https://repository.example.org/oai', 'agri', $licensing ?? self::fixtureLicensing());
    }

    protected static function fixtureBulkProfile(string $sourceKey = 'dataset_example', ?ScientificSourceLicensingDeclaration $licensing = null): BulkDatasetSourceProfile
    {
        return new BulkDatasetSourceProfile(
            sourceKey: $sourceKey,
            displayName: 'Example dataset (fixture)',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: [
                'identifier' => 'record_id',
                'title' => 'title',
                'abstract' => 'summary',
                'doi' => 'doi',
                'url' => 'landing',
                'published' => 'issued',
                'authors' => 'creators',
                'journal' => 'venue',
                'withdrawn' => 'withdrawn',
            ],
            licensing: $licensing ?? self::fixtureLicensing(),
            maxRecords: 100,
        );
    }

    protected static function fixtureRecord(string $sourceKey, string $identifier, string $title): ScientificSearchResult
    {
        return ScientificSourceFamilyRecord::toResult(
            profile: self::fixtureBulkProfile($sourceKey),
            rawTitle: $title,
            originalIdentifier: $identifier,
            originalIdentifierField: 'record_id',
            rawDoi: null,
            rawUrl: null,
            rawAbstract: null,
            publishedAt: null,
            year: null,
            authors: [],
            journal: null,
            raw: ['record_id' => $identifier, 'title' => $title],
        );
    }

    protected static function fixturePath(string $name): string
    {
        return dirname(__DIR__).'/Fixtures/ScientificSourceFamilies/'.$name;
    }

    /** @param  list<string|int>  $responses  fixture names or HTTP status codes, served in order */
    protected static function fakeOaiResponses(array $responses): void
    {
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            is_int($response)
                ? $sequence->push('', $response)
                : $sequence->push((string) file_get_contents(self::fixturePath($response)), 200, ['Content-Type' => 'text/xml']);
        }
        Http::fake(['https://repository.example.org/oai*' => $sequence]);
    }
}
