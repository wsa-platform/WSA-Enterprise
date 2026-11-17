<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Mockery;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * Shared C1→C3 bridge contract (EXIT-09 / EXIT-10).
 * PARTIAL evidence only — not SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class GovernedSourceC1C3EvidenceBridgeContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_bridge_preserves_source_identity_into_answer_composer_citations(): void
    {
        $result = new ScientificSearchResult(
            sourceKey: 'agris',
            sourceIdentifier: 'agris-contract-1',
            title: 'Wheat deficit irrigation trial',
            authors: ['Author A'],
            publicationYear: 2024,
            doi: '10.1000/agris-contract-1',
            canonicalUrl: 'https://example.org/agris/agris-contract-1',
            abstract: 'Measured yield under deficit irrigation for Triticum aestivum.',
            journal: 'Agronomy Journal',
            foundBySources: ['agris'],
            provenance: [
                new ScientificSourceRecordProvenance(
                    sourceKey: 'agris',
                    retrievedAt: '2026-10-18T17:00:00+00:00',
                    adapterClass: 'App\\Services\\Agriculture\\Research\\Search\\LocalIndex\\LocalIndexScientificSourceAdapter',
                    contractVersion: 'v2',
                    accessFamily: 'bulk_dataset',
                ),
            ],
        );

        $composer = ScientificEvidenceAnswerCitationBridge::makeComposer();
        $evidence = ScientificEvidenceAnswerCitationBridge::evidenceFromSearchResult(
            $result,
            claimTopic: 'irrigation',
            scientificIdentityPhrase: 'Triticum aestivum deficit irrigation yield',
        );

        $this->assertSame('agris', $evidence->sourceKey);
        $this->assertSame('agris-contract-1', $evidence->sourceId);
        $this->assertSame('agris', $evidence->sourceAttribution['provenance']['source_key'] ?? null);
        $this->assertSame('agris-contract-1', $evidence->sourceAttribution['provenance']['source_identifier'] ?? null);

        $citations = ScientificEvidenceAnswerCitationBridge::buildCitations($composer, [$evidence]);
        $this->assertCount(1, $citations);
        ScientificEvidenceAnswerCitationBridge::assertCitationPreservesSourceIdentity($citations[0], $result);
    }

    public function test_bridge_rejects_blank_source_identifier_before_composer(): void
    {
        $result = new ScientificSearchResult(
            sourceKey: 'gbif',
            sourceIdentifier: '   ',
            title: 'Invalid blank identifier',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: null,
            abstract: null,
            journal: null,
            foundBySources: ['gbif'],
        );

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        ScientificEvidenceAnswerCitationBridge::evidenceFromSearchResult(
            $result,
            claimTopic: 'taxonomy',
            scientificIdentityPhrase: 'Triticum aestivum',
        );
    }
}
