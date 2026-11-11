<?php

declare(strict_types=1);

namespace Tests\Unit\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerAssessmentIssuer;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerAssessmentRegistry;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerAuthorizedAssessment;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerDecisionIdentifier;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerWriteBoundary;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CapVerWriteBoundaryTest extends TestCase
{
    private CapVerAssessmentRegistry $registry;

    private CapVerAssessmentIssuer $issuer;

    private CapVerWriteBoundary $boundary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CapVerAssessmentRegistry;
        $this->issuer = new CapVerAssessmentIssuer($this->registry);
        $this->boundary = new CapVerWriteBoundary($this->registry);
    }

    public function test_authorized_verified_write_passes(): void
    {
        $record = $this->record('G1-01', CapabilityState::VERIFIED);
        $assessment = $this->issuer->issue(
            'G1-01',
            $record->dimension,
            CapabilityState::VERIFIED,
            ['evidence://capver/g1-01'],
            new DateTimeImmutable('2026-11-11T09:00:00Z'),
        );

        $this->boundary->assertAuthorizedWrite($record, $assessment);
        $this->assertStringStartsWith('capver-decision-G1-01-', $assessment->decisionId->value);
        $this->assertSame($assessment, $this->registry->resolve($assessment->decisionId->value));
    }

    public function test_missing_assessment_is_rejected(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('Capability persistence requires a CapVer-authorized assessment.');
        $this->boundary->assertAuthorizedWrite($this->record('G1-01', CapabilityState::VERIFIED), null);
    }

    public function test_unverified_is_never_persisted(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('UNVERIFIED capability state must not be persisted.');
        $this->boundary->assertAuthorizedWrite($this->record('G1-01', CapabilityState::UNVERIFIED), null);
    }

    public function test_issuer_refuses_unverified_assessment(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('CapVer must not issue an assessment that persists UNVERIFIED.');
        $this->issuer->issue('G1-01', CapabilityDimension::accessMethod(CapabilityAccessMethod::API), CapabilityState::UNVERIFIED, []);
    }

    public function test_malformed_identifier_is_rejected(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('malformed');
        CapVerDecisionIdentifier::parse('not-a-capver-id');
    }

    public function test_fabricated_identifier_without_assessment_is_rejected(): void
    {
        $fabricated = CapVerDecisionIdentifier::mint(
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            new DateTimeImmutable('2026-11-11T09:00:00Z'),
        )->value;

        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('does not resolve');
        $this->registry->resolve($fabricated);
    }

    public function test_valid_looking_identifier_string_cannot_authorize_without_registry(): void
    {
        $record = $this->record('G1-01', CapabilityState::VERIFIED);
        $this->expectException(CapabilityInvariantViolation::class);
        $this->boundary->assertAuthorizedWriteFromIdentifier(
            $record,
            'capver-decision-G1-01-access_method~api-20261111T090000000Z',
        );
    }

    public function test_wrong_source_is_rejected(): void
    {
        $assessment = $this->issuer->issue(
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::VERIFIED,
            ['evidence://a'],
            new DateTimeImmutable('2026-11-11T09:00:00Z'),
        );
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('does not authorize');
        $this->boundary->assertAuthorizedWrite($this->record('G5-04', CapabilityState::VERIFIED), $assessment);
    }

    public function test_wrong_dimension_is_rejected(): void
    {
        $assessment = $this->issuer->issue(
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::VERIFIED,
            ['evidence://a'],
            new DateTimeImmutable('2026-11-11T09:00:00Z'),
        );
        $wrong = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-wrong-dim'),
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01')),
            CapabilityDimension::accessMethod(CapabilityAccessMethod::OAI_PMH),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
        );
        $this->expectException(CapabilityInvariantViolation::class);
        $this->boundary->assertAuthorizedWrite($wrong, $assessment);
    }

    public function test_wrong_state_is_rejected(): void
    {
        $assessment = $this->issuer->issue(
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::PARTIAL,
            ['evidence://a'],
            new DateTimeImmutable('2026-11-11T09:00:00Z'),
        );
        $this->expectException(CapabilityInvariantViolation::class);
        $this->boundary->assertAuthorizedWrite($this->record('G1-01', CapabilityState::VERIFIED), $assessment);
    }

    public function test_empty_decision_id_is_rejected(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->boundary->assertAuthorizedWriteFromIdentifier(
            $this->record('G1-01', CapabilityState::VERIFIED),
            '',
        );
    }

    #[DataProvider('permittedNonVerifiedStates')]
    public function test_non_verified_authorized_states_require_matching_assessment(CapabilityState $state): void
    {
        $record = $this->record('G1-02', $state);
        $assessment = $this->issuer->issue(
            'G1-02',
            $record->dimension,
            $state,
            $state === CapabilityState::NOT_APPLICABLE ? [] : ['evidence://partial'],
            new DateTimeImmutable('2026-11-11T10:00:00Z'),
        );
        $this->boundary->assertAuthorizedWrite($record, $assessment);
        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{0: CapabilityState}>
     */
    public static function permittedNonVerifiedStates(): array
    {
        return [
            'partial' => [CapabilityState::PARTIAL],
            'unavailable' => [CapabilityState::UNAVAILABLE],
            'not_applicable' => [CapabilityState::NOT_APPLICABLE],
        ];
    }

    public function test_stale_evidence_alone_does_not_require_automatic_downgrade_hook(): void
    {
        // Boundary authorizes VERIFIED with CURRENT freshness on the record; freshness demotion
        // is not automatic (OD-A-ID item 11). Absence of a downgrade producer is intentional.
        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-stale-ok'),
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01')),
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::STALE,
        );
        $assessment = $this->issuer->issue(
            'G1-01',
            $record->dimension,
            CapabilityState::VERIFIED,
            ['evidence://stale-but-authorized'],
            new DateTimeImmutable('2026-11-11T11:00:00Z'),
        );
        $this->boundary->assertAuthorizedWrite($record, $assessment);
        $this->assertSame(CapabilityEvidenceFreshness::STALE, $record->evidenceFreshness);
    }

    public function test_forged_assessment_object_still_requires_registry_resolution(): void
    {
        $id = CapVerDecisionIdentifier::mint(
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            new DateTimeImmutable('2026-11-11T12:00:00Z', new DateTimeZone('UTC')),
        );
        $forged = new CapVerAuthorizedAssessment(
            $id,
            'G1-01',
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::VERIFIED,
            ['evidence://forged'],
            $id->issuedAtUtc,
        );

        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('does not resolve');
        $this->boundary->assertAuthorizedWrite($this->record('G1-01', CapabilityState::VERIFIED), $forged);
    }

    private function record(string $adrId, CapabilityState $state): CapabilityRecord
    {
        return CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-'.$adrId.'-'.$state->value),
            CapabilitySubject::seatScoped(AdrMembershipId::fromString($adrId)),
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            $state,
            CapabilityEvidenceFreshness::CURRENT,
            limitationText: $state === CapabilityState::PARTIAL ? 'partial limitation for test' : null,
        );
    }
}
