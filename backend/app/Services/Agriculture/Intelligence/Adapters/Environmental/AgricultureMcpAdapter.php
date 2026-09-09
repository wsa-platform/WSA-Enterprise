<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Environmental;

use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use Illuminate\Support\Facades\Http;

/**
 * Agriculture MCP adapter — listed capabilities; NOT_CONFIGURED without infra.
 */
final class AgricultureMcpAdapter extends AbstractAgriculturalProvider
{
    public const ID = 'agriculture_mcp';

    protected function buildDescriptor(): ProviderDescriptor
    {
        $cfg = (array) config('agricultural_intelligence.mcp.agriculture', []);

        return new ProviderDescriptor(
            id: self::ID,
            name: 'Agriculture MCP',
            type: ProviderType::MCP,
            capabilities: [
                'soil_context',
                'crop_calendar_signals',
                'agronomy_tools',
                'knowledge_tooling',
            ],
            version: '1.0.0',
            priority: 65,
            timeoutSeconds: (int) ($cfg['timeout'] ?? 20),
            health: $this->isConfigured() ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: [
                'mode' => 'required_endpoint',
                'configured' => $this->isConfigured(),
            ],
            confidenceMeta: ['family' => 'mcp'],
            evidenceCapable: true,
            limitations: [
                'NOT_CONFIGURED without MCP endpoint',
                'Does not duplicate Open-Meteo weather logic',
            ],
            enabled: (bool) ($cfg['enabled'] ?? false),
        );
    }

    protected function isConfigured(): bool
    {
        $cfg = (array) config('agricultural_intelligence.mcp.agriculture', []);

        return filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)
            && trim((string) ($cfg['endpoint'] ?? '')) !== '';
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $cfg = (array) config('agricultural_intelligence.mcp.agriculture', []);
        $endpoint = (string) $cfg['endpoint'];
        $timeout = max(1, min(60, (int) ($cfg['timeout'] ?? 20)));
        $token = trim((string) ($cfg['token'] ?? ''));

        try {
            $pending = Http::timeout($timeout)->acceptJson();
            if ($token !== '') {
                $pending = $pending->withHeaders(['Authorization' => 'Bearer '.$token]);
            }
            $response = $pending->post($endpoint, [
                'tool' => 'agriculture.query',
                'arguments' => $input->toArray(),
            ]);
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed(self::ID, 'request_exception');
        }

        if (! $response->successful()) {
            return CanonicalAgriculturalResult::failed(self::ID, 'http_'.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            return CanonicalAgriculturalResult::empty(self::ID);
        }

        return new CanonicalAgriculturalResult(
            providerId: self::ID,
            status: 'success',
            claims: is_array($json['claims'] ?? null) ? $json['claims'] : [],
            entities: is_array($json['entities'] ?? null) ? $json['entities'] : [],
            confidence: 0.5,
            source: self::ID,
            timestamp: now()->toIso8601String(),
            meta: ['mcp' => true],
        );
    }
}
