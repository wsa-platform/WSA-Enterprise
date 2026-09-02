<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\CrossRefScientificClient;
use App\Services\Agriculture\OpenAlexScientificClient;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Maps Stage 3 search results to the legacy source contract used by ScientificSourceValidator.
 * Never fabricates metadata — only derives from normalized search results and raw adapter payloads.
 */
class ScientificSearchResultSourceMapper
{
    public function __construct(
        private OpenAlexScientificClient $openAlexClient,
        private CrossRefScientificClient $crossRefClient,
    ) {}

    /**
     * @return array{organization: string, title: string, year: int|null, url: string|null, doi: string|null, source_type: string, source_key: string, source_identifier: string|null}
     */
    public function map(ScientificSearchResult $result): array
    {
        $raw = $result->rawMetadata ?? [];

        if ($result->sourceKey === 'openalex' && is_array($raw['openalex'] ?? null)) {
            $mapped = $this->openAlexClient->buildSourceFromWork($raw['openalex']);
        } elseif ($result->sourceKey === 'crossref' && is_array($raw['crossref'] ?? null)) {
            $mapped = $this->crossRefClient->buildSourceFromWork($raw['crossref']);
        } else {
            $mapped = $this->fallbackFromNormalizedResult($result);
        }

        return array_merge($mapped, [
            'source_key' => $result->sourceKey,
            'source_identifier' => $result->sourceIdentifier,
        ]);
    }

    /**
     * @return array{organization: string, title: string, year: int|null, url: string|null, doi: string|null, source_type: string}
     */
    private function fallbackFromNormalizedResult(ScientificSearchResult $result): array
    {
        $organization = $result->journal ?? '';
        if ($organization === '' && $result->authors !== []) {
            $organization = $result->authors[0];
        }

        $sourceType = $this->inferSourceType($organization, $result->journal);

        return [
            'organization' => $organization !== '' ? $organization : 'Unknown source',
            'title' => $result->title,
            'year' => $result->publicationYear,
            'url' => $result->canonicalUrl,
            'doi' => $result->doi,
            'source_type' => $sourceType,
        ];
    }

    private function inferSourceType(string $organization, ?string $journal): string
    {
        $haystack = strtoupper(trim($organization.' '.($journal ?? '')));

        foreach ([
            'FAO' => 'international_organization',
            'IPPC' => 'international_organization',
            'CGIAR' => 'international_organization',
            'USDA' => 'government',
            'MINISTRY' => 'government',
            'UNIVERSITY' => 'university_research',
            'COLLEGE' => 'university_research',
        ] as $needle => $type) {
            if (str_contains($haystack, $needle)) {
                return $type;
            }
        }

        if ($journal !== null && trim($journal) !== '') {
            return 'peer_reviewed_journal';
        }

        return 'supporting_verified';
    }
}
