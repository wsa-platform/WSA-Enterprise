<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;

final class AgriculturalResultNormalizer
{
    public function __construct(
        private UnitNormalizationService $units,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public function normalize(string $providerId, array $raw, string $status = 'success'): CanonicalAgriculturalResult
    {
        $measurements = [];
        foreach ($raw['measurements'] ?? [] as $m) {
            if (! is_array($m)) {
                continue;
            }
            $measurements[] = $this->units->normalize(
                $m['value'] ?? null,
                isset($m['unit']) ? (string) $m['unit'] : null,
            );
        }

        return new CanonicalAgriculturalResult(
            providerId: $providerId,
            status: $status,
            claims: is_array($raw['claims'] ?? null) ? $raw['claims'] : [],
            measurements: $measurements,
            entities: is_array($raw['entities'] ?? null) ? $raw['entities'] : [],
            disease: is_array($raw['disease'] ?? null) ? $raw['disease'] : [],
            weather: is_array($raw['weather'] ?? null) ? $raw['weather'] : [],
            stats: is_array($raw['stats'] ?? null) ? $raw['stats'] : [],
            scientificEvidence: is_array($raw['scientific_evidence'] ?? null) ? $raw['scientific_evidence'] : [],
            webEvidence: is_array($raw['web_evidence'] ?? null) ? $raw['web_evidence'] : [],
            confidence: isset($raw['confidence']) && is_numeric($raw['confidence']) ? (float) $raw['confidence'] : null,
            source: isset($raw['source']) ? (string) $raw['source'] : $providerId,
            timestamp: now()->toIso8601String(),
            limitations: is_array($raw['limitations'] ?? null) ? array_values($raw['limitations']) : [],
            error: isset($raw['error']) ? (string) $raw['error'] : null,
            meta: is_array($raw['meta'] ?? null) ? $raw['meta'] : [],
        );
    }
}
