<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 4 — R6 vocabulary: directness ≠ claim_relation ≠ disposition.
 */
class Phase4EvidenceVocabularyContractTest extends TestCase
{
    public function test_claim_relation_vocabulary_is_canonical_and_distinct(): void
    {
        $relations = ClaimEvidenceRelationship::all();
        $this->assertContains(ClaimEvidenceRelationship::SUPPORTED, $relations);
        $this->assertContains(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $relations);
        $this->assertContains(ClaimEvidenceRelationship::CONFLICTING, $relations);
        $this->assertContains(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $relations);
        $this->assertContains(ClaimEvidenceRelationship::NOT_VALIDATED, $relations);

        $directness = [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
        ];
        foreach ($directness as $label) {
            $this->assertNotContains(
                $label,
                $relations,
                'directness label must not appear in claim_relation vocabulary: '.$label,
            );
        }
    }

    public function test_disposition_vocabulary_is_independent_of_directness_and_claim_relation(): void
    {
        $disposition = [
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            HomeEvidenceLifecycleDisposition::RETRIEVED_BUT_REJECTED,
            HomeEvidenceLifecycleDisposition::VALIDATED_NOT_COMPOSER_ELIGIBLE,
            HomeEvidenceLifecycleDisposition::COMPOSER_USED,
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED,
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_FOR_SYNTHESIS,
            HomeEvidenceLifecycleDisposition::PROVIDER_FAILED,
            HomeEvidenceLifecycleDisposition::NEEDS_CLARIFICATION,
        ];

        foreach ($disposition as $label) {
            $this->assertNotContains($label, ClaimEvidenceRelationship::all());
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $label);
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $label);
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $label);
        }
    }

    public function test_legacy_directness_supported_alias_is_compatibility_not_canonical_supporting(): void
    {
        // Legacy alias shares the string "supported" with claim_relation SUPPORTED (historical).
        // Canonical directness value is SUPPORTING ("supporting"); EVL maps both to LABEL_SUPPORTED.
        $this->assertSame('supported', ScientificEvidenceDirectnessAssessor::SUPPORTED);
        $this->assertSame('supporting', ScientificEvidenceDirectnessAssessor::SUPPORTING);
        $this->assertSame('supported', ClaimEvidenceRelationship::SUPPORTED);
        $this->assertNotSame(
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        );

        $evl = app(EvidenceVerificationLayer::class);
        $this->assertSame(
            EvidenceVerificationLayer::LABEL_SUPPORTED,
            $evl->toVerificationLabel(ScientificEvidenceDirectnessAssessor::SUPPORTING),
        );
        $this->assertSame(
            EvidenceVerificationLayer::LABEL_SUPPORTED,
            $evl->toVerificationLabel(ScientificEvidenceDirectnessAssessor::SUPPORTED),
        );
    }

    public function test_evl_does_not_own_disposition_constants(): void
    {
        $constants = (new ReflectionClass(EvidenceVerificationLayer::class))->getConstants();
        foreach ($constants as $name => $value) {
            if (! is_string($value)) {
                continue;
            }
            $this->assertStringNotContainsString(
                'composer_used',
                $value,
                'EVL must not define disposition labels: '.$name,
            );
            $this->assertStringNotContainsString(
                'no_results_retrieved',
                $value,
                'EVL must not define disposition labels: '.$name,
            );
        }
    }
}
