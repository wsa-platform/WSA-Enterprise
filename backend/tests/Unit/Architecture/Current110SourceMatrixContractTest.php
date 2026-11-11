<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Tracks OD-10 / P1-EXIT-10 membership integrity for 110.v1.
 * Kept under Architecture so it does not depend on untracked Membership WIP packages.
 */
final class Current110SourceMatrixContractTest extends TestCase
{
    public function test_current_matrix_matches_110_v1_register(): void
    {
        $root = dirname(__DIR__, 4);
        $registerPath = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json';
        $matrixPath = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json';
        $historicalPath = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'ADR-023-109-MEMBERSHIP-REGISTER.v1.json';

        $this->assertFileExists($registerPath);
        $this->assertFileExists($matrixPath);
        $this->assertFileExists($historicalPath);

        $register = json_decode((string) file_get_contents($registerPath), true, 512, JSON_THROW_ON_ERROR);
        $matrix = json_decode((string) file_get_contents($matrixPath), true, 512, JSON_THROW_ON_ERROR);
        $historical = json_decode((string) file_get_contents($historicalPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('110.v1', $register['membership_revision']);
        $this->assertSame(110, $register['total_current_seats']);
        $this->assertSame(110, $matrix['total_rows']);
        $this->assertSame('110.v1', $matrix['membership_revision']);
        $this->assertCount(110, $matrix['rows']);
        $this->assertSame(109, $historical['total_current_seats']);

        $registerIds = array_column($register['seats'], 'adr_id');
        $matrixIds = array_column($matrix['rows'], 'adr_id');
        $this->assertSame($registerIds, $matrixIds);
        $this->assertContains('G2-26', $matrixIds);
        $this->assertNotContains('G2-26', array_column($historical['seats'], 'adr_id'));
        $this->assertSame(count($registerIds), count(array_unique($registerIds)));

        $g226 = null;
        foreach ($matrix['rows'] as $row) {
            $this->assertSame('CURRENT', $row['membership']);
            $this->assertSame('INACTIVE', $row['activation']);
            $this->assertSame('NOT_ELIGIBLE', $row['eligibility']);
            $this->assertSame('NOT_RUN', $row['endpoint_verification']);
            if ($row['adr_id'] === 'G2-26') {
                $g226 = $row;
            }
        }
        $this->assertNotNull($g226);
        $this->assertSame('P1-BLK-2026-11-11-G2-26-01', $g226['seat_specific_blocker_id']);
        $this->assertSame(hash_file('sha256', $registerPath), $matrix['membership_register_sha256']);
    }
}
