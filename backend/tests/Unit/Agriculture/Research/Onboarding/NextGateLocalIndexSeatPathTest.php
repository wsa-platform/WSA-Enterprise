<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Tests\TestCase;

/**
 * Proves G1-01 / G1-17 LocalIndex ingest→search path with deterministic fixtures.
 * Bounded/limited ACTIVATION is authorized; fixture path remains independent of durable store.
 */
class NextGateLocalIndexSeatPathTest extends TestCase
{
    public function test_agris_profile_permits_retrieval_and_searches_seeded_index(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertNotNull($seat?->familyProfile);
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
        $this->assertTrue($seat->isRuntimeActive());

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $report = $ingestion->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_agris_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-17T18:00:00+00:00'),
        );
        $this->assertSame('completed', $report->status);
        $this->assertGreaterThan(0, $store->generation('agris'));

        $adapter = new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store);
        $hit = $adapter->search('wheat irrigation', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $hit->status);
        $this->assertNotEmpty($hit->results);
        $this->assertSame('agris', $hit->results[0]->sourceKey);
        $this->assertSame('Deficit irrigation effects on wheat yield in arid systems', $hit->results[0]->title);
        $this->assertContains(
            (string) $hit->results[0]->sourceIdentifier,
            ['agris-fixture-1', '10.0000/agris.fixture.1'],
            'Identifier may be provider id or DOI depending on family record normalization.',
        );

        $empty = $adapter->search('nonexistent_token_xyzzy', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $empty->status);

        $unseeded = new LocalIndexScientificSourceAdapter(
            $seat->familyProfile,
            new LocalScientificIndex(),
            new InMemoryScientificRecordStore(),
        );
        $unavailable = $unseeded->search('wheat', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $unavailable->status);
        $this->assertSame('local_index_not_built', $unavailable->error);
    }

    public function test_dr_duke_profile_permits_retrieval_and_preserves_provenance(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-17');
        $this->assertNotNull($seat?->familyProfile);
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
        $this->assertTrue($seat->isRuntimeActive());

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $ingestion->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_dr_duke_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-17T18:00:00+00:00'),
        );

        $adapter = new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store);
        $outcome = $adapter->search('Mentha piperita', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame('dr_duke', $outcome->results[0]->sourceKey);
        $this->assertSame('drduke-fixture-1', (string) $outcome->results[0]->sourceIdentifier);
        $this->assertNotNull($outcome->results[0]->primaryProvenance());
    }

    public function test_hortdb_and_osmia_licence_wiring_does_not_activate(): void
    {
        $hort = NextGateEligibleSeatCatalog::get('G1-13');
        $osmia = NextGateEligibleSeatCatalog::get('G3-05');
        $this->assertNotNull($hort);
        $this->assertNotNull($osmia);
        $this->assertFalse($hort->onboardingContract?->licensing->isFullyUnresolved());
        $this->assertFalse($osmia->onboardingContract?->licensing->isFullyUnresolved());
        $this->assertSame('CC BY 4.0', $osmia->onboardingContract?->licensing->licenceIdentifier);
        $this->assertFalse($hort->isRuntimeActive());
        $this->assertFalse($osmia->isRuntimeActive());
        // CONDITIONAL retrieval must not be treated as permits().
        $this->assertFalse($hort->familyProfile?->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
    }
}
