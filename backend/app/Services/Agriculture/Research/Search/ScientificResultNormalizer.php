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
     * Normalize a Consensus.app search hit into the Stage 3 result contract.
     * Missing DOI/abstract stay null — never fabricate.
     *
     * @param  array<string, mixed>  $work
     */
    public function fromConsensusWork(array $work): ?ScientificSearchResult
    {
        $title = trim(strip_tags((string) ($work['title'] ?? '')));
        if ($title === '') {
            return null;
        }

        $doi = $this->normalizeDoi(trim((string) ($work['doi'] ?? '')) ?: null);
        $url = trim((string) ($work['url'] ?? ''));
        $canonicalUrl = $url !== '' ? $url : ($doi !== null ? 'https://doi.org/'.$doi : null);
        $abstract = trim(strip_tags((string) ($work['abstract'] ?? '')));
        $journal = trim((string) ($work['journal_name'] ?? ''));
        $year = is_numeric($work['publish_year'] ?? null) ? (int) $work['publish_year'] : null;

        $sourceIdentifier = $doi
            ?? ($url !== '' ? $url : null)
            ?? ('consensus:'.md5(mb_strtolower($title).'|'.($year ?? 'unknown')));

        $authors = [];
        $rawAuthors = is_array($work['authors'] ?? null) ? $work['authors'] : [];
        foreach ($rawAuthors as $author) {
            $name = trim(is_array($author) ? (string) ($author['name'] ?? '') : (string) $author);
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        $relevanceMetadata = [];
        if (isset($work['semantic_score']) && is_numeric($work['semantic_score'])) {
            $relevanceMetadata['semantic_score'] = (float) $work['semantic_score'];
        }
        if (isset($work['citation_count']) && is_numeric($work['citation_count'])) {
            $relevanceMetadata['citation_count'] = (int) $work['citation_count'];
        }
        if (is_array($work['countries_of_study'] ?? null)) {
            $relevanceMetadata['countries_of_study'] = array_values(array_filter(
                array_map(static fn ($c): string => strtolower(trim((string) $c)), $work['countries_of_study']),
                static fn (string $c): bool => $c !== '',
            ));
        }
        if (isset($work['publisher_name']) && is_string($work['publisher_name']) && trim($work['publisher_name']) !== '') {
            $relevanceMetadata['publisher_name'] = trim($work['publisher_name']);
        }

        return new ScientificSearchResult(
            sourceKey: 'consensus',
            sourceIdentifier: $sourceIdentifier,
            title: $title,
            authors: array_values(array_unique($authors)),
            publicationYear: $year,
            doi: $doi,
            canonicalUrl: $canonicalUrl,
            abstract: $abstract !== '' ? $abstract : null,
            journal: $journal !== '' ? $journal : null,
            foundBySources: ['consensus'],
            relevanceMetadata: $relevanceMetadata !== [] ? $relevanceMetadata : null,
            rawMetadata: ['consensus' => $work],
            relevanceScore: isset($relevanceMetadata['semantic_score'])
                ? (float) $relevanceMetadata['semantic_score']
                : null,
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
