<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Environmental;

use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\Normalization\EnvironmentalResultNormalizer;
use Illuminate\Support\Facades\Http;

/**
 * Open-Meteo primary environmental/weather provider (no API key required).
 * Does not duplicate weather logic into MCP adapters.
 */
final class OpenMeteoEnvironmentalAdapter extends AbstractAgriculturalProvider
{
    public const ID = 'open_meteo';

    public function __construct(
        private EnvironmentalResultNormalizer $normalizer,
    ) {}

    protected function buildDescriptor(): ProviderDescriptor
    {
        $enabled = (bool) config('agricultural_intelligence.open_meteo.enabled', true);

        return new ProviderDescriptor(
            id: self::ID,
            name: 'Open-Meteo',
            type: ProviderType::ENVIRONMENTAL,
            capabilities: ['weather', 'forecast', 'historical_weather'],
            version: '1.0.0',
            priority: 20,
            timeoutSeconds: (int) config('agricultural_intelligence.open_meteo.timeout', 15),
            health: $enabled ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: ['mode' => 'none'],
            confidenceMeta: ['family' => 'environmental'],
            evidenceCapable: true,
            limitations: ['Requires latitude/longitude in constraints'],
            config: ['base_url' => config('agricultural_intelligence.open_meteo.base_url')],
            enabled: $enabled,
        );
    }

    protected function isConfigured(): bool
    {
        return (bool) config('agricultural_intelligence.open_meteo.enabled', true);
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $lat = $input->constraints['latitude'] ?? $input->context['latitude'] ?? null;
        $lon = $input->constraints['longitude'] ?? $input->context['longitude'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return CanonicalAgriculturalResult::empty(self::ID, 'missing_coordinates');
        }

        $base = rtrim((string) config(
            'agricultural_intelligence.open_meteo.base_url',
            'https://api.open-meteo.com/v1/forecast',
        ), '/');
        $timeout = max(1, min(60, (int) config('agricultural_intelligence.open_meteo.timeout', 15)));

        try {
            $response = Http::timeout($timeout)->acceptJson()->get($base, [
                'latitude' => (float) $lat,
                'longitude' => (float) $lon,
                'current_weather' => true,
            ]);
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed(self::ID, 'request_exception');
        }

        if (! $response->successful()) {
            return CanonicalAgriculturalResult::failed(self::ID, 'http_'.$response->status());
        }

        $json = $response->json();
        $current = is_array($json['current_weather'] ?? null) ? $json['current_weather'] : [];
        $weather = [$this->normalizer->normalize([
            'temperature_c' => $current['temperature'] ?? null,
            'wind_speed' => $current['windspeed'] ?? null,
            'observed_at' => $current['time'] ?? null,
            'location' => ['latitude' => (float) $lat, 'longitude' => (float) $lon],
            'meta' => ['provider' => self::ID],
        ], self::ID)];

        return new CanonicalAgriculturalResult(
            providerId: self::ID,
            status: 'success',
            weather: $weather,
            confidence: 0.7,
            source: self::ID,
            timestamp: now()->toIso8601String(),
        );
    }
}
