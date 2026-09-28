<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Foreign-binomial detector: catalog-backed different species only.
 * Unknown Latin pairs fail closed. Same-genus epithets stay on the existing pipeline.
 */
class ForeignBinomialSpeciesRelationContractTest extends TestCase
{
    private ScientificEvidenceRelevanceGate $gate;

    private ReflectionMethod $foreignBinomial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new ScientificEvidenceRelevanceGate(new AnswerExpressionAccuracyGate);
        $this->foreignBinomial = new ReflectionMethod(
            ScientificEvidenceRelevanceGate::class,
            'mentionsForeignBinomial',
        );
        $this->foreignBinomial->setAccessible(true);
    }

    public function test_target_binomial_is_not_foreign(): void
    {
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');
        $hay = 'Ipomoea batatas seed germination temperature was measured under controlled conditions.';

        $this->assertFalse($this->isForeign($plan, $hay, 'ipomoea'));
        $this->assertSame('exact_species', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_different_catalogued_genus_binomial_is_foreign(): void
    {
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');
        $hay = 'Potato (Solanum tuberosum) seed germination temperature was measured under controlled conditions.';

        $this->assertTrue($this->isForeign($plan, $hay, 'ipomoea'));
        $this->assertSame('different_species', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_different_crop_binomial_resolves_foreign_through_catalog(): void
    {
        $plan = $this->cropPlan('potato', 'Solanum tuberosum');
        $hay = 'Sweet potato (Ipomoea batatas) seed germination temperature requirements were measured.';

        $this->assertTrue($this->isForeign($plan, $hay, 'solanum'));
        $assessment = $this->gate->assess($plan, $hay, $hay);
        $this->assertSame('different_species', $assessment['species_relation']);
    }

    public function test_unknown_latin_binomial_is_not_foreign(): void
    {
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');
        $hay = 'Adjacent woodland plots of Quercus robur were scored independently of the crop trial.';

        $this->assertFalse($this->isForeign($plan, $hay, 'ipomoea'));
        $this->assertNotSame('different_species', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_same_genus_different_epithet_stays_genus_only(): void
    {
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');
        $hay = 'Cardinal temperatures of Ipomoea nil seed germination were determined under controlled light.';

        $this->assertFalse($this->isForeign($plan, $hay, 'ipomoea'));
        $this->assertSame('genus_only', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_coincidental_epithet_does_not_drive_classification(): void
    {
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');
        $hay = 'Oryza batatas nomenclature was listed only as a bibliographic label in the appendix.';

        $this->assertTrue($this->isForeign($plan, $hay, 'ipomoea'));
        $this->assertSame('different_species', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_latin_binomial_is_language_neutral_in_surrounding_prose(): void
    {
        $plan = $this->cropPlan('potato', 'Solanum tuberosum');
        $french = 'La germination de Ipomoea batatas a été mesurée en chambre contrôlée.';
        $arabic = 'تم قياس إنبات Ipomoea batatas تحت ظروف متحكم بها.';

        $this->assertTrue($this->isForeign($plan, $french, 'solanum'));
        $this->assertTrue($this->isForeign($plan, $arabic, 'solanum'));
        $this->assertSame('different_species', $this->gate->assess($plan, $french, $french)['species_relation']);
        $this->assertSame('different_species', $this->gate->assess($plan, $arabic, $arabic)['species_relation']);
    }

    public function test_substring_without_binomial_structure_is_not_foreign(): void
    {
        $plan = $this->cropPlan('wheat', 'Triticum aestivum');
        $hay = 'Wheat growth without thermal regimes was scored in Triticaceae discussion only.';

        $this->assertFalse($this->isForeign($plan, $hay, 'triticum'));
        $this->assertNotSame('different_species', $this->gate->assess($plan, $hay, $hay)['species_relation']);
    }

    public function test_species_relation_vocabulary_is_closed(): void
    {
        $allowed = ['exact_species', 'genus_only', 'different_species', 'entity_less'];
        $plan = $this->cropPlan('sweet-potato', 'Ipomoea batatas');

        foreach ([
            'Ipomoea batatas seed germination was measured.',
            'Ipomoea nil seed germination was measured.',
            'Solanum tuberosum seed germination was measured.',
            'Quercus robur plots were scored separately.',
        ] as $hay) {
            $relation = $this->gate->assess($plan, $hay, $hay)['species_relation'];
            $this->assertContains($relation, $allowed, $hay);
        }

        $entityLess = $this->gate->assess(
            $this->entityLessPlan(),
            'Soil organic matter mineralization in agricultural soils.',
            'Nitrogen mineralization rates were measured without a crop binomial.',
        )['species_relation'];
        $this->assertSame('entity_less', $entityLess);
        $this->assertContains($entityLess, $allowed);
    }

    private function isForeign(KnowledgeQueryPlan $plan, string $haystack, string $targetGenus): bool
    {
        return (bool) $this->foreignBinomial->invoke($this->gate, $haystack, $targetGenus, $plan);
    }

    private function cropPlan(string $cropId, string $scientificName): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $cropId.' germination temperature',
            normalizedQuestion: $cropId.' germination temperature',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropId],
            crop: $cropId,
            cropId: $cropId,
            scientificName: $scientificName,
            topic: 'germination',
            subtopic: null,
            requestedInformation: ['temperature'],
            constraints: [
                'entity_dependent' => true,
                'scientific_sense' => 'seed_germination',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'environmental_requirements',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'environmental_requirements',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['germination'],
            subtopics: [],
            requestedInformation: ['temperature'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function entityLessPlan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'How does soil organic matter mineralization affect nitrogen availability?',
            normalizedQuestion: 'How does soil organic matter mineralization affect nitrogen availability?',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'soil', 'value' => 'soil'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'soil nitrogen',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'entity_dependent' => false,
                'question_type' => 'causes',
                'scientific_sense' => 'plant_nutrition',
                'scientific_factors' => ['nitrogen'],
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'plant_nutrition',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'plant_nutrition',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'soil', 'value' => 'soil'],
            topics: ['soil nitrogen'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
