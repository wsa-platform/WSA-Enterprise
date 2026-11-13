<?php

namespace Tests\Unit\Agriculture\Research\Membership;

use Tests\TestCase;

/**
 * EXIT-10 automated integrity checks for Current 110 membership vs matrix.
 */
final class Exit10CurrentMembershipMatrixValidatorTest extends TestCase
{
    public function test_register_and_matrix_have_exactly_110_matching_identities(): void
    {
        $candidates = [
            base_path('docs/architecture/science-source-expansion'),
            dirname(base_path()).'/docs/architecture/science-source-expansion',
            '/var/www/html/docs/architecture/science-source-expansion',
        ];
        $registerPath = null;
        $matrixPath = null;
        foreach ($candidates as $dir) {
            $r = $dir.'/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json';
            $m = $dir.'/2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json';
            if (is_file($r) && is_file($m)) {
                $registerPath = $r;
                $matrixPath = $m;
                break;
            }
        }
        if ($registerPath === null || $matrixPath === null) {
            $this->markTestSkipped('EXIT-10 register/matrix artifacts not mounted in this environment.');
        }

        $register = json_decode((string) file_get_contents($registerPath), true);
        $matrix = json_decode((string) file_get_contents($matrixPath), true);
        $this->assertIsArray($register);
        $this->assertIsArray($matrix);

        $regRows = $this->rows($register);
        $matRows = $this->rows($matrix);
        $this->assertCount(110, $regRows, 'Register must contain exactly 110 rows');
        $this->assertCount(110, $matRows, 'Current matrix must contain exactly 110 rows');

        $regIds = array_map(fn (array $r): string => $this->identity($r), $regRows);
        $matIds = array_map(fn (array $r): string => $this->identity($r), $matRows);
        $this->assertSame($regIds, $matIds, 'Register and matrix identities must match in order');
        $this->assertCount(110, array_unique($regIds), 'No duplicate identities in register');
    }

    /** @param  array<string, mixed>  $doc
     *  @return list<array<string, mixed>> */
    private function rows(array $doc): array
    {
        foreach (['rows', 'seats', 'entries', 'membership', 'sources'] as $key) {
            if (isset($doc[$key]) && is_array($doc[$key])) {
                return array_values(array_filter($doc[$key], 'is_array'));
            }
        }
        if (isset($doc['current']) && is_array($doc['current'])) {
            return array_values(array_filter($doc['current'], 'is_array'));
        }

        return [];
    }

    /** @param  array<string, mixed>  $row */
    private function identity(array $row): string
    {
        foreach (['seat_id', 'adr_id', 'membership_id', 'id', 'canonical_identity_id', 'source_key'] as $key) {
            if (isset($row[$key]) && is_string($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        if (isset($row['source_key'], $row['adr_id'])) {
            return $row['source_key'].'/'.$row['adr_id'];
        }

        return json_encode($row, JSON_UNESCAPED_SLASHES) ?: 'unknown';
    }
}
