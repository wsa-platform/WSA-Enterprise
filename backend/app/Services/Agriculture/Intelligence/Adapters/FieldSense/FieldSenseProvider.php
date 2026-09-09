<?php

namespace App\Services\Agriculture\Intelligence\Adapters\FieldSense;

use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use Illuminate\Support\Facades\Http;

/**
 * FieldSense field/sensor provider abstraction.
 */
final class FieldSenseProvider extends AbstractAgriculturalProvider
{
    public const ID = 'field_sense';

    protected function buildDescriptor(): ProviderDescriptor
    {
        $cfg = (array) config('agricultural_intelligence.field_sense', []);

        return new ProviderDescriptor(
            id: self::ID,
            name: 'FieldSense',
            type: ProviderType::FIELD,
            capabilities: ['field_sensors', 'soil_moisture', 'in_field_observations'],
            version: '1.0.0',
            priority: 70,
            timeoutSeconds: (int) ($cfg['timeout'] ?? 20),
            health: $this->isConfigured() ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: ['mode' => 'required_endpoint', 'configured' => $this->isConfigured()],
            confidenceMeta: ['family' => 'field'],
            evidenceCapable: true,
            limitations: ['NOT_CONFIGURED without endpoint/credentials'],
            enabled: (bool) ($cfg['enabled'] ?? false),
        );
    }

    protected function isConfigured(): bool
    {
        $cfg = (array) config('agricultural_intelligence.field_sense', []);

        return filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)
            && trim((string) ($cfg['endpoint'] ?? '')) !== '';
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $cfg = (array) config('agricultural_intelligence.field_sense', []);
        $endpoint = (string) $cfg['endpoint'];
        $timeout = max(1, min(60, (int) ($cfg['timeout'] ?? 20)));
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));

        try {
            $pending = Http::timeout($timeout)->acceptJson();
            if ($apiKey !== '') {
                $pending = $pending->withHeaders(['Authorization' => 'Bearer '.$apiKey]);
            }
            $response = $pending->get($endpoint, ['q' => $input->query, 'limit' => $input->limit]);
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed(self::ID, 'request_exception');
        }

        if (! $response->successful()) {
            return CanonicalAgriculturalResult::failed(self::ID, 'http_'.$response->status());
        }

        $json = $response->json();

        return new CanonicalAgriculturalResult(
            providerId: self::ID,
            status: 'success',
            measurements: is_array($json['measurements'] ?? null) ? $json['measurements'] : [],
            stats: is_array($json['stats'] ?? null) ? $json['stats'] : [],
            confidence: 0.55,
            source: self::ID,
            timestamp: now()->toIso8601String(),
        );
    }
}
