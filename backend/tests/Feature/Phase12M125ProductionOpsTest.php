<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class Phase12M125ProductionOpsTest extends TestCase
{
    private function repoRoot(): string
    {
        foreach ([dirname(base_path()), '/var/www/repo'] as $root) {
            if (is_file($root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'backup-production.sh')) {
                return $root;
            }
        }

        $this->markTestSkipped('Production ops scripts not available in this environment.');
    }

    private function scriptPath(string $name): string
    {
        $path = $this->repoRoot().DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.$name;
        if (! is_file($path)) {
            $this->markTestSkipped("Script not available: {$name}");
        }

        return $path;
    }

    /** @param  array<string, string>  $env */
    private function runScript(string $name, array $env = []): Process
    {
        $path = $this->scriptPath($name);
        $envExports = 'PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin HOME=/tmp';
        foreach ($env as $key => $value) {
            $envExports .= ' '.escapeshellarg($key).'='.escapeshellarg($value);
        }

        $process = Process::fromShellCommandline(
            "env -i {$envExports} bash ".escapeshellarg($path),
            $this->repoRoot(),
        );
        $process->run();

        return $process;
    }

    public function test_backup_script_dry_run_validates_required_configuration(): void
    {
        $process = $this->runScript('backup-production.sh', [
            'DRY_RUN' => '1',
            'POSTGRES_DB' => 'wsa_enterprise',
            'POSTGRES_USER' => 'wsa',
            'POSTGRES_PASSWORD' => 'test-backup-password-not-logged',
        ]);

        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString('DRY RUN', $combined);
        $this->assertStringContainsString('no dump executed', $combined);
    }

    public function test_backup_script_fails_when_required_configuration_is_missing(): void
    {
        $process = $this->runScript('backup-production.sh', [
            'POSTGRES_DB' => 'wsa_enterprise',
            'POSTGRES_USER' => 'wsa',
        ]);

        $this->assertFalse($process->isSuccessful());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString('POSTGRES_PASSWORD', $combined);
    }

    public function test_backup_script_does_not_print_secrets(): void
    {
        $secret = 'super-secret-backup-test-value-xyz';

        $process = $this->runScript('backup-production.sh', [
            'DRY_RUN' => '1',
            'POSTGRES_DB' => 'wsa_enterprise',
            'POSTGRES_USER' => 'wsa',
            'POSTGRES_PASSWORD' => $secret,
        ]);

        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringNotContainsString($secret, $combined);
    }

    public function test_rollback_script_requires_explicit_image_tag(): void
    {
        $process = $this->runScript('rollback-production.sh', [
            'DOMAIN' => 'app.example.com',
        ]);

        $this->assertFalse($process->isSuccessful());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString('IMAGE_TAG is required', $combined);
    }

    public function test_rollback_script_refuses_mutable_main_tag(): void
    {
        $process = $this->runScript('rollback-production.sh', [
            'DOMAIN' => 'app.example.com',
            'IMAGE_TAG' => 'main',
        ]);

        $this->assertFalse($process->isSuccessful());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString("mutable tag 'main'", $combined);
    }

    public function test_rollback_script_refuses_unsafe_non_immutable_target(): void
    {
        $process = $this->runScript('rollback-production.sh', [
            'DOMAIN' => 'app.example.com',
            'IMAGE_TAG' => 'latest',
        ]);

        $this->assertFalse($process->isSuccessful());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString('immutable reference', $combined);
    }

    public function test_rollback_script_prints_explicit_target_before_deploy_actions(): void
    {
        $sha = 'dec5049a1b2c3d4e5f6789012345678901234567';

        $process = $this->runScript('rollback-production.sh', [
            'DOMAIN' => 'app.example.com',
            'IMAGE_TAG' => $sha,
        ]);

        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString("Target IMAGE_TAG: {$sha}", $combined);
    }

    public function test_verify_production_script_requires_domain(): void
    {
        $process = $this->runScript('verify-production.sh', []);

        $this->assertFalse($process->isSuccessful());
        $combined = $process->getOutput().$process->getErrorOutput();
        $this->assertStringContainsString('DOMAIN', $combined);
    }

    public function test_production_scripts_exist_and_are_non_empty(): void
    {
        foreach (['backup-production.sh', 'rollback-production.sh', 'verify-production.sh'] as $script) {
            $path = $this->scriptPath($script);
            $this->assertGreaterThan(100, filesize($path), "{$script} should contain implementation");
        }
    }
}
