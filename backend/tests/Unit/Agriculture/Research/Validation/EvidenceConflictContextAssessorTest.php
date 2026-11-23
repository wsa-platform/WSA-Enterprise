<?php

namespace Tests\Unit\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Validation\EvidenceConflictAssessmentOutcome;
use App\Services\Agriculture\Research\Validation\EvidenceConflictContextAssessor;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

class EvidenceConflictContextAssessorTest extends TestCase
{
    public function test_differing_irrigation_methods_resolve_by_context(): void
    {
        $assessor = new EvidenceConflictContextAssessor;
        $a = $this->item('e1', conditions: ['irrigation_method' => 'drip']);
        $b = $this->item('e2', conditions: ['irrigation_method' => 'sprinkler']);

        $result = $assessor->assessPair($a, $b);

        $this->assertSame(EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT, $result['outcome']);
        $this->assertContains('irrigation_method', $result['differing_keys']);
    }

    public function test_matching_conditions_are_genuine_conflict_candidates(): void
    {
        $assessor = new EvidenceConflictContextAssessor;
        $a = $this->item('e1', conditions: [
            'irrigation_method' => 'drip',
            'location' => 'egypt',
        ]);
        $b = $this->item('e2', conditions: [
            'irrigation_method' => 'drip',
            'location' => 'egypt',
        ]);

        $result = $assessor->assessPair($a, $b);

        $this->assertSame(EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT, $result['outcome']);
        $this->assertTrue($result['comparable']);
    }

    public function test_missing_conditions_are_insufficient_information(): void
    {
        $assessor = new EvidenceConflictContextAssessor;
        $a = $this->item('e1', text: 'Irrigation improves yield.');
        $b = $this->item('e2', text: 'Irrigation reduces yield.');

        $result = $assessor->assessPair($a, $b);

        $this->assertSame(EvidenceConflictAssessmentOutcome::INSUFFICIENT_INFORMATION, $result['outcome']);
    }

    public function test_different_entities_are_not_comparable(): void
    {
        $assessor = new EvidenceConflictContextAssessor;
        $a = $this->item('e1', entity: 'wheat', conditions: ['irrigation_method' => 'drip']);
        $b = $this->item('e2', entity: 'tomato', conditions: ['irrigation_method' => 'drip']);

        $result = $assessor->assessPair($a, $b);

        $this->assertSame(EvidenceConflictAssessmentOutcome::NOT_COMPARABLE, $result['outcome']);
    }

    public function test_text_hints_can_resolve_context_without_inventing_numbers(): void
    {
        $assessor = new EvidenceConflictContextAssessor;
        $a = $this->item('e1', text: 'Deficit irrigation increased wheat yield under arid conditions.');
        $b = $this->item('e2', text: 'Flood irrigation decreased wheat yield in humid climates.');

        $result = $assessor->assessPair($a, $b);

        $this->assertSame(EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT, $result['outcome']);
    }

    /**
     * @param  array<string, mixed>|null  $conditions
     */
    private function item(
        string $id,
        ?array $conditions = null,
        string $text = 'Evidence text',
        ?string $entity = null,
    ): ScientificEvidenceItem {
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
            claimRelationship: 'supported',
            confidence: 0.8,
            qualityScore: 0.8,
            qualityFactors: [],
            sourceAttribution: [],
            hasConflict: false,
            conditions: $conditions,
            cropOrEntity: $entity,
        );
    }
}
