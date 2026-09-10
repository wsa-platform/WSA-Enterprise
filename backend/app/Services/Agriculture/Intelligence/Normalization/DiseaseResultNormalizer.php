<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

use App\Services\Agriculture\Intelligence\Contracts\SourceRole;

final class DiseaseResultNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw, string $providerId): array
    {
        return [
            'evidence_family' => 'diagnostic',
            'source_role' => SourceRole::DIAGNOSTIC_RESULT,
            'provider_id' => $providerId,
            'disease_name' => $raw['disease_name'] ?? $raw['label'] ?? $raw['name'] ?? null,
            'confidence' => isset($raw['confidence']) && is_numeric($raw['confidence'])
                ? (float) $raw['confidence']
                : null,
            'symptoms' => is_array($raw['symptoms'] ?? null) ? $raw['symptoms'] : [],
            'recommendations' => is_array($raw['recommendations'] ?? null) ? $raw['recommendations'] : [],
            'raw_keys' => array_keys($raw),
        ];
    }
}
