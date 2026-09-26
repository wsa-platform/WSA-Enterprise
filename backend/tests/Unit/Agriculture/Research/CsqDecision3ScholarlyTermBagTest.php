<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Decision 3 #6/#7: scholarly bag + factor interleave from frozen CSQ.
 */
class CsqDecision3ScholarlyTermBagTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string, 2: string, 3: list<string>}>
     */
    public static function protectedFamilyMatrix(): array
    {
        return [
            [
                'water',
                'What are the water requirements of potatoes?',
                RetrievalSemanticContract::ROLE_IRRIGATION_WATER,
                RetrievalSpecification::SCHOLARLY_WATER_TERMS,
            ],
            [
                'temperature',
                'What is the suitable temperature for wheat seed germination?',
                RetrievalSemanticContract::ROLE_TEMPERATURE,
                RetrievalSpecification::SCHOLARLY_TEMPERATURE_TERMS,
            ],
            [
                'class',
                'What are the types of wheat?',
                RetrievalSemanticContract::ROLE_CLASSIFICATION,
                RetrievalSpecification::SCHOLARLY_CLASSIFICATION_TERMS,
            ],
            [
                'quantity',
                'What is the production quantity of wheat?',
                RetrievalSemanticContract::ROLE_QUANTITY,
                RetrievalSpecification::SCHOLARLY_QUANTITY_TERMS,
            ],
        ];
    }

    /**
     * @dataProvider protectedFamilyMatrix
     * @param  list<string>  $requiredBag
     */
    public function test_csq_spec_bag_matches_legacy_rsc_scholarly_bag(
        string $family,
        string $query,
        string $expectedRole,
        array $requiredBag,
    ): void {
        $qus = new QueryUnderstandingService();
        $planner = new ResearchPlanner($qus);
        $rsc = new RetrievalSemanticContract();
        $builder = new ScientificSearchQueryBuilder();

        $understood = $qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $family);
        $this->assertFalse(
            CanonicalScientificQuestion::isGenericProcessSurface((string) $csq->entity->surface)
            && $csq->entity->canonicalId === null,
            $family,
        );

        $newSpec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(RetrievalSpecification::SOURCE_CSQ, $newSpec->source, $family);
        $this->assertSame($expectedRole, $newSpec->scholarlyPropertyRole(), $family);
        $newBag = $newSpec->orderedScholarlyPropertyTerms();

        $legacyQuery = $understood->copyPreservingCanonical($understood->constraints, $understood->crop, $understood->cropId);
        $legacy = new AgriculturalKnowledgeQuery(
            originalQuestion: $legacyQuery->originalQuestion,
            normalizedQuestion: $legacyQuery->normalizedQuestion,
            language: $legacyQuery->language,
            agriculturalDomain: $legacyQuery->agriculturalDomain,
            subject: $legacyQuery->subject,
            crop: $legacyQuery->crop,
            cropId: $legacyQuery->cropId,
            scientificName: $legacyQuery->scientificName,
            topic: $legacyQuery->topic,
            subtopic: $legacyQuery->subtopic,
            requestedInformation: $legacyQuery->requestedInformation,
            constraints: $legacyQuery->constraints,
            location: $legacyQuery->location,
            researchRequired: $legacyQuery->researchRequired,
            ambiguityState: $legacyQuery->ambiguityState,
            clarificationRequirements: $legacyQuery->clarificationRequirements,
            researchIntent: $legacyQuery->researchIntent,
        );
        $this->assertNull($legacy->canonicalQuestion);
        $oldApplied = $rsc->apply(new KnowledgeQueryPlan(
            normalizedQuery: $legacy,
            researchIntent: $understood->researchIntent,
            agriculturalDomain: $understood->agriculturalDomain,
            subjectEntity: $understood->subject,
            topics: [$understood->topic],
            subtopics: [],
            requestedInformation: $understood->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: $understood->ambiguityState,
            clarificationRequirements: $understood->clarificationRequirements,
            contextInput: [],
            readyForStage3: true,
        ));
        $oldBag = $oldApplied->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        $this->assertIsArray($oldBag, $family);

        foreach ($requiredBag as $term) {
            $this->assertContains($term, $newBag, $family.' new bag missing '.$term.' in '.implode('|', $newBag));
            $this->assertContains($term, $oldBag, $family.' old bag missing '.$term.' in '.implode('|', is_array($oldBag) ? $oldBag : []));
        }

        if (in_array($expectedRole, [
            RetrievalSemanticContract::ROLE_IRRIGATION_WATER,
            RetrievalSemanticContract::ROLE_TEMPERATURE,
            RetrievalSemanticContract::ROLE_CLASSIFICATION,
            RetrievalSemanticContract::ROLE_QUANTITY,
        ], true)) {
            foreach (RetrievalSpecification::PRODUCTIVITY_FALLBACK_TERMS as $forbidden) {
                $this->assertNotContains($forbidden, $newBag, $family.' new bag leaked '.$forbidden);
                $this->assertNotContains($forbidden, $oldBag, $family.' old bag leaked '.$forbidden);
            }
        }

        $plan = $planner->planKnowledgeQuery(['query' => $query]);
        $applied = $rsc->apply($plan);
        $this->assertSame($plan->normalizedQuery->canonicalQuestion, $applied->normalizedQuery->canonicalQuestion, $family);
        $variantBlob = mb_strtolower(implode(' ', $builder->buildVariantsFromPlan($applied)));
        foreach ($requiredBag as $term) {
            $this->assertStringContainsString($term, $variantBlob, $family.' variants missing '.$term.' in '.$variantBlob);
        }
    }

    public function test_temperature_family_key_is_normalized(): void
    {
        $csq = (new QueryUnderstandingService())->understand([
            'query' => 'What is the suitable temperature for wheat seed germination?',
        ])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('temperature', $csq->property->key);
        $this->assertNotSame('suitable temperature', $csq->property->key);
        $this->assertNotNull($csq->property->surface);
    }

    public function test_factor_interleave_is_derived_from_csq_process(): void
    {
        $csq = (new QueryUnderstandingService())->understand([
            'query' => 'تأثير الري على إنتاج القمح',
        ])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->process->surface);
        $spec = RetrievalSpecification::fromCanonical($csq);
        $factors = $spec->orderedFactors();
        $this->assertSame([$csq->process->surface], $factors);
        $ordered = $spec->orderedScholarlyPropertyTerms();
        $this->assertNotSame([], $ordered);
        $this->assertContains($csq->process->surface, $ordered);
        if (count($ordered) > 1) {
            $this->assertSame($csq->process->surface, $ordered[1], implode(' | ', $ordered));
        }
        $this->assertStringContainsString('interleaveRequiredFactors', file_get_contents(
            dirname(__DIR__, 4).'/app/Services/Agriculture/Research/RetrievalSpecification.php'
        ) ?: '');
    }

    public function test_legacy_absent_csq_still_materializes_water_bag(): void
    {
        $rsc = new RetrievalSemanticContract();
        $legacy = new AgriculturalKnowledgeQuery(
            originalQuestion: 'potato water requirement',
            normalizedQuestion: 'potato water requirement',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'potato', 'label' => 'potato'],
            crop: 'potato',
            cropId: 'potato',
            scientificName: null,
            topic: 'water requirement',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'requested_property_surface' => 'water requirement',
                'requested_property_key' => 'quantity',
                'scientific_sense' => 'crop_water_requirement',
                'requested_property_query_terms' => ['yield', 'production', 'quantity'],
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );
        $this->assertNull($legacy->canonicalQuestion);
        $plan = new KnowledgeQueryPlan(
            normalizedQuery: $legacy,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: $legacy->subject,
            topics: ['water requirement'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
        $applied = $rsc->apply($plan);
        $terms = $applied->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        $this->assertIsArray($terms);
        foreach (RetrievalSpecification::SCHOLARLY_WATER_TERMS as $term) {
            $this->assertContains($term, $terms, implode('|', $terms));
        }
        $this->assertNotContains('yield', $terms);
    }
}
