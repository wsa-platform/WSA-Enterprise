<?php

namespace Tests\Unit\Agriculture\Research\Membership;

use PHPUnit\Framework\TestCase;

/**
 * IU-10A — Fail-closed validation of ADR-023 109 CURRENT membership register (JSON only).
 *
 * Does not load runtime registries, Cap, Stage-3, or identity minting.
 */
final class Adr023109MembershipRegisterContractTest extends TestCase
{
    private const RELATIVE_PATH = 'docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json';

    /** @var list<string> */
    private const ALLOWED_ROOT_KEYS = [
        'schema_id',
        'governance_reference',
        'membership_revision',
        'adr_baseline_commit',
        'total_current_seats',
        'group_counts',
        'seats',
        'dual_pairs',
        'provenance',
    ];

    /** @var list<string> */
    private const ALLOWED_SEAT_KEYS = [
        'adr_id',
        'group_id',
        'ordinal',
        'display_name',
        'membership_status',
        'dual_peer_adr_id',
    ];

    /** @var list<string> */
    private const FORBIDDEN_FIELD_NAMES = [
        'canonical_identity_id',
        'sourceKey',
        'source_key',
        'adapter',
        'provider',
        'capability',
        'path',
        'projection',
        'retrieval_method',
        'api',
        'oai_pmh',
        'rss',
        'atom',
        'bulk',
        'license',
        'machine_access',
        'historical_62_xref',
        'same_as',
        'SAME_AS',
    ];

    /** @var array<string, int> */
    private const EXPECTED_GROUP_COUNTS = [
        'G1' => 22,
        'G2' => 25,
        'G3' => 14,
        'G4' => 7,
        'G5' => 19,
        'G6' => 22,
    ];

