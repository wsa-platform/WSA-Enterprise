<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Synthesis\ClaimAttributionLedger;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use PHPUnit\Framework\TestCase;

/**
 * Workstream G: L7 requires Claim → Evidence → Source → Provider Record → Citation.
 */
class ClaimAttributionLedgerTest extends TestCase
{
    public function test_ledger_marks_l7_when_claim_evidence_source_and_citation_resolve(): void
    {
        $item = $this->evidence('ev-1', 'openalex', 'W123', '10.1000/test');
        $citation = new ResearchAnswerCitation(
            citationId: 'cite-ev-1',
            sourceId: 'W123',
            evidenceId: 'ev-1',
            title: 'Temperature effects on wheat',
            authors: ['A'],
            organization: null,
            journal: 'Journal',
            doi: '10.1000/test',
            url: 'https://example.test/paper',
            publicationYear: 2020,
            sourceType: 'scholarly',
            sourceKey: 'openalex',
        );
        $claim = new ResearchAnswerClaim(
            claimId: 'claim-J1',
            claimText: 'Temperature affects wheat growth rate.',
            evidenceIds: ['ev-1'],
            sourceIds: ['W123'],
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            sourceKeys: ['openalex'],
        );

        $ledger = new ClaimAttributionLedger();
        $rows = $ledger->build([$claim], [$item], [$citation]);

        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['l7_final_claim_attribution_proven']);
        $this->assertSame('openalex', $rows[0]['attributions'][0]['source_key']);
        $this->assertSame('10.1000/test', $rows[0]['attributions'][0]['provider_record_id']);
        $this->assertTrue($rows[0]['attributions'][0]['citation_resolves_to_evidence']);
        $this->assertSame('cite-ev-1', $rows[0]['attributions'][0]['citation_id']);
    }

    public function test_retrieved_but_unused_source_does_not_get_l7_on_empty_claim(): void
    {
        $item = $this->evidence('ev-2', 'gbif', 'gbif:123', null);
        $claim = new ResearchAnswerClaim(
            claimId: 'claim-J2',
            claimText: '',
            evidenceIds: [],
            sourceIds: [],
            validationStatus: 'insufficient_evidence',
            claimRelationship: ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            confidence: 0.0,
        );

        $rows = (new ClaimAttributionLedger())->build([$claim], [$item], []);

        $this->assertFalse($rows[0]['l7_final_claim_attribution_proven']);
        $this->assertSame([], $rows[0]['attributions']);
    }

    public function test_source_keys_from_evidence_ids(): void
    {
        $items = [
            $this->evidence('a', 'openalex', '1', null),
            $this->evidence('b', 'crossref', '2', null),
            $this->evidence('c', 'openalex', '3', null),
        ];
        $keys = (new ClaimAttributionLedger())->sourceKeysFromEvidenceIds(['a', 'b', 'c'], $items);

        $this->assertSame(['openalex', 'crossref'], $keys);
    }

    private function evidence(string $id, string $sourceKey, string $sourceId, ?string $doi): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: $sourceId,
            sourceKey: $sourceKey,
            sourceType: 'scholarly',
            publicationTitle: 'Title '.$id,
            authors: ['A'],
            institution: null,
            journal: null,
            doi: $doi,
            url: 'https://example.test/'.$id,
            publicationYear: 2020,
            retrievedAt: '2026-11-22T00:00:00Z',
            agriculturalDomain: 'crop_production',
            claimTopic: 'growth',
            evidenceText: 'Evidence body for '.$id,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 0.8,
            qualityFactors: ['evidence_directness' => 'DIRECT'],
            sourceAttribution: $doi !== null ? ['doi' => $doi] : ['provider_record_id' => $sourceId],
        );
    }
}
