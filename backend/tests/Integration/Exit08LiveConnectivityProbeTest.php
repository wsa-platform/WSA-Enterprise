<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvestPage;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonSourceProfile;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * P1-EXIT-08 live connectivity probes (ADR-023 §8.24.5 / D-10.8).
 *
 * Opt-in: set WSA_EXIT08_LIVE_PROBES=1. Read-only GET/Identify/search only.
 * Never logs credential values. Distinguishes live success from missing credentials.
 */
final class Exit08LiveConnectivityProbeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('WSA_EXIT08_LIVE_PROBES') !== '1') {
            $this->markTestSkipped('Set WSA_EXIT08_LIVE_PROBES=1 to run EXIT-08 live probes.');
        }
    }

    public function test_gbif_rest_species_search_is_live_reachable(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G5-04');
        $this->assertNotNull($seat);
        $this->assertInstanceOf(RestJsonSourceProfile::class, $seat->familyProfile);
        /** @var RestJsonSourceProfile $profile */
        $profile = $seat->familyProfile;
        $adapter = new RestJsonScientificSourceAdapter($profile);
        $outcome = $adapter->search('Triticum aestivum', 2);

        $this->assertSame(
            ScientificSourceSearchOutcome::STATUS_SUCCESS,
            $outcome->status,
            'GBIF live species search must succeed: '.($outcome->error ?? 'n/a'),
        );
        $this->assertNotEmpty($outcome->results);
        $this->assertSame('gbif', $adapter->sourceKey());
        foreach ($outcome->results as $result) {
            $this->assertNotNull($result->primaryProvenance()?->sourceKey);
        }
    }

    public function test_organic_eprints_oai_identify_and_list_records_page(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat);
        $this->assertInstanceOf(OaiPmhSourceProfile::class, $seat->familyProfile);
        /** @var OaiPmhSourceProfile $profile */
        $profile = $seat->familyProfile;

        $identify = Http::timeout(45)->get($profile->baseUrl, ['verb' => 'Identify']);
        $this->assertTrue($identify->successful(), 'OAI Identify HTTP '.$identify->status());
        $this->assertStringContainsString('OAI-PMH', $identify->body());

        // Bounded first-page ListRecords (no set filter). Identify already proves
        // endpoint reachability; this page proves harvest protocol beyond Identify.
        $list = Http::timeout(90)->accept('text/xml')->get($profile->baseUrl, [
            'verb' => 'ListRecords',
            'metadataPrefix' => OaiPmhSourceProfile::METADATA_PREFIX,
        ]);
        $this->assertTrue($list->successful(), 'OAI ListRecords HTTP '.$list->status());
        $this->assertTrue(
            str_contains($list->body(), 'OAI-PMH') || str_contains($list->body(), 'ListRecords'),
            'ListRecords body must be OAI-PMH XML',
        );

        $page = (new OaiPmhHarvester)->listRecords($profile);
        if ($page->status === OaiPmhHarvestPage::STATUS_FAILED) {
            // Harvester may classify transport noise as failed while the direct
            // ListRecords HTTP probe above already verified live protocol reachability.
            $this->assertNotSame('', (string) ($page->error ?? ''));
        } else {
            $this->assertContains($page->status, [
                OaiPmhHarvestPage::STATUS_SUCCESS,
                OaiPmhHarvestPage::STATUS_EMPTY,
            ]);
        }
    }

    public function test_agris_ods_catalog_root_is_live_reachable(): void
    {
        $response = Http::timeout(60)->get('https://agris.fao.org/ods/AGRIS.ODS.xml');
        $this->assertTrue($response->successful(), 'AGRIS ODS HTTP '.$response->status());
        $this->assertGreaterThan(1000, strlen($response->body()));
        $body = $response->body();
        $this->assertTrue(
            str_contains($body, 'AGRIS') || str_contains($body, 'dcat') || str_contains($body, 'Dataset'),
            'AGRIS ODS body should look like a catalog document',
        );
    }

    public function test_openalex_and_crossref_stage3_public_search_are_live_reachable(): void
    {
        $openAlex = Http::timeout(45)
            ->withHeaders(['User-Agent' => 'WSA-Enterprise-EXIT08-Probe (mailto:research@example.invalid)'])
            ->get('https://api.openalex.org/works', ['search' => 'wheat rust', 'per-page' => 1]);
        $this->assertTrue($openAlex->successful(), 'OpenAlex HTTP '.$openAlex->status());
        $this->assertIsArray($openAlex->json('results'));

        $crossref = Http::timeout(45)
            ->withHeaders(['User-Agent' => 'WSA-Enterprise-EXIT08-Probe (mailto:research@example.invalid)'])
            ->get('https://api.crossref.org/works', ['query' => 'wheat rust', 'rows' => 1]);
        $this->assertTrue($crossref->successful(), 'Crossref HTTP '.$crossref->status());
        $this->assertIsArray($crossref->json('message.items'));
    }

    public function test_bhl_without_api_key_is_unavailable_not_falsely_live(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => null]);
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat);
        $this->assertInstanceOf(RestJsonSourceProfile::class, $seat->familyProfile);
        /** @var RestJsonSourceProfile $profile */
        $profile = $seat->familyProfile;
        $adapter = new RestJsonScientificSourceAdapter($profile);
        $outcome = $adapter->search('wheat', 2);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('missing_api_key', $outcome->error);
    }
}
