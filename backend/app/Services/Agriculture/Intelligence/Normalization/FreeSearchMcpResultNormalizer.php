<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

/**
 * Maps free-search-mcp hits into WSA web evidence. Missing fields stay null — never fabricated.
 */
final class FreeSearchMcpResultNormalizer
{
    public function __construct(
        private WebResultNormalizer $webNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>|null
     */
    public function normalize(array $hit, string $providerId): ?array
    {
        $base = $this->webNormalizer->normalize($hit, $providerId);
        if ($base === null) {
            return null;
        }

        $content = $this->stringOrNull($hit['content'] ?? $hit['text'] ?? $hit['body'] ?? null);
        $source = $this->stringOrNull($hit['source'] ?? $hit['sitename'] ?? $hit['site'] ?? null);
        $domain = $this->stringOrNull($hit['domain'] ?? $hit['host'] ?? null);
        if ($domain === null && is_string($base['url'] ?? null) && $base['url'] !== '') {
            $host = parse_url($base['url'], PHP_URL_HOST);
            $domain = is_string($host) && $host !== '' ? $host : null;
        }

        $published = $this->stringOrNull(
            $hit['published_at'] ?? $hit['published_date'] ?? $hit['date'] ?? $hit['published'] ?? null,
        );
        $author = $this->stringOrNull($hit['author'] ?? null);
        $engine = $this->stringOrNull($hit['engine'] ?? $hit['engines'] ?? $hit['provider'] ?? null);
        $rank = null;
        if (isset($hit['rank']) && is_numeric($hit['rank'])) {
            $rank = (int) $hit['rank'];
        } elseif (isset($hit['position']) && is_numeric($hit['position'])) {
            $rank = (int) $hit['position'];
        }
        $provenance = $this->stringOrNull($hit['provenance'] ?? null);

        $metadata = [];
        foreach (['gated_engines', 'gated_hint', 'filter_diagnostics', 'score'] as $metaKey) {
            if (array_key_exists($metaKey, $hit) && $hit[$metaKey] !== null && $hit[$metaKey] !== '') {
                $metadata[$metaKey] = $hit[$metaKey];
            }
        }

        $base['content'] = $content;
        $base['source'] = $source;
        $base['domain'] = $domain;
        $base['published_at'] = $published;
        $base['author'] = $author;
        $base['engine'] = $engine;
        $base['rank'] = $rank;
        $base['provenance'] = $provenance;
        $base['metadata'] = $metadata === [] ? null : $metadata;

        return $base;
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

    private function stringOrNull(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }
        $trimmed = trim(strip_tags((string) $value));

        return $trimmed === '' ? null : $trimmed;
    }
}
