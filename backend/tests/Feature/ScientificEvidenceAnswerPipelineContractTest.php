<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimEvidenceMapper;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Tests\TestCase;

/**
 * Isolated G-EVD contract: extractor, mapper aggregate, synthesis conflict eligibility.
 * Relevance, accuracy, composer, confidence, and presentation methods remain outside this boundary.
 */
class ScientificEvidenceAnswerPipelineContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    /**
     * @return list<array{0: string, 1: list<string>, 2: string, 3: list<string>}>
     */
    public static function requestedInformationCases(): array
    {
        return [
            'home_intent_meta_keeps_temperature' => [
                'environmental_requirements',
                ['environmental_requirements', 'evidence_backed_guidance', 'temperature', 'optimal_value_or_range'],
                'wheat',
                ['temperature'],
            ],
            'crop_intent_meta_collapses_to_single_claim' => [
                'cultivation',
                ['cultivation', 'verified_evidence'],
                'rice',
                [],
            ],
            'genuine_multi_property_kept' => [
                'statistical_lookup',
                ['quantity', 'area'],
                'barley',
                ['quantity', 'area'],
            ],
            'french_surface_intent_keeps_scientific_property' => [
                'environmental_requirements',
                ['environmental_requirements', 'evidence_backed_guidance', 'temperature'],
                'wheat',
                ['temperature'],
            ],
        ];
    }

    /**
     * @dataProvider requestedInformationCases
     *
     * @param  list<string>  $requested
     * @param  list<string>  $expected
     */
    public function test_extractor_keeps_scientific_properties_not_pipeline_meta(
        string $intent,
        array $requested,
        string $cropId,
        array $expected,
    ): void {
        $plan = $this->pipelinePlan(
            question: $cropId.' scientific request',
            requested: $requested,
            cropId: $cropId,
            intent: $intent,
        );
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $properties = array_values(array_filter(array_map(
            static fn ($claim): string => strtolower(trim((string) ($claim->property ?? ''))),
            $claims,
        )));

        if ($expected === []) {
            $this->assertCount(1, $claims);
            $this->assertSame('qc-1', $claims[0]->claimId);

            return;
        }

        $this->assertSame($expected, $properties);
    }

    public function test_mapper_does_not_collapse_conflict_axis_when_supported_sibling_exists(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $mapper = new QuestionClaimEvidenceMapper;
        $bindings = $mapper->bind($claims, [
            $this->phase5Evidence('e-s', ClaimEvidenceRelationship::SUPPORTED),
            $this->phase5Evidence('e-c', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
        ]);

        $this->assertSame(
            ClaimEvidenceRelationship::CONFLICTING,
            $mapper->aggregateRelationshipForClaim('qc-1', $bindings),
        );
    }

    public function test_mixed_conflict_keeps_conflict_axis_but_remains_answer_eligible(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $supported = $this->withText(
            $this->phase5Evidence('e-s', ClaimEvidenceRelationship::SUPPORTED),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        );
        $conflict = $this->withText(
            $this->phase5Evidence('e-c', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation([$supported, $conflict], true),
            [$supported, $conflict],
        );

        $this->assertSame(
            ClaimEvidenceRelationship::CONFLICTING,
            $matrix['answer_statement_traces'][0]['aggregate_claim_relationship'],
        );
        $this->assertTrue($matrix['answer_statement_traces'][0]['answer_eligible']);
    }

    public function test_pure_conflict_remains_ineligible(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $conflict = $this->withText(
            $this->phase5Evidence('e-c', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
            'Wheat production quantity was 9 million tonnes according to source A.',
        );
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation([$conflict], false),
            [$conflict],
        );

        $this->assertFalse($matrix['answer_statement_traces'][0]['answer_eligible']);
        $this->assertSame(
            ClaimEvidenceRelationship::CONFLICTING,
            $matrix['answer_statement_traces'][0]['aggregate_claim_relationship'],
        );
    }

    /**
     * @param  list<string>  $requested
     */
    private function pipelinePlan(
        string $question,
        array $requested,
        string $cropId,
        string $intent,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropId],
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: $intent,
            subtopic: null,
            requestedInformation: $requested,
            constraints: ['answer_language' => 'en'],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: [$intent],
            subtopics: [],
            requestedInformation: $requested,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: ['selected_crop_id' => $cropId],
            readyForStage3: true,
        );
    }

    private function withText(mixed $item, string $text): mixed
    {
        $ref = new \ReflectionClass($item);
        $clone = $ref->newInstance(
            $item->evidenceId,
            $item->sourceId,
            $item->sourceKey,
            $item->sourceType,
            $item->publicationTitle,
            $item->authors,
            $item->institution,
            $item->journal,
            $item->doi,
            $item->url,
            $item->publicationYear,
            $item->retrievedAt,
            $item->agriculturalDomain,
            $item->claimTopic,
            $text,
            $item->validationStatus,
            $item->validationFailures,
            $item->claimRelationship,
            $item->confidence,
            $item->qualityScore,
            $item->qualityFactors,
            $item->sourceAttribution,
            $item->hasConflict,
            $item->conditions,
            $item->cropOrEntity,
        );

        return $clone;
    }
}
