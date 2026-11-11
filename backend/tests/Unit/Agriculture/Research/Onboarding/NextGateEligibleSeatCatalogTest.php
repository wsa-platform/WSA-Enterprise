<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOnboardingDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * R9: the seventeen R7 next-gate seats, selective activation, and GBIF REST wiring.
 */
class NextGateEligibleSeatCatalogTest extends TestCase
{
    public function test_authoritative_seventeen_seats_match_r7_section_8_41_2(): void
    {
        $this->assertSame([
            'G1-01', 'G1-13', 'G1-15', 'G1-17', 'G1-19',
            'G2-15', 'G2-23', 'G3-05', 'G4-01', 'G4-02', 'G5-04',
            'G6-03', 'G6-04', 'G6-06', 'G6-08', 'G6-12', 'G6-16',
        ], NextGateEligibleSeatCatalog::adrIds());
        $this->assertCount(17, NextGateEligibleSeatCatalog::all());
    }

    public function test_only_gbif_is_runtime_active_and_dual_peers_are_deferred(): void
    {
        $active = [];
        foreach (NextGateEligibleSeatCatalog::all() as $adrId => $seat) {
            if ($seat->isRuntimeActive()) {
                $active[] = $adrId;
            }
        }
        sort($active);
        $this->assertSame(['G1-01', 'G1-17', 'G5-04'], $active);
        // Next-gate ACTIVE: GBIF + bounded AGRIS + limited Duke; CURRENT-membership OE/BHL merge.
        $keys = AcceptedOnboardingDecisionCatalog::activeSourceKeys();
        sort($keys);
        $this->assertSame(['agris', 'bhl', 'dr_duke', 'gbif', 'organic_eprints'], $keys);

        foreach (['G6-03', 'G6-04', 'G6-06', 'G6-08'] as $dual) {
            $seat = NextGateEligibleSeatCatalog::get($dual);
            $this->assertNotNull($seat);
            $this->assertSame(SeatActivationState::DEFERRED, $seat->intendedActivation);
            $this->assertTrue($seat->isDualPeerDeferred());
            $this->assertFalse($seat->isRuntimeActive());
        }
    }

    public function test_production_catalog_registers_activated_next_gate_seats(): void
    {
        $catalog = new ScientificSourceCatalog();
        $this->assertTrue($catalog->has('gbif'));
        $this->assertTrue($catalog->has('agris'));
        $this->assertTrue($catalog->has('dr_duke'));
        $registration = $catalog->get('gbif');
        $this->assertSame(ScientificSourceLifecycle::GOVERNED_ONBOARDED, $registration?->lifecycle);
        $this->assertSame(ScientificSourceSelectionRole::GOVERNED_EXPANSION, $registration?->selectionRole);
        $this->assertSame('G5-04', $registration?->adrId?->value);
        $this->assertSame(ScientificSourceAccessFamily::REST_API, $registration?->accessFamily);
        $this->assertSame('G1-01', $catalog->get('agris')?->adrId?->value);
        $this->assertSame('G1-17', $catalog->get('dr_duke')?->adrId?->value);
        $this->assertSame(ScientificSourceAccessFamily::BULK_DATASET, $catalog->get('agris')?->accessFamily);
        $this->assertSame(ScientificSourceAccessFamily::STATIC_DATASET, $catalog->get('dr_duke')?->accessFamily);
    }

