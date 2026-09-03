<?php

namespace App\Services\Agriculture;

use App\Support\ScientificHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches real bibliographic metadata from the Crossref scholarly index.
 * Does not fabricate DOIs, URLs, or publication metadata.
 */
class CrossRefScientificClient
{
    /** @return list<array<string, mixed>> */
    public function searchWorks(string $query, int $perPage = 5): array
    {
        try {
            $response = Http::timeout(ScientificHttp::timeoutSeconds())
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => (string) config('wsa.crossref_mailto', 'wsa-platform/1.0 (mailto:wsa-platform@example.com)'),
                ])
                ->get('https://api.crossref.org/works', [
                    'query' => $query,
                    'rows' => max(1, min($perPage, 10)),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Crossref search request failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Crossref search failed', [
                'status' => $response->status(),
                'query' => $query,
            ]);

            return [];
        }

        $items = $response->json('message.items');

        return is_array($items) ? $items : [];
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public function titleFromWork(array $work): string
    {
        $title = $work['title'] ?? null;
        if (is_array($title)) {
            return trim(strip_tags((string) ($title[0] ?? '')));
        }

        return trim(strip_tags((string) $title));
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public function abstractFromWork(array $work): string
    {
        $abstract = trim(strip_tags((string) ($work['abstract'] ?? '')));
        if ($abstract !== '') {
            return $abstract;
        }

        $subtitle = $work['subtitle'] ?? null;
        if (is_array($subtitle) && isset($subtitle[0])) {
            return trim(strip_tags((string) $subtitle[0]));
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $work
     * @return array{organization: string, title: string, year: int|null, url: string, doi: string|null, source_type: string}
     */
    public function buildSourceFromWork(array $work): array
    {
        $title = $this->titleFromWork($work);
        $doi = trim((string) ($work['DOI'] ?? ''));
        $doiUrl = $doi !== '' ? 'https://doi.org/'.$doi : '';
        $url = $doiUrl;
        if ($url === '') {
            $url = trim((string) ($work['URL'] ?? ''));
        }

        $organization = trim((string) ($work['publisher'] ?? ''));
        $sourceType = 'peer_reviewed_journal';

        $container = $work['container-title'] ?? null;
        if (is_array($container) && isset($container[0])) {
            $journal = trim((string) $container[0]);
            if ($journal !== '') {
                $organization = $journal;
            }
        }

        $institutions = is_array($work['institution'] ?? null) ? $work['institution'] : [];
        foreach ($institutions as $institution) {
            if (! is_array($institution)) {
                continue;
            }
            $name = trim((string) ($institution['name'] ?? ''));
            if ($name !== '') {
                $organization = $name;
                $sourceType = 'university_research';
                break;
            }
        }

        if ($organization === '') {
            $organization = 'Crossref indexed publication';
            $sourceType = 'supporting_verified';
        }

        $year = null;
        $issued = $work['issued']['date-parts'][0][0] ?? null;
        if (is_numeric($issued)) {
            $year = (int) $issued;
        }

        return [
            'organization' => $organization,
            'title' => $title,
            'year' => $year,
            'url' => $url,
            'doi' => $doi !== '' ? $doi : null,
            'source_type' => $sourceType,
        ];
    }
}
