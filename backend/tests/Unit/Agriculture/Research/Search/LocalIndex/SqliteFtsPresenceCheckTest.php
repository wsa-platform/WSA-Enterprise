<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use Tests\TestCase;

/**
 * Mission 75193: Stage-3 must not full-scan FTS for presence; in-memory engine mirrors the contract.
 */
final class SqliteFtsPresenceCheckTest extends TestCase
{
    public function test_has_indexed_documents_matches_document_count_presence(): void
    {
        $index = new LocalScientificIndex;

        $this->assertFalse($index->hasIndexedDocuments('dr_duke'));

        // LocalScientificIndex is populated via rebuild in lifecycle tests; here we only
        // assert the presence helper stays consistent with documentCount for empty sources.
        $this->assertSame($index->documentCount('dr_duke') > 0, $index->hasIndexedDocuments('dr_duke'));
    }
}
