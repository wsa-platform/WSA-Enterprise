<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\MeasurementValue;

/**
 * Normalizes general web search hits (not scholarly SS/FAO).
 */
final class WebResultNormalizer
{
    public function __construct(
        private UnitNormalizationService $units,
    ) {}

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>|null
     */
    public function normalize(array $hit, string $providerId): ?array
    {
        $title = trim(strip_tags((string) ($hit['title'] ?? '')));
        $url = trim((string) ($hit['url'] ?? $hit['link'] ?? ''));
        $snippet = trim(strip_tags((string) ($hit['snippet'] ?? $hit['description'] ?? $hit['content'] ?? '')));

        if ($title === '' && $url === '' && $snippet === '') {
            return null;
        }

        $extracted = $this->extractNumericContext($hit, $snippet);
        $measurement = $extracted['unit'] !== null || $extracted['value'] !== null
            ? $this->units->normalize($extracted['value'], $extracted['unit'])
            : null;
        if ($measurement instanceof MeasurementValue
            && $extracted['range_min'] !== null
            && $extracted['range_max'] !== null) {
            $measurement = new MeasurementValue(
                value: $measurement->value,
                unit: $measurement->unit,
                normalizedValue: $measurement->normalizedValue,
                normalizedUnit: $measurement->normalizedUnit,
                rangeMin: $extracted['range_min'],
                rangeMax: $extracted['range_max'],
                conversionSafe: $measurement->conversionSafe,
                conversionNote: $measurement->conversionNote,
            );
        }

        return [
            'evidence_family' => 'web',
            'source_role' => SourceRole::WEB_SOURCE,
            'provider_id' => $providerId,
            'title' => $title !== '' ? $title : null,
            'url' => $url !== '' ? $url : null,
            'snippet' => $snippet !== '' ? $snippet : null,
            'context' => $snippet !== '' ? $snippet : ($title !== '' ? $title : null),
            'numeric_value' => $extracted['value'],
            'unit' => $extracted['unit'],
            'range_min' => $extracted['range_min'],
            'range_max' => $extracted['range_max'],
            'measurement' => $measurement?->toArray(),
            'confidence' => isset($hit['confidence']) && is_numeric($hit['confidence'])
                ? (float) $hit['confidence']
                : 0.45,
            'quality' => isset($hit['quality']) && is_numeric($hit['quality'])
                ? (float) $hit['quality']
                : 0.45,
            'retrieved_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hits
     * @return list<array<string, mixed>>
     */
    public function normalizeMany(array $hits, string $providerId): array
    {
        $out = [];
        foreach ($hits as $hit) {
            if (! is_array($hit)) {
                continue;
            }
            $n = $this->normalize($hit, $providerId);
            if ($n !== null) {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * Generic numeric/range/unit extraction. Keeps surrounding snippet as context.
     *
     * @param  array<string, mixed>  $hit
     * @return array{value: float|null, unit: string|null, range_min: float|null, range_max: float|null}
     */
    private function extractNumericContext(array $hit, string $snippet): array
    {
        $unit = isset($hit['unit']) && is_string($hit['unit']) && trim($hit['unit']) !== ''
            ? trim($hit['unit'])
            : null;
        $rangeMin = isset($hit['range_min']) && is_numeric($hit['range_min']) ? (float) $hit['range_min'] : null;
        $rangeMax = isset($hit['range_max']) && is_numeric($hit['range_max']) ? (float) $hit['range_max'] : null;
        $value = null;
        if (isset($hit['numeric_value']) && is_numeric($hit['numeric_value'])) {
            $value = (float) $hit['numeric_value'];
        }

        if ($value === null && $rangeMin === null && preg_match(
            '/(-?\d+(?:\.\d+)?)\s*(?:–|-|to|إلى)\s*(-?\d+(?:\.\d+)?)\s*(%|°c|celsius|c\b|mm|cm|m\b|kg|ha|t\b|ec|ph)?/iu',
            $snippet,
            $range,
        )) {
            $rangeMin = (float) $range[1];
            $rangeMax = (float) $range[2];
            if (($range[3] ?? '') !== '') {
                $unit = $this->canonicalUnit((string) $range[3]);
            }
        } elseif ($value === null && preg_match(
            '/(-?\d+(?:\.\d+)?)\s*(%|°c|celsius|c\b|mm|cm|m\b|kg|ha|t\b|ec|ph)?/iu',
            $snippet,
            $m,
        )) {
            $value = (float) $m[1];
            if (($m[2] ?? '') !== '') {
                $unit = $this->canonicalUnit((string) $m[2]);
            }
        }

        return [
            'value' => $value,
            'unit' => $unit,
            'range_min' => $rangeMin,
            'range_max' => $rangeMax,
        ];
    }

    private function canonicalUnit(string $raw): string
    {
        $key = strtolower(trim($raw));

        return match ($key) {
            '°c', 'c', 'celsius' => 'c',
            default => $key,
        };
    }
}