    public function test_gbif_rest_adapter_maps_species_search_results(): void
    {
        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::response([
                'results' => [
                    [
                        'key' => 2704179,
                        'scientificName' => 'Triticum aestivum L.',
                        'rank' => 'SPECIES',
                        'publishedIn' => 'Sp. Pl.',
                    ],
                    [
                        'key' => 2704178,
                        'scientificName' => 'Triticum',
                        'rank' => 'GENUS',
                    ],
                ],
            ], 200),
        ]);

        $seat = NextGateEligibleSeatCatalog::get('G5-04');
        $this->assertNotNull($seat?->familyProfile);
        $adapter = new RestJsonScientificSourceAdapter($seat->familyProfile);
        $outcome = $adapter->search('Triticum aestivum', 10);

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertCount(2, $outcome->results);
        $this->assertSame('gbif', $outcome->results[0]->sourceKey);
        $this->assertSame('2704179', (string) $outcome->results[0]->sourceIdentifier);
        $this->assertSame('Triticum aestivum L.', $outcome->results[0]->title);
        Http::assertSent(static function (Request $request): bool {
            if (! str_starts_with($request->url(), 'https://api.gbif.org/v1/species/search')) {
                return false;
            }
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['q'] ?? null) === 'Triticum aestivum';
        });
    }

    public function test_agris_bounded_scope_is_runtime_active_with_cc_by_licence(): void
    {
        $agris = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertNotNull($agris);
        $this->assertSame(SeatActivationState::ACTIVE, $agris->intendedActivation);
        $this->assertSame([], $agris->blockers);
        $this->assertTrue($agris->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('agris'));
        $this->assertNotNull($agris->familyProfile);
        $this->assertFalse($agris->onboardingContract?->licensing->isFullyUnresolved());
        $this->assertStringContainsString('CC-BY-4.0', (string) $agris->onboardingContract?->licensing->licenceIdentifier);
        $this->assertSame('agris', $agris->onboardingContract?->stage3Relationship);
        $this->assertTrue($agris->familyProfile->licensing()->permits(
            \App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage::RETRIEVAL
        ));
        $this->assertStringContainsString('BOUNDED', $agris->accessNotes);
    }

    public function test_dr_duke_limited_projection_is_runtime_active_with_cc0(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-17');
        $this->assertNotNull($seat);
        $this->assertSame(SeatActivationState::ACTIVE, $seat->intendedActivation);
        $this->assertTrue($seat->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('dr_duke'));
        $this->assertFalse($seat->onboardingContract?->licensing->isFullyUnresolved());
        $this->assertSame('CC0 1.0', $seat->onboardingContract?->licensing->licenceIdentifier);
        $this->assertSame('dr_duke', $seat->onboardingContract?->stage3Relationship);
        $this->assertTrue($seat->familyProfile?->licensing()->permits(
            \App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage::RETRIEVAL
        ));
        $this->assertStringContainsString('LIMITED', $seat->accessNotes);
        $this->assertStringContainsString('FARMACY', $seat->accessNotes);
    }

    public function test_gbif_empty_and_malformed_responses_are_isolated(): void
    {
        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::sequence()
                ->push(['results' => []], 200)
                ->push(['not' => 'results'], 200)
                ->push('server error', 503),
        ]);

        $seat = NextGateEligibleSeatCatalog::get('G5-04');
        $this->assertNotNull($seat?->familyProfile);
        $adapter = new RestJsonScientificSourceAdapter($seat->familyProfile);

        $empty = $adapter->search('zzzznonexistenttaxon', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $empty->status);
        $this->assertCount(0, $empty->results);

        $malformed = $adapter->search('Triticum', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $malformed->status);
        $this->assertSame('malformed_or_unusable_results', $malformed->error);

        $httpError = $adapter->search('Triticum', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $httpError->status);
        $this->assertSame('http_error', $httpError->error);
        $this->assertSame(503, $httpError->httpStatus);
    }

    public function test_readiness_reconciler_keeps_activation_and_licence_distinct(): void
    {
        $summary = (new \App\Services\Agriculture\Research\Onboarding\NextGateSeatReadinessReconciler)->summary();
        $this->assertSame(17, $summary['seat_count']);
        $this->assertSame(3, $summary['runtime_active_count']);
        $this->assertTrue($summary['policy']['automatic_activation_when_all_gates_pass']);
        $byId = [];
        foreach ($summary['seats'] as $row) {
            $byId[$row['adr_id']] = $row;
        }
        $this->assertTrue($byId['G5-04']['runtime_active']);
        $this->assertTrue($byId['G1-01']['licence_declared']);
        $this->assertTrue($byId['G1-01']['runtime_active']);
        $this->assertTrue($byId['G1-17']['runtime_active']);
        $this->assertTrue($byId['G1-13']['licence_declared'] || ! $byId['G1-13']['runtime_active']);
        $this->assertFalse($byId['G1-13']['runtime_active']);
        $this->assertFalse($byId['G1-15']['runtime_active']);
    }
}