    public function test_membership_register_is_fail_closed_valid(): void
    {
        $absolute = $this->absolutePath();
        $this->assertFileExists($absolute);

        $raw = file_get_contents($absolute);
        $this->assertNotFalse($raw);
        $this->assertStringEndsWith("\n", $raw, 'Deterministic trailing newline required.');

        $decoded = json_decode($raw, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON must parse: '.json_last_error_msg());
        $this->assertIsArray($decoded);

        $this->assertSame(self::ALLOWED_ROOT_KEYS, array_keys($decoded), 'Unexpected or reordered root keys.');

        $this->assertSame('adr023.membership_register.v1', $decoded['schema_id']);
        $this->assertSame('ADR-023/D-09', $decoded['governance_reference']);
        $this->assertSame('109.v1', $decoded['membership_revision']);
        $this->assertSame('9de9ed874510dad1970abe14b2aadb4441e9137a', $decoded['adr_baseline_commit']);
        $this->assertSame(109, $decoded['total_current_seats']);
        $this->assertSame(self::EXPECTED_GROUP_COUNTS, $decoded['group_counts']);

        $seats = $decoded['seats'];
        $this->assertIsArray($seats);
        $this->assertCount(109, $seats);

        $seen = [];
        $groupTallies = ['G1' => 0, 'G2' => 0, 'G3' => 0, 'G4' => 0, 'G5' => 0, 'G6' => 0];
        $previousGroupIndex = 0;
        $previousOrdinalInGroup = 0;
        $groupOrder = ['G1' => 1, 'G2' => 2, 'G3' => 3, 'G4' => 4, 'G5' => 5, 'G6' => 6];

        foreach ($seats as $index => $seat) {
            $this->assertIsArray($seat);
            foreach (array_keys($seat) as $key) {
                $this->assertContains($key, self::ALLOWED_SEAT_KEYS, "Unexpected seat field [{$key}] at index {$index}");
                $this->assertNotContains($key, self::FORBIDDEN_FIELD_NAMES);
            }

            foreach (self::FORBIDDEN_FIELD_NAMES as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $seat);
            }

            $this->assertArrayHasKey('adr_id', $seat);
            $this->assertArrayHasKey('group_id', $seat);
            $this->assertArrayHasKey('ordinal', $seat);
            $this->assertArrayHasKey('display_name', $seat);
            $this->assertArrayHasKey('membership_status', $seat);

            $adrId = $seat['adr_id'];
            $groupId = $seat['group_id'];
            $ordinal = $seat['ordinal'];

            $this->assertIsString($adrId);
            $this->assertMatchesRegularExpression('/^G[1-6]-\d{2}$/', $adrId);
            $this->assertNotSame('G3-15', $adrId);
            $this->assertArrayNotHasKey($adrId, $seen, "Duplicate adr_id {$adrId}");
            $seen[$adrId] = true;

            $this->assertSame(substr($adrId, 0, 2), $groupId);
            $this->assertSame((int) substr($adrId, 3), $ordinal);
            $this->assertSame('CURRENT', $seat['membership_status']);
            $this->assertIsString($seat['display_name']);
            $this->assertNotSame('', trim($seat['display_name']));

            $this->assertArrayHasKey($groupId, $groupTallies);
            $groupTallies[$groupId]++;

            $groupIndex = $groupOrder[$groupId];
            if ($groupIndex === $previousGroupIndex) {
                $this->assertSame($previousOrdinalInGroup + 1, $ordinal, "Unstable ordinal order in {$groupId}");
            } else {
                $this->assertSame($previousGroupIndex + 1, $groupIndex, 'Unstable group order');
                $this->assertSame(1, $ordinal, "Group {$groupId} must start at ordinal 1");
            }
            $previousGroupIndex = $groupIndex;
            $previousOrdinalInGroup = $ordinal;

            if (isset($seat['dual_peer_adr_id'])) {
                $this->assertIsString($seat['dual_peer_adr_id']);
                $this->assertMatchesRegularExpression('/^G[1-6]-\d{2}$/', $seat['dual_peer_adr_id']);
            }
        }

        $this->assertSame(self::EXPECTED_GROUP_COUNTS, $groupTallies);
        $this->assertArrayNotHasKey('G3-15', $seen);

        $g217 = $this->seatById($seats, 'G2-17');
        $g219 = $this->seatById($seats, 'G2-19');
        $this->assertSame('REMVT', $g217['display_name']);
        $this->assertSame('Animal Bioscience', $g219['display_name']);

        $dualPairs = $decoded['dual_pairs'];
        $this->assertIsArray($dualPairs);
        $this->assertCount(10, $dualPairs);

        $expectedDuals = [
            ['G1-12', 'G6-01', 'FlowerBase'],
            ['G1-13', 'G6-03', 'HortDB V1.0'],
            ['G1-14', 'G6-02', 'Tropicals.cn'],
            ['G1-15', 'G6-04', 'sCentInDB'],
            ['G1-16', 'G6-05', 'AromaDb'],
            ['G1-17', 'G6-06', "Dr. Duke's"],
            ['G1-18', 'G6-07', 'MPNS'],
            ['G1-19', 'G6-08', 'FNCD'],
            ['G1-20', 'G6-09', 'FEAtl'],
            ['G1-21', 'G6-10', 'CRFG'],
        ];

        foreach ($dualPairs as $i => $pair) {
            $this->assertSame(['seat_a', 'seat_b', 'label'], array_keys($pair));
            $this->assertSame($expectedDuals[$i][0], $pair['seat_a']);
            $this->assertSame($expectedDuals[$i][1], $pair['seat_b']);
            $this->assertSame($expectedDuals[$i][2], $pair['label']);
            $this->assertArrayHasKey($pair['seat_a'], $seen);
            $this->assertArrayHasKey($pair['seat_b'], $seen);
            $this->assertArrayNotHasKey('same_as', $pair);
            $this->assertArrayNotHasKey('SAME_AS', $pair);
            $this->assertArrayNotHasKey('canonical_identity_id', $pair);

            $a = $this->seatById($seats, $pair['seat_a']);
            $b = $this->seatById($seats, $pair['seat_b']);
            $this->assertSame($pair['seat_b'], $a['dual_peer_adr_id'] ?? null);
            $this->assertSame($pair['seat_a'], $b['dual_peer_adr_id'] ?? null);
        }

        $provenance = $decoded['provenance'];
        $this->assertSame(
            ['adr_path', 'decision_id', 'decision_date', 'enumeration_source', 'baseline_git'],
            array_keys($provenance)
        );
        $this->assertSame('ADR-023', $provenance['adr_path']);
        $this->assertSame('D-09', $provenance['decision_id']);
        $this->assertSame('2026-10-01', $provenance['decision_date']);
        $this->assertSame('preserved ADR-023 enumeration / IU-10A design closure', $provenance['enumeration_source']);
        $this->assertSame('9de9ed874510dad1970abe14b2aadb4441e9137a', $provenance['baseline_git']);

        // Determinism: 2-space pretty-print, UTF-8, trailing newline, no timestamps/env paths.
        $this->assertSame(
            $this->deterministicEncode($decoded),
            $raw,
            'JSON serialization must be deterministic.'
        );
        $this->assertDoesNotMatchRegularExpression('/"generated_at"|T\\d{2}:\\d{2}:\\d{2}|[A-Za-z]:\\\\\\\\|\\/Users\\/|\\/home\\//', $raw);

        $this->assertStringNotContainsString('historical_62_xref', $raw);
        $this->assertStringNotContainsString('"G3-15"', $raw);
        $this->assertStringNotContainsString('canonical_identity_id', $raw);
        $this->assertStringNotContainsString('sourceKey', $raw);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function deterministicEncode(array $decoded): string
    {
        $json = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $this->assertNotFalse($json);

        // Match artifact convention: 2-space indent (PHP PRETTY_PRINT uses 4).
        $json = preg_replace_callback('/^(?:    )+/m', static function (array $m): string {
            $spaces = strlen($m[0]);

            return str_repeat(' ', intdiv($spaces, 2));
        }, $json);

        return $json."\n";
    }

    private function absolutePath(): string
    {
        // Host monorepo: repoRoot/docs/...
        // Docker compose: docs mounted at backendRoot/docs/...
        $candidates = [
            dirname(__DIR__, 6).DIRECTORY_SEPARATOR.self::RELATIVE_PATH,
            dirname(__DIR__, 5).DIRECTORY_SEPARATOR.self::RELATIVE_PATH,
        ];

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                return $resolved;
            }
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $this->fail('Membership register not found at '.self::RELATIVE_PATH);
    }

    /**
     * @param  list<array<string, mixed>>  $seats
     * @return array<string, mixed>
     */
    private function seatById(array $seats, string $adrId): array
    {
        foreach ($seats as $seat) {
            if (($seat['adr_id'] ?? null) === $adrId) {
                return $seat;
            }
        }

        $this->fail("Missing seat {$adrId}");
    }
}
