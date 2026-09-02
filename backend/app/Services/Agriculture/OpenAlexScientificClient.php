<?php

namespace App\Services\Agriculture;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches real bibliographic metadata from the OpenAlex scholarly index.
 * Does not fabricate DOIs, URLs, or publication metadata.
 */
class OpenAlexScientificClient
{
    /** @return list<array<string, mixed>> */
    public function searchWorks(string $query, int $perPage = 5): array
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get('https://api.openalex.org/works', [
                    'search' => $query,
                    'per_page' => max(1, min($perPage, 10)),
                    'mailto' => (string) config('wsa.openalex_mailto', 'wsa-platform@example.com'),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('OpenAlex search request failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('OpenAlex search failed', [
                'status' => $response->status(),
                'query' => $query,
            ]);

            return [];
        }

        $results = $response->json('results');

        return is_array($results) ? $results : [];
    }

  /**
     * @param  array<string, list<int>>|null  $invertedIndex
     */
    public function reconstructAbstract(?array $invertedIndex): string
    {
        if ($invertedIndex === null || $invertedIndex === []) {
            return '';
        }

        $positions = [];
        foreach ($invertedIndex as $word => $indexes) {
            if (! is_array($indexes)) {
                continue;
            }
            foreach ($indexes as $index) {
                $positions[(int) $index] = (string) $word;
            }
        }

        if ($positions === []) {
            return '';
        }

        ksort($positions);

        return trim(implode(' ', $positions));
    }

    /**
     * @param  array<string, mixed>  $work
     * @return array{organization: string, title: string, year: int|null, url: string, doi: string|null, source_type: string}
     */
    public function buildSourceFromWork(array $work): array
    {
        $title = trim(strip_tags((string) ($work['display_name'] ?? $work['title'] ?? '')));
        $doiUrl = trim((string) ($work['doi'] ?? ''));
        $doi = $doiUrl !== '' ? str_replace('https://doi.org/', '', $doiUrl) : null;
        $landingPage = trim((string) ($work['primary_location']['landing_page_url'] ?? ''));
        $openAlexId = trim((string) ($work['id'] ?? ''));
        $url = $landingPage !== '' ? $landingPage : ($doiUrl !== '' ? $doiUrl : $openAlexId);

        $organization = '';
        $sourceType = 'peer_reviewed_journal';

        $journal = trim((string) ($work['primary_location']['source']['display_name'] ?? ''));
        if ($journal !== '') {
            $organization = $journal;
            $sourceType = 'peer_reviewed_journal';
        }

        $authorships = is_array($work['authorships'] ?? null) ? $work['authorships'] : [];
        foreach ($authorships as $authorship) {
            if (! is_array($authorship)) {
                continue;
            }
            $institutions = is_array($authorship['institutions'] ?? null) ? $authorship['institutions'] : [];
            foreach ($institutions as $institution) {
                if (! is_array($institution)) {
                    continue;
                }
                $name = trim((string) ($institution['display_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                if ($organization === '' || $sourceType === 'peer_reviewed_journal') {
                    $organization = $name;
                    $sourceType = $this->sourceTypeForInstitution($name, (string) ($institution['type'] ?? ''));
                }
                break 2;
            }
        }

        if ($organization === '') {
            $organization = 'OpenAlex indexed publication';
            $sourceType = 'supporting_verified';
        }

        $year = is_numeric($work['publication_year'] ?? null) ? (int) $work['publication_year'] : null;

        return [
            'organization' => $organization,
            'title' => $title,
            'year' => $year,
            'url' => $url,
            'doi' => $doi,
            'source_type' => $sourceType,
        ];
    }

    private function sourceTypeForInstitution(string $name, string $institutionType): string
    {
        $upper = strtoupper($name);
        foreach ([
            'FAO' => 'international_organization',
            'CIMMYT' => 'research_institute',
            'USDA' => 'government',
            'CGIAR' => 'international_organization',
        ] as $needle => $type) {
            if (str_contains($upper, $needle)) {
                return $type;
            }
        }

        return match ($institutionType) {
            'education' => 'university_research',
            'government' => 'government',
            'facility', 'healthcare' => 'research_institute',
            'company' => 'supporting_verified',
            default => 'peer_reviewed_journal',
        };
    }
}
