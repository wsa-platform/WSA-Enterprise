<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\CrossRefScientificClient;
use App\Services\Agriculture\OpenAlexScientificClient;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;

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

        $provenance = new ScientificSourceRecordProvenance(
            sourceKey: 'openalex',
            originalIdentifier: $openAlexId !== '' ? $openAlexId : null,
            originalIdentifierField: $openAlexId !== '' ? 'id' : null,
            recordUrl: $this->httpUrlOrNull($openAlexId),
            publishedAt: $this->stringOrNull($work['publication_date'] ?? null),
            identifierBasis: $openAlexId !== '' ? ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER : null,
            canonicalUrlBasis: match (true) {
                $landingPage !== '', $doiUrl !== '' => ScientificSourceRecordProvenance::URL_PROVIDER,
                $openAlexId !== '' => ScientificSourceRecordProvenance::URL_PROVIDER_RECORD,
                default => null,
            },
            transformations: $this->transformations([
                'title_tags_and_whitespace_stripped' => $title !== (string) ($work['display_name'] ?? $work['title'] ?? ''),
                'doi_resolver_prefix_removed' => $doiUrl !== '' && str_starts_with($doiUrl, 'https://doi.org/'),
                'doi_lowercased' => $doi !== null && $doi !== str_replace('https://doi.org/', '', $doiUrl),
                'abstract_reconstructed_from_inverted_index' => $abstract !== '',
            ]),
        );

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
            provenance: [$provenance],
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

        $rawDoi = trim((string) ($work['DOI'] ?? ''));
        $rawTitle = $work['title'] ?? null;
        $rawTitle = (string) (is_array($rawTitle) ? ($rawTitle[0] ?? '') : $rawTitle);
        $rawAbstract = trim(strip_tags((string) ($work['abstract'] ?? '')));
        $provenance = new ScientificSourceRecordProvenance(
            sourceKey: 'crossref',
            originalIdentifier: $rawDoi !== '' ? $rawDoi : null,
            originalIdentifierField: $rawDoi !== '' ? 'DOI' : null,
            recordUrl: $this->httpUrlOrNull($work['URL'] ?? null),
            publishedAt: $this->crossRefIssuedDate($work),
            identifierBasis: match (true) {
                $doi !== null => ScientificSourceRecordProvenance::IDENTIFIER_DOI,
                $url !== null => ScientificSourceRecordProvenance::IDENTIFIER_URL,
                default => null,
            },
            canonicalUrlBasis: match (true) {
                $doiUrl !== '' => ScientificSourceRecordProvenance::URL_DOI_RESOLVER,
                $url !== null => ScientificSourceRecordProvenance::URL_PROVIDER,
                default => null,
            },
            transformations: $this->transformations([
                'title_tags_and_whitespace_stripped' => $title !== $rawTitle,
                'doi_lowercased' => $doi !== null && $doi !== $rawDoi,
                'abstract_tags_stripped' => $rawAbstract !== '' && $rawAbstract !== (string) $work['abstract'],
                'abstract_taken_from_subtitle' => $rawAbstract === '' && $abstract !== '',
            ]),
        );

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
            provenance: [$provenance],
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

        $rawDoi = trim((string) ($work['doi'] ?? ''));
        $provenance = new ScientificSourceRecordProvenance(
            sourceKey: 'consensus',
            recordUrl: $this->httpUrlOrNull($url),
            identifierBasis: match (true) {
                $doi !== null => ScientificSourceRecordProvenance::IDENTIFIER_DOI,
                $url !== '' => ScientificSourceRecordProvenance::IDENTIFIER_URL,
                default => ScientificSourceRecordProvenance::IDENTIFIER_DERIVED_HASH,
            },
            canonicalUrlBasis: match (true) {
                $url !== '' => ScientificSourceRecordProvenance::URL_PROVIDER,
                $doi !== null => ScientificSourceRecordProvenance::URL_DOI_RESOLVER,
                default => null,
            },
            transformations: $this->transformations([
                'title_tags_and_whitespace_stripped' => $title !== (string) ($work['title'] ?? ''),
                'doi_lowercased' => $doi !== null && $doi !== $rawDoi,
                'abstract_tags_and_whitespace_stripped' => $abstract !== '' && $abstract !== (string) ($work['abstract'] ?? ''),
            ]),
        );

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
            provenance: [$provenance],
        );
    }

    /**
     * Normalize a Semantic Scholar paper hit into the Stage 3 result contract.
     * Missing DOI/abstract stay null — never fabricate. citationCount is metadata only.
     *
     * @param  array<string, mixed>  $work
     */
    public function fromSemanticScholarWork(array $work): ?ScientificSearchResult
    {
        $title = trim(strip_tags((string) ($work['title'] ?? '')));
        if ($title === '') {
            return null;
        }

        $externalIds = is_array($work['externalIds'] ?? null) ? $work['externalIds'] : [];
        $doi = $this->normalizeDoi(trim((string) ($externalIds['DOI'] ?? $externalIds['doi'] ?? '')) ?: null);
        $url = trim((string) ($work['url'] ?? ''));
        $openAccessPdf = is_array($work['openAccessPdf'] ?? null) ? $work['openAccessPdf'] : [];
        $pdfUrl = trim((string) ($openAccessPdf['url'] ?? ''));
        $canonicalUrl = $url !== ''
            ? $url
            : ($doi !== null ? 'https://doi.org/'.$doi : ($pdfUrl !== '' ? $pdfUrl : null));

        $abstract = trim(strip_tags((string) ($work['abstract'] ?? '')));
        $journal = trim((string) ($work['venue'] ?? ''));
        $year = is_numeric($work['year'] ?? null) ? (int) $work['year'] : null;
        $paperId = trim((string) ($work['paperId'] ?? ''));

        $sourceIdentifier = $doi
            ?? ($paperId !== '' ? $paperId : null)
            ?? ($url !== '' ? $url : null)
            ?? ('semantic_scholar:'.md5(mb_strtolower($title).'|'.($year ?? 'unknown')));

        $authors = [];
        $rawAuthors = is_array($work['authors'] ?? null) ? $work['authors'] : [];
        foreach ($rawAuthors as $author) {
            $name = trim(is_array($author) ? (string) ($author['name'] ?? '') : (string) $author);
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        $relevanceMetadata = [];
        if (isset($work['citationCount']) && is_numeric($work['citationCount'])) {
            $relevanceMetadata['citation_count'] = (int) $work['citationCount'];
        }
        if (is_array($work['publicationTypes'] ?? null) && $work['publicationTypes'] !== []) {
            $relevanceMetadata['publication_types'] = array_values(array_filter(
                array_map(static fn ($t): string => trim((string) $t), $work['publicationTypes']),
                static fn (string $t): bool => $t !== '',
            ));
        }
        if ($pdfUrl !== '') {
            $relevanceMetadata['open_access_pdf_url'] = $pdfUrl;
        }
        if ($externalIds !== []) {
            $relevanceMetadata['external_ids'] = $externalIds;
        }

        $rawDoi = trim((string) ($externalIds['DOI'] ?? $externalIds['doi'] ?? ''));
        $provenance = new ScientificSourceRecordProvenance(
            sourceKey: 'semantic_scholar',
            originalIdentifier: $paperId !== '' ? $paperId : null,
            originalIdentifierField: $paperId !== '' ? 'paperId' : null,
            recordUrl: $this->httpUrlOrNull($url),
            publishedAt: $this->stringOrNull($work['publicationDate'] ?? null),
            identifierBasis: match (true) {
                $doi !== null => ScientificSourceRecordProvenance::IDENTIFIER_DOI,
                $paperId !== '' => ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER,
                $url !== '' => ScientificSourceRecordProvenance::IDENTIFIER_URL,
                default => ScientificSourceRecordProvenance::IDENTIFIER_DERIVED_HASH,
            },
            canonicalUrlBasis: match (true) {
                $url !== '' => ScientificSourceRecordProvenance::URL_PROVIDER,
                $doi !== null => ScientificSourceRecordProvenance::URL_DOI_RESOLVER,
                $pdfUrl !== '' => ScientificSourceRecordProvenance::URL_OPEN_ACCESS_PDF,
                default => null,
            },
            transformations: $this->transformations([
                'title_tags_and_whitespace_stripped' => $title !== (string) ($work['title'] ?? ''),
                'doi_lowercased' => $doi !== null && $doi !== $rawDoi,
                'abstract_tags_and_whitespace_stripped' => $abstract !== '' && $abstract !== (string) ($work['abstract'] ?? ''),
            ]),
        );

        return new ScientificSearchResult(
            sourceKey: 'semantic_scholar',
            sourceIdentifier: $sourceIdentifier,
            title: $title,
            authors: array_values(array_unique($authors)),
            publicationYear: $year,
            doi: $doi,
            canonicalUrl: $canonicalUrl,
            abstract: $abstract !== '' ? $abstract : null,
            journal: $journal !== '' ? $journal : null,
            foundBySources: ['semantic_scholar'],
            relevanceMetadata: $relevanceMetadata !== [] ? $relevanceMetadata : null,
            rawMetadata: ['semantic_scholar' => $work],
            provenance: [$provenance],
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

    /**
     * @param  array<string, bool>  $applied
     * @return list<string>
     */
    private function transformations(array $applied): array
    {
        return array_keys(array_filter($applied));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function httpUrlOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return $value !== null && preg_match('#^https?://#i', $value) === 1 ? $value : null;
    }

    /**
     * The issued date exactly as Crossref's date-parts give it, at the precision given.
     *
     * @param  array<string, mixed>  $work
     */
    private function crossRefIssuedDate(array $work): ?string
    {
        $parts = $work['issued']['date-parts'][0] ?? null;
        if (! is_array($parts) || ! is_numeric($parts[0] ?? null)) {
            return null;
        }
        $date = sprintf('%04d', (int) $parts[0]);
        foreach ([1, 2] as $index) {
            if (! is_numeric($parts[$index] ?? null)) {
                break;
            }
            $date .= sprintf('-%02d', (int) $parts[$index]);
        }

        return $date;
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
