<?php

declare(strict_types=1);

namespace Tests\Unit\Agriculture\Research\IntegrationClassification;

use App\Services\Agriculture\Research\IntegrationClassification\OperationalEligibilityGate;
use PHPUnit\Framework\TestCase;

final class OperationalEligibilityGateTest extends TestCase
{
    private OperationalEligibilityGate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new OperationalEligibilityGate();
    }

    public function test_every_verified_dimension_is_required_for_eligibility(): void
    {
        $result = $this->gate->evaluate(
            'G5-04',
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
        );

        $this->assertTrue($result->operationallyEligible);
        $this->assertSame([], $result->blockers);
    }

    public function test_classification_does_not_count_as_an_endpoint_test(): void
    {
        $result = $this->gate->evaluate(
            'G1-02',
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::NOT_RUN,
            OperationalEligibilityGate::VERIFIED,
        );

        $this->assertFalse($result->operationallyEligible);
        $this->assertSame(OperationalEligibilityGate::NOT_RUN, $result->endpointTest);
        $this->assertContains('endpoint_test_not_verified', $result->blockers);
    }

    public function test_a_classification_record_cannot_verify_an_endpoint(): void
    {
        $result = $this->gate->evaluate(
            'G6-20',
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::ENDPOINT_KIND_CLASSIFICATION_RECORD,
        );

        $this->assertFalse($result->operationallyEligible);
        $this->assertSame(OperationalEligibilityGate::BLOCKED, $result->endpointTest);
        $this->assertContains('endpoint_test_cannot_be_a_classification_record', $result->blockers);
    }

    public function test_documentary_status_is_not_operational_eligibility(): void
    {
        $result = $this->gate->evaluate(
            'G1-01',
            OperationalEligibilityGate::DOCUMENTED,
            OperationalEligibilityGate::DOCUMENTED,
            OperationalEligibilityGate::DOCUMENTED,
            OperationalEligibilityGate::DOCUMENTED,
        );

        $this->assertFalse($result->operationallyEligible);
        $this->assertContains('endpoint_test_not_verified', $result->blockers);
        $this->assertContains('integration_not_verified', $result->blockers);
    }

    public function test_documentary_inventory_blocks_the_five_designated_seats(): void
    {
        foreach (['G1-02', 'G6-20', 'G1-01', 'G1-17', 'G5-04'] as $seatId) {
            $result = $this->gate->fromDocumentaryInventory($seatId);

            $this->assertSame(OperationalEligibilityGate::DOCUMENTED, $result->classification);
            $this->assertSame(OperationalEligibilityGate::NOT_RUN, $result->endpointTest);
            $this->assertSame(OperationalEligibilityGate::NOT_RUN, $result->integration);
            $this->assertFalse($result->operationallyEligible);
        }
    }

    public function test_a_facet_without_a_resolved_capability_cell_blocks_eligibility(): void
    {
        $blocked = $this->gate->evaluate(
            'G5-04',
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            null,
            ['species'],
            [],
        );
        $accepted = $this->gate->evaluate(
            'G5-04',
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            OperationalEligibilityGate::VERIFIED,
            null,
            ['species'],
            ['species'],
        );

        $this->assertFalse($blocked->operationallyEligible);
        $this->assertContains('unsupported_facet', $blocked->blockers);
        $this->assertTrue($accepted->operationallyEligible);
    }

    public function test_missing_evidence_stays_not_run_rather_than_a_verified_negative(): void
    {
        $result = $this->gate->fromDocumentaryInventory('G1-17');

        $this->assertSame(OperationalEligibilityGate::NOT_RUN, $result->identity);
        $this->assertSame(OperationalEligibilityGate::NOT_RUN, $result->endpointTest);
        $this->assertNotSame(OperationalEligibilityGate::BLOCKED, $result->endpointTest);
    }

    public function test_unsupported_status_and_seat_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->gate->evaluate('G1-02', 'APPROVED', OperationalEligibilityGate::NOT_RUN, OperationalEligibilityGate::NOT_RUN, OperationalEligibilityGate::NOT_RUN);
    }

    public function test_the_gate_source_has_no_http_client(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 5).'/app/Services/Agriculture/Research/IntegrationClassification/OperationalEligibilityGate.php');

        $this->assertStringNotContainsString('Http::', $source);
        $this->assertStringNotContainsString('curl_', $source);
        $this->assertStringNotContainsString('file_get_contents(\'http', $source);
    }
}
