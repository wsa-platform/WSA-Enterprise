<?php

namespace Tests\Unit\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceConflictAssessmentOutcome;
use App\Services\Agriculture\Research\Validation\EvidenceConflictDetector;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

class EvidenceConflictDetectorContextTest extends TestCase
{
    public function test_opposing_polarity_with_different_conditions_is_not_genuine_conflict(): void
    {
        $detector = new EvidenceConflictDetector;
        $a = $this->item(
            'e1',
            'Deficit irrigation improve increase enhance wheat yield under arid conditions.',
            ['irrigation_method' => 'deficit', 'climate' => 'arid'],
        );
        $b = $this->item(
            'e2',
            'Flood irrigation reduce decrease harm wheat yield under humid conditions.',
            ['irrigation_method' => 'flood', 'climate' => 'humid'],
        );

        $out = $detector->detect([$a, $b]);

        $this->assertCount(2, $out);
        foreach ($out as $item) {
            $this->assertFalse($item->hasConflict);
            $this->assertNotSame(ClaimEvidenceRelationship::CONFLICTING, $item->claimRelationship);
            $this->assertSame(
                EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                $item->qualityFactors['conflict_assessment'] ?? null,
            );
            $this->assertTrue(($item->qualityFactors['contextual_difference'] ?? false) === true);
        }
    }

    public function test_opposing_polarity_with_same_conditions_is_genuine_conflict(): void
    {
        $detector = new EvidenceConflictDetector;
        $conditions = ['irrigation_method' => 'drip', 'location' => 'egypt'];
        $a = $this->item(
            'e1',
            'Drip irrigation improve increase enhance wheat yield in Egypt field trials.',
            $conditions,
        );
        $b = $this->item(
            'e2',
            'Drip irrigation reduce decrease harm wheat yield in Egypt field trials.',
            $conditions,
        );

        $out = $detector->detect([$a, $b]);

        foreach ($out as $item) {
            $this->assertTrue($item->hasConflict);
            $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $item->claimRelationship);
            $this->assertSame(
                EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT,
                $item->qualityFactors['conflict_assessment'] ?? null,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function item(string $id, string $text, array $conditions): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'scholarly',
            publicationTitle: 'Title '.$id,
            authors: ['Author'],
            institution: null,
            journal: null,
            doi: null,
            url: null,
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'irrigation',
            claimTopic: 'irrigation yield',
            evidenceText: $text,
            validationStatus: 'evidence_usable',
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.85,
            qualityScore: 0.85,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            hasConflict: false,
            conditions: $conditions,
            cropOrEntity: 'wheat',
        );
    }
}
