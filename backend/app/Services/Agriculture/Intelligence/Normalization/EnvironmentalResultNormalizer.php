<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

final class EnvironmentalResultNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw, string $providerId): array
    {
        return [
            'provider_id' => $providerId,
            'temperature_c' => $raw['temperature_c'] ?? $raw['temperature'] ?? null,
            'precipitation_mm' => $raw['precipitation_mm'] ?? $raw['precipitation'] ?? null,
            'humidity' => $raw['humidity'] ?? null,
            'wind_speed' => $raw['wind_speed'] ?? null,
            'location' => $raw['location'] ?? null,
            'observed_at' => $raw['observed_at'] ?? $raw['time'] ?? null,
            'meta' => is_array($raw['meta'] ?? null) ? $raw['meta'] : [],
        ];
    }
}
