<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\CrossRefScientificClient;
use App\Services\Agriculture\OpenAlexScientificClient;

/**
 * Normalizes external scholarly works into the Stage 3 result contract.
 */
class ScientificResultNormalizer
{
    public function __construct(
        private OpenAlexScientificClient $openAlexClient,
        private CrossRefScientificClient $crossRefClient,
    ) {}

    /**
     * @param  array<string, mixed>  $work
     */
    public function fromOpenAlexWork(array $work): ?ScientificSearchResult
    {
        $title = trim(strip_tags((string) ($work['display_name'] ?? $work['title'] ?? '')));
        if ($title === '') {
            return null;
        }

        $doiUrl = trim((string) ($work['doi'] ?? ''));
        $doi = $this->normalizeDoi($doiUrl !== '' ? str_replace('https://doi.org/', '', $doiUrl) : null);
        $landingPage = trim((string) ($work['primary_location']['landing_page_url'] ?? ''));
        $openAlexId = trim((string) ($work['id'] ?? ''));
        $canonicalUrl = $landingPage !== '' ? $landingPage : ($doiUrl !== '' ? $doiUrl : ($openAlexId !== '' ? $openAlexId : null));
        $abstract = $this->openAlexClient->reconstructAbstract(
            is_array($work['abstract_inverted_index'] ?? null) ? $work['abstract_inverted_index'] : null,
        );
        $journal = trim((string) ($work['primary_location']['source']['display_name'] ?? ''));
        $year = is_numeric($work['publication_year'] ?? null) ? (int) $work['publication_year'] : null;

        return new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: $openAlexId !== '' ? $openAlexId : null,
            title: $title,
            authors: $this->authorsFromOpenAlexWork($work),
            publicationYear: $year,
            doi: $doi,
            canonicalUrl: $canonicalUrl,
            abstract: $abstract !== '' ? $abstract : null,
            journal: $journal !== '' ? $journal : null,
            foundBySources: ['openalex'],
            rawMetadata: ['openalex' => $work],
        );
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public function fromCrossRefWork(array $work): ?ScientificSearchResult
    {
        $title = $this->crossRefClient->titleFromWork($work);
        if ($title === '') {
            return null;
        }

        $doi = $this->normalizeDoi(trim((string) ($work['DOI'] ?? '')) ?: null);
        $doiUrl = $doi !== null ? 'https://doi.org/'.$doi : '';
        $url = $doiUrl !== '' ? $doiUrl : (trim((string) ($work['URL'] ?? '')) ?: null);
        $abstract = $this->crossRefClient->abstractFromWork($work);
        $container = $work['container-title'] ?? null;
        $journal = is_array($container) ? trim((string) ($container[0] ?? '')) : '';
        $year = null;
        $issued = $work['issued']['date-parts'][0][0] ?? null;
        if (is_numeric($issued)) {
            $year = (int) $issued;
        }

        $crossrefId = $doi ?? $url;

        return new ScientificSearchResult(
            sourceKey: 'crossref',
            sourceIdentifier: $crossrefId !== null && $crossrefId !== '' ? $crossrefId : null,
            title: $title,
            authors: $this->authorsFromCrossRefWork($work),
            publicationYear: $year,
            doi: $doi,
            canonicalUrl: $url,
            abstract: $abstract !== '' ? $abstract : null,
            journal: $journal !== '' ? $journal : null,
            foundBySources: ['crossref'],
            rawMetadata: ['crossref' => $work],
        );
    }

    /**
     * @param  array<string, mixed>  $work
     * @return list<string>
     */
    private function authorsFromOpenAlexWork(array $work): array
    {
        $authors = [];
        $authorships = is_array($work['authorships'] ?? null) ? $work['authorships'] : [];
        foreach ($authorships as $authorship) {
            if (! is_array($authorship)) {
                continue;
            }
            $author = trim((string) ($authorship['author']['display_name'] ?? ''));
            if ($author !== '') {
                $authors[] = $author;
            }
        }

        return array_values(array_unique($authors));
    }

    /**
     * @param  array<string, mixed>  $work
     * @return list<string>
     */
    private function authorsFromCrossRefWork(array $work): array
    {
        $authors = [];
        $rawAuthors = is_array($work['author'] ?? null) ? $work['author'] : [];
        foreach ($rawAuthors as $author) {
            if (is_array($author)) {
                $given = trim((string) ($author['given'] ?? ''));
                $family = trim((string) ($author['family'] ?? ''));
                $name = trim($given.' '.$family);
                if ($name !== '') {
                    $authors[] = $name;
                }

                continue;
            }
            $name = trim((string) $author);
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        return array_values(array_unique($authors));
    }

    private function normalizeDoi(?string $doi): ?string
    {
        if ($doi === null) {
            return null;
        }
        $doi = strtolower(trim($doi));
        if ($doi === '') {
            return null;
        }

        return $doi;
    }
}
