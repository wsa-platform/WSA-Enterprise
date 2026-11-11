<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * G6-20 BHL: credential gating + provider envelope Status (fixture/mocked only).
 * Does not use or assert any real API key value.
 */
class BhlApiEnvelopeAndCredentialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_bhl_is_runtime_active_and_present_in_governed_catalog(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat);
        $this->assertSame(SeatActivationState::ACTIVE, $seat->intendedActivation);
        $this->assertTrue($seat->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('bhl'));
        $this->assertSame('Status', $seat->familyProfile?->toArray()['response_status_path']);
    }

    public function test_missing_credential_is_unavailable_without_http(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => null]);
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $outcome = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('missing_api_key', $outcome->error);
        Http::assertNothingSent();
    }

    public function test_http_success_with_provider_unauthorized_status_is_rejected(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-a-secret']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response([
                'Status' => 'unauthorized',
                'ErrorMessage' => 'invalid or unauthorized API key',
                'Result' => 'invalid or unauthorized API key',
            ], 200),
        ]);

        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $outcome = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame('provider_status_rejected', $outcome->error);
        Http::assertSent(static function (Request $request): bool {
            // Credential must be sent as query param name only; never assert the secret value in output.
            return str_contains($request->url(), 'apikey=')
                && str_contains($request->url(), 'op=PublicationSearch')
                && str_contains($request->url(), 'searchterm=wheat');
        });
    }

    public function test_ok_envelope_maps_results_and_preserves_identity(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-a-secret']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/bhl_publication_search_fixture_v1.json')),
                200,
            ),
        ]);

        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $outcome = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat rust', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame('bhl', $outcome->results[0]->sourceKey);
        $this->assertSame('bhl-fixture-part-1', (string) $outcome->results[0]->sourceIdentifier);
        $this->assertSame(
            'https://www.biodiversitylibrary.org/part/bhl-fixture-part-1',
            (string) $outcome->results[0]->canonicalUrl,
        );
        $this->assertNotNull($outcome->results[0]->primaryProvenance());
        $this->assertTrue($seat->isRuntimeActive());
    }

    public function test_http_401_is_failed_without_treating_as_empty(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-a-secret']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response([
                'Status' => 'unauthorized',
                'ErrorMessage' => 'unauthorized',
                'Result' => 'unauthorized',
            ], 401),
        ]);

        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $outcome = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame('http_error', $outcome->error);
        $this->assertSame(401, $outcome->httpStatus);
    }
}
