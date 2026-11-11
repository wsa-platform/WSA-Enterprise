<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use PHPUnit\Framework\TestCase;

/**
 * P1-EXIT-08: contract packages must exist and must not claim live family verification.
 *
 * ADR-023 §8.24.5 / D-10.8 — ACCESS DOCUMENTED + adapters/tests ≠ ENDPOINT TESTED / INTEGRATION VERIFIED.
 */
final class VerifiedFamilyEvidencePackageContractTest extends TestCase
{
    private const PACKAGES = [
        'RestJson' => '2026-11-11-PHASE-1-EXIT-08-FAMILY-VERIFICATION-PACKAGE.RestJson.v10.6.json',
        'OaiPmh' => '2026-11-11-PHASE-1-EXIT-08-FAMILY-VERIFICATION-PACKAGE.OaiPmh.v10.6.json',
        'Bulk' => '2026-11-11-PHASE-1-EXIT-08-FAMILY-VERIFICATION-PACKAGE.Bulk.v10.6.json',
    ];

    private const REQUIRED_KEYS = [
        'schema_id',
        'family_name',
        'authoritative',
        'adapter_implementation',
        'contract_tests',
        'verification_facts',
        'closure_status',
        'gate_b',
    ];

    public function test_each_family_verification_package_exists_with_required_keys_and_no_live_claims(): void
    {
        $repoRoot = dirname(__DIR__, 7);
        $root = $repoRoot.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'architecture'
            .DIRECTORY_SEPARATOR.'science-source-expansion';

        foreach (self::PACKAGES as $family => $filename) {
            $path = $root.DIRECTORY_SEPARATOR.$filename;
            $this->assertFileExists($path, $family);
            $raw = file_get_contents($path);
            $this->assertNotFalse($raw, $family);
            $data = json_decode($raw, true);
            $this->assertIsArray($data, $family);
            foreach (self::REQUIRED_KEYS as $key) {
                $this->assertArrayHasKey($key, $data, "{$family}.{$key}");
            }
            $this->assertSame('adr023.family_verification_package.v1', $data['schema_id'], $family);
            $this->assertSame('GB-FOUNDATION-01', $data['gate_b'], $family);
            $this->assertNotSame([], $data['contract_tests'], $family);
            $this->assertDirectoryExists(
                $repoRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $data['adapter_implementation']),
                "{$family} adapter path",
            );

            $facts = $data['verification_facts'];
            $this->assertIsArray($facts, $family);
            $this->assertFalse((bool) ($facts['identity_verified'] ?? true), $family);
            $this->assertSame('NOT_RUN', $facts['endpoint_tested'] ?? null, $family);
            $this->assertSame('NOT_RUN', $facts['integration_verified'] ?? null, $family);
            $this->assertFalse((bool) ($facts['live_connectivity_claimed'] ?? true), $family);

            $this->assertStringContainsString('PARTIAL', (string) $data['closure_status'], $family);
            $this->assertNotSame('PASS', $data['closure_status'], $family);
        }
    }
}
