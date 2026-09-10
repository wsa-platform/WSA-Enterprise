<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

use App\Services\Agriculture\Intelligence\Contracts\SourceRole;

final class EnvironmentalResultNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw, string $providerId): array
    {
        return [
            'evidence_family' => 'environmental',
            'source_role' => SourceRole::ENVIRONMENTAL_DATA,
            'provider_id' => $providerId,
            'temperature_c' => $raw['temperature_c'] ?? $raw['temperature'] ?? null,
            'precipitation_mm' => $raw['precipitation_mm'] ?? $raw['precipitation'] ?? null,
            'humidity' => $raw['humidity'] ?? null,
            'wind_speed' => $raw['wind_speed'] ?? null,
            'location' => $raw['location'] ?? null,
            'observed_at' => $raw['observed_at'] ?? $raw['time'] ?? null,
            'context' => $raw['location'] ?? $raw['observed_at'] ?? null,
            'meta' => is_array($raw['meta'] ?? null) ? $raw['meta'] : [],
        ];
    }
}
