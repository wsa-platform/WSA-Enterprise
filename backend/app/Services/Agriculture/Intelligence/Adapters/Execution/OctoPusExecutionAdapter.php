<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Execution;

use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OctoPus execution adapter — validated I/O, isolation, timeout, logging, limits.
 * License metadata is recorded; commercial use is NOT assumed OK.
 */
final class OctoPusExecutionAdapter extends AbstractAgriculturalProvider
{
    public const ID = 'octopus_execution';

    protected function buildDescriptor(): ProviderDescriptor
    {
        $cfg = (array) config('agricultural_intelligence.octopus', []);
        $license = (string) ($cfg['license_status'] ?? 'unknown');
        $blocked = ! in_array($license, ['permitted', 'internal_ok'], true);

        return new ProviderDescriptor(
            id: self::ID,
            name: 'OctoPus Execution',
            type: ProviderType::EXECUTION,
            capabilities: ['tool_execution', 'validated_io'],
            version: '1.0.0',
            priority: 90,
            timeoutSeconds: (int) ($cfg['timeout'] ?? 30),
            health: $blocked
                ? ProviderHealthState::BLOCKED
                : ($this->isConfigured() ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED),
            auth: [
                'mode' => 'required_endpoint',
                'configured' => $this->isConfigured(),
                'blocked' => $blocked,
                'block_reason' => $blocked
                    ? 'license_not_confirmed_for_commercial_use'
                    : null,
                'license_status' => $license,
            ],
            confidenceMeta: ['family' => 'execution'],
            evidenceCapable: false,
            limitations: [
                'License metadata must be reviewed — commercial OK is not assumed',
                'Input/output size limits enforced',
            ],
            enabled: (bool) ($cfg['enabled'] ?? false) && ! $blocked,
        );
    }

    protected function isConfigured(): bool
    {
        $cfg = (array) config('agricultural_intelligence.octopus', []);
        $license = (string) ($cfg['license_status'] ?? 'unknown');
        if (! in_array($license, ['permitted', 'internal_ok'], true)) {
            return false;
        }

        return filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)
            && trim((string) ($cfg['endpoint'] ?? '')) !== '';
    }

    public function health(): ProviderHealthStatus
    {
        $cfg = (array) config('agricultural_intelligence.octopus', []);
        $license = (string) ($cfg['license_status'] ?? 'unknown');
        if (! in_array($license, ['permitted', 'internal_ok'], true)) {
            return new ProviderHealthStatus(
                self::ID,
                ProviderHealthState::BLOCKED,
                'BLOCKED:license_not_confirmed',
                ['license_status' => $license],
            );
        }

        return parent::health();
    }

    public function retrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $cfg = (array) config('agricultural_intelligence.octopus', []);
        $license = (string) ($cfg['license_status'] ?? 'unknown');
        if (! in_array($license, ['permitted', 'internal_ok'], true)) {
            return CanonicalAgriculturalResult::blocked(self::ID, 'BLOCKED:license_not_confirmed');
        }

        return parent::retrieve($input);
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $cfg = (array) config('agricultural_intelligence.octopus', []);
        $license = (string) ($cfg['license_status'] ?? 'unknown');
        if (! in_array($license, ['permitted', 'internal_ok'], true)) {
            return CanonicalAgriculturalResult::blocked(self::ID, 'BLOCKED:license_not_confirmed');
        }

        $maxInput = max(256, min(100000, (int) ($cfg['max_input_bytes'] ?? 16384)));
        $payload = json_encode($input->toArray(), JSON_THROW_ON_ERROR);
        if (strlen($payload) > $maxInput) {
            return CanonicalAgriculturalResult::failed(self::ID, 'input_limit_exceeded');
        }

        $endpoint = (string) $cfg['endpoint'];
        $timeout = max(1, min(120, (int) ($cfg['timeout'] ?? 30)));
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));

        Log::info('OctoPus execution invoked', [
            'provider' => self::ID,
            'input_bytes' => strlen($payload),
            'license_status' => $license,
        ]);

        try {
            $pending = Http::timeout($timeout)->acceptJson();
            if ($apiKey !== '') {
                $pending = $pending->withHeaders(['Authorization' => 'Bearer '.$apiKey]);
            }
            $response = $pending->post($endpoint, json_decode($payload, true));
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed(self::ID, 'request_exception');
        }

        if (! $response->successful()) {
            return CanonicalAgriculturalResult::failed(self::ID, 'http_'.$response->status());
        }

        $body = $response->body();
        $maxOutput = max(256, min(500000, (int) ($cfg['max_output_bytes'] ?? 65536)));
        if (strlen($body) > $maxOutput) {
            return CanonicalAgriculturalResult::failed(self::ID, 'output_limit_exceeded');
        }

        $json = $response->json();
        if (! is_array($json)) {
            return CanonicalAgriculturalResult::empty(self::ID);
        }

        return new CanonicalAgriculturalResult(
            providerId: self::ID,
            status: 'success',
            claims: is_array($json['claims'] ?? null) ? $json['claims'] : [],
            meta: [
                'execution' => true,
                'license_status' => $license,
                'validated_io' => true,
            ],
            confidence: 0.4,
            source: self::ID,
            timestamp: now()->toIso8601String(),
            limitations: ['execution_output_not_scientific_evidence'],
        );
    }
}
