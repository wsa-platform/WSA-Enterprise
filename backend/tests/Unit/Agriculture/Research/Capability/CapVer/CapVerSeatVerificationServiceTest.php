<?php

namespace Tests\Unit\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapVer\CapVerSeatVerificationService;
use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Capability\Persistence\CapabilityStoreRepository;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * CapVer AUTHORIZE ≠ CapVer pass; store status reported honestly.
 */
class CapVerSeatVerificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        AcceptedOwnerDecisionCatalog::swap(null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        AcceptedOwnerDecisionCatalog::swap(null);
        parent::tearDown();
    }

    public function test_capver_persists_verified_access_method_when_store_available(): void
    {
        $persisted = [];
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->allows('findCurrentFact')->andReturn(null);
        $store->shouldReceive('persistCurrent')
            ->times(5)
            ->andReturnUsing(function ($record, $refs, $assessment) use (&$persisted) {
                $this->assertSame(CapabilityState::VERIFIED, $record->state);
                $this->assertNotEmpty($refs);
                $this->assertNotNull($assessment);
                $persisted[] = $record->recordId->value;

                return \App\Services\Agriculture\Research\Capability\Persistence\CapabilityCellFact::forWrite(
                    $record,
                    $refs,
                );
            });

        $service = new CapVerSeatVerificationService($store);
        $outcomes = $service->executeAcceptedSeats();
        $this->assertCount(5, $outcomes);
        $this->assertCount(5, $persisted);
        $this->assertContains('G5-04', array_map(static fn ($outcome) => $outcome->adrId, $outcomes));
        $this->assertNotContains('G6-06', array_map(static fn ($outcome) => $outcome->adrId, $outcomes));
        foreach ($outcomes as $outcome) {
            $this->assertTrue($outcome->prerequisitesSatisfied, $outcome->adrId);
            $this->assertTrue($outcome->contractVerifiedAuthored, $outcome->adrId);
            $this->assertSame('PERSISTED', $outcome->capabilityStoreStatus, $outcome->adrId.' '.$outcome->capabilityStoreDetail);
            $this->assertTrue($outcome->capVerPassedForActivation, $outcome->adrId);
        }
    }

    public function test_capver_reports_store_unavailable_without_claiming_store_success(): void
    {
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->shouldReceive('findCurrentFact')->once()->andThrow(new RuntimeException('connection refused'));
        $store->shouldReceive('persistCurrent')->never();

        $service = new CapVerSeatVerificationService($store);
        $outcome = $service->execute('G1-02');
        $this->assertTrue($outcome->prerequisitesSatisfied);
        $this->assertTrue($outcome->contractVerifiedAuthored);
        $this->assertSame('STORE_UNAVAILABLE', $outcome->capabilityStoreStatus);
        $this->assertFalse($outcome->capVerPassedForActivation, 'STORE_UNAVAILABLE must never count as CapVer activation-pass');
        $this->assertFalse($outcome->toArray()['capability_persisted']);
        $this->assertStringContainsString('connection refused', (string) $outcome->capabilityStoreDetail);
    }

    public function test_capver_blocked_without_owner_authorization(): void
    {
        AcceptedOwnerDecisionCatalog::swap([], []);
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->shouldReceive('persistCurrent')->never();

        $service = new CapVerSeatVerificationService($store);
        $outcome = $service->execute('G1-02');
        $this->assertFalse($outcome->prerequisitesSatisfied);
        $this->assertFalse($outcome->capVerPassedForActivation);
        $this->assertContains('owner_onboarding_accept_not_recorded', $outcome->prerequisiteFailures);
        $this->assertContains('owner_capver_not_authorized', $outcome->prerequisiteFailures);
    }

    public function test_already_current_verified_is_idempotent(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G6-20'));
        $dimension = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $fact = \App\Services\Agriculture\Research\Capability\Persistence\CapabilityCellFact::forWrite(
            \App\Services\Agriculture\Research\Capability\CapabilityRecord::create(
                \App\Services\Agriculture\Research\Capability\CapabilityRecordId::fromString('cap-rec-existing'),
                $subject,
                $dimension,
                CapabilityState::VERIFIED,
                \App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness::CURRENT,
            ),
            ['ev-existing'],
        );
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->shouldReceive('findCurrentFact')->once()->andReturn($fact);
        $store->shouldReceive('persistCurrent')->never();

        $service = new CapVerSeatVerificationService($store);
        $outcome = $service->execute('G6-20');
        $this->assertSame('ALREADY_CURRENT', $outcome->capabilityStoreStatus);
        $this->assertTrue($outcome->capVerPassedForActivation);
    }

    public function test_g6_06_stays_outside_the_scoped_gbif_capver_authorization(): void
    {
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->shouldReceive('persistCurrent')->never();
        $store->shouldReceive('findCurrentFact')->never();

        $service = new CapVerSeatVerificationService($store);
        $outcome = $service->execute('G6-06');
        $this->assertFalse($outcome->capVerPassedForActivation);
        $this->assertContains('owner_capver_not_authorized', $outcome->prerequisiteFailures);
    }
}
