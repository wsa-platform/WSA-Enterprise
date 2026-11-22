<?php

namespace Tests\Unit\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\EvidenceIndependenceAssessor;
use PHPUnit\Framework\TestCase;

final class EvidenceIndependenceAssessorTest extends TestCase
{
    public function test_same_doi_from_two_providers_is_one_independent_document(): void
    {
        $assessor = new EvidenceIndependenceAssessor();
        $citations = [
            new ResearchAnswerCitation(
                citationId: 'c1',
                sourceId: 'W1',
                evidenceId: 'ev-1',
                title: 'Shared paper',
                authors: ['A'],
                organization: null,
                journal: null,
                doi: '10.1000/same-doc',
                url: 'https://doi.org/10.1000/same-doc',
                publicationYear: 2020,
                sourceType: 'scholarly',
                sourceKey: 'openalex',
                contributingProviders: ['openalex', 'crossref'],
            ),
        ];

        $report = $assessor->assessCitations($citations);

        $this->assertSame(2, $report['independent_provider_count']);
        $this->assertSame(1, $report['independent_document_count']);
        $this->assertSame(1, $report['same_document_multi_provider_count']);
    }

    public function test_two_distinct_dois_are_two_independent_documents(): void
    {
        $assessor = new EvidenceIndependenceAssessor();
        $citations = [
            $this->cite('ev-a', 'openalex', '10.1000/a', ['openalex']),
            $this->cite('ev-b', 'crossref', '10.1000/b', ['crossref']),
        ];

        $report = $assessor->assessCitations($citations);

        $this->assertSame(2, $report['independent_provider_count']);
        $this->assertSame(2, $report['independent_document_count']);
        $this->assertSame(0, $report['same_document_multi_provider_count']);
    }

    public function test_invalid_doi_does_not_merge_documents(): void
    {
        $assessor = new EvidenceIndependenceAssessor();
        $this->assertNull($assessor->documentIdentityKey('not-a-doi', null, 'openalex', 'x'));
        $this->assertNull($assessor->documentIdentityKey(null, null, null, null));
    }

    public function test_missing_doi_falls_back_to_url_or_source_identifier(): void
    {
        $assessor = new EvidenceIndependenceAssessor();
        $this->assertSame(
            'url:https://example.test/paper',
            $assessor->documentIdentityKey(null, 'https://example.test/paper/', 'openalex', null),
        );
        $this->assertSame(
            'id:gbif:2705099',
            $assessor->documentIdentityKey(null, null, 'gbif', '2705099'),
        );
    }

    /**
     * @param  list<string>  $providers
     */
    private function cite(string $evidenceId, string $sourceKey, string $doi, array $providers): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'cite-'.$evidenceId,
            sourceId: $sourceKey.'-id',
            evidenceId: $evidenceId,
            title: 'Title '.$evidenceId,
            authors: ['A'],
            organization: null,
            journal: null,
            doi: $doi,
            url: 'https://doi.org/'.$doi,
            publicationYear: 2021,
            sourceType: 'scholarly',
            sourceKey: $sourceKey,
            contributingProviders: $providers,
        );
    }
}
