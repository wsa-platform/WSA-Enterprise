<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Search\ScientificKnowledgeType;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use PHPUnit\Framework\TestCase;

/**
 * Workstream C: GBIF structured taxonomy must not be paper-shaped-only.
 */
class GbifStructuredEvidenceContractTest extends TestCase
{
    public function test_with_relevance_metadata_preserves_structured_taxonomy_contract(): void
    {
        $base = new ScientificSearchResult(
            sourceKey: 'gbif',
            sourceIdentifier: '123',
            title: 'Mentha piperita L.',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://www.gbif.org/species/123',
            abstract: 'Taxonomic classification. kingdom: Plantae; family: Lamiaceae',
            journal: null,
            foundBySources: ['gbif'],
        );

        $stamped = $base->withRelevanceMetadata([
            'knowledge_type' => ScientificKnowledgeType::TAXONOMIC_IDENTITY,
            'evidence_modality' => 'structured_taxonomic',
            'evidence_shape' => 'structured_taxonomy_hierarchy',
            'structured_taxonomic_fields' => [
                'family' => 'Lamiaceae',
                'genus' => 'Mentha',
                'scientificName' => 'Mentha piperita L.',
            ],
        ]);

        $this->assertNull($stamped->relevanceScore);
        $this->assertSame(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $stamped->relevanceMetadata['knowledge_type']);
        $this->assertSame('structured_taxonomy_hierarchy', $stamped->relevanceMetadata['evidence_shape']);
        $this->assertSame('Lamiaceae', $stamped->relevanceMetadata['structured_taxonomic_fields']['family']);
    }
}
