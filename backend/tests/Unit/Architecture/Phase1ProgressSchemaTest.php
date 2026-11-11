<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class Phase1ProgressSchemaTest extends TestCase
{
    public function test_phase_1_progress_json_is_valid_syntax_and_required_shape(): void
    {
        $path = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'phase-1-progress.json';
        $this->assertFileExists($path);
        $raw = file_get_contents($path);
        $this->assertNotFalse($raw);
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('1.0', $data['schema_version']);
        $this->assertSame('PHASE_1_GAP_CLOSURE_PARTIAL', $data['classification']);
        $this->assertIsString($data['last_updated']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['last_updated']);
        $this->assertNotSame('', $data['repository']);
        $this->assertNotSame('', $data['branch']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{7,40}$/', (string) $data['head']);

        foreach ([
            'P1-EXIT-01', 'P1-EXIT-02', 'P1-EXIT-03', 'P1-EXIT-04', 'P1-EXIT-05',
            'P1-EXIT-06', 'P1-EXIT-07', 'P1-EXIT-08', 'P1-EXIT-09', 'P1-EXIT-10', 'P1-EXIT-11',
        ] as $id) {
            $this->assertArrayHasKey($id, $data['criteria']);
            $c = $data['criteria'][$id];
            $this->assertContains($c['status'], [
                'PASS', 'PARTIAL', 'BLOCKED', 'NOT_RUN', 'NOT_APPLICABLE', 'IN_PROGRESS', 'VERIFIED_PENDING_FINAL_REVIEW',
            ]);
            $this->assertIsArray($c['requirements']);
            $this->assertIsArray($c['evidence_files']);
            $this->assertIsArray($c['test_commands']);
            $this->assertIsArray($c['evidence_commits']);
            $this->assertIsArray($c['blockers']);
            $this->assertIsString($c['next_action']);
            if ($c['status'] === 'PASS') {
                $this->assertTrue(
                    $c['evidence_files'] !== [] || ($c['test_results']['summary'] ?? '') !== '',
                    "PASS without evidence for {$id}"
                );
            }
        }

        $this->assertSame('NOT_APPLICABLE', $data['layer_h']['status']);
        $this->assertSame('BLOCKED', $data['d08']['status']);
        $this->assertIsArray($data['dependencies']);
        $this->assertIsArray($data['next_session_focus']);
    }

    public function test_schema_file_exists_and_declares_version_1_0(): void
    {
        $path = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'phase-1-progress.schema.json';
        $this->assertFileExists($path);
        $schema = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('1.0', $schema['properties']['schema_version']['const']);
    }

    public function test_no_pass_with_blocking_gate_ids_on_exit_07(): void
    {
        $path = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'phase-1-progress.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotSame('PASS', $data['criteria']['P1-EXIT-07']['status']);
        $this->assertContains('P1-BLK-2026-11-11-G2-26-01', $data['criteria']['P1-EXIT-07']['blockers']);
    }
}
