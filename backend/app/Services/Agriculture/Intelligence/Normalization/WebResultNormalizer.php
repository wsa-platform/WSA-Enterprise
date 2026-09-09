<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

/**
 * Normalizes general web search hits (not scholarly SS/FAO).
 */
final class WebResultNormalizer
{
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

        $numeric = null;
        if (isset($hit['numeric_value']) && is_numeric($hit['numeric_value'])) {
            $numeric = (float) $hit['numeric_value'];
        } elseif (preg_match('/(-?\d+(?:\.\d+)?)\s*(%|c|°c|mm|kg|ha|t\b)?/iu', $snippet, $m)) {
            $numeric = (float) $m[1];
        }

        return [
            'evidence_family' => 'web',
            'provider_id' => $providerId,
            'title' => $title !== '' ? $title : null,
            'url' => $url !== '' ? $url : null,
            'snippet' => $snippet !== '' ? $snippet : null,
            'numeric_value' => $numeric,
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
}
