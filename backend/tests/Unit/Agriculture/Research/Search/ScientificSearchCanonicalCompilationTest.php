<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * General compiler contract: CSQ/Spec identity must reach the provider query.
 * Crops in fixtures are catalog rows, not special-case patches.
 */
class ScientificSearchCanonicalCompilationTest extends TestCase
{
    private QueryUnderstandingService $qus;

    private ResearchPlanner $planner;

    private ScientificSearchQueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qus = new QueryUnderstandingService();
        $this->planner = new ResearchPlanner($this->qus);
        $this->builder = new ScientificSearchQueryBuilder();
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function homeIdentityQuestions(): array
    {
        return [
            ['What is the optimal temperature for rice growth?', 'rice', 'Oryza sativa'],
            ['ما هي احتياجات القمح من الماء؟', 'wheat', 'Triticum aestivum'],
            ['What soil is suitable for growing barley?', 'barley', 'Hordeum vulgare'],
            ['What is the average wheat yield per hectare?', 'wheat', 'Triticum aestivum'],
            ['ما هي الأرض المناسبة لزراعة دوار الشمس؟', 'sunflower', 'Helianthus annuus'],
            ['Quelle est la température optimale pour le blé?', 'wheat', 'Triticum aestivum'],
        ];
    }

    /**
     * Same catalog wheat identity, four linguistic surfaces.
     *
     * @return list<array{0: string}>
     */
    public static function multilingualWheatTemperatureQuestions(): array
    {
        return [
            ['ما درجة الحرارة المثلى للقمح؟'],
            ['What is the optimal temperature for wheat?'],
            ['Quelle est la température optimale pour le blé?'],
            ['Buğday için optimum sıcaklık nedir?'],
        ];
    }

    /**
     * @dataProvider homeIdentityQuestions
     */
    public function test_home_compiled_query_keeps_canonical_crop_identity(
        string $query,
        string $cropId,
        string $scientificName,
    ): void {
        $this->assertSame($scientificName, FieldCropTaxonomyCatalog::scientificNameFor($cropId));
        $blob = $this->compiledBlob(['query' => $query]);
        $this->assertStringContainsString(mb_strtolower($scientificName), $blob);
        $this->assertStringNotContainsString('scientific-research scientific literature', $blob);
        $this->assertFalse($this->blobHasProcessAsSoleEntity($blob, $cropId, $scientificName));
        $this->assertTrue($this->everyVariantKeepsResolvedIdentity($blob, $scientificName, $cropId));
    }

    /**
     * @dataProvider multilingualWheatTemperatureQuestions
     */
    public function test_multilingual_surfaces_compile_the_same_catalog_identity(string $query): void
    {
        $cropId = 'wheat';
        $scientificName = FieldCropTaxonomyCatalog::scientificNameFor($cropId);
        $this->assertNotSame('', $scientificName);
        $plan = $this->planner->planKnowledgeQuery(['query' => $query]);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame($cropId, $csq->entity->canonicalId ?? $csq->cropBinding->cropId);
        $blob = mb_strtolower(implode("\n", $this->builder->buildVariantsFromPlan($plan)));
        $this->assertStringContainsString(mb_strtolower($scientificName), $blob);
        $this->assertTrue($this->everyVariantKeepsResolvedIdentity($blob, $scientificName, $cropId));
        $this->assertStringContainsString('temperature', $blob);
        $this->assertDoesNotMatchRegularExpression('/^blé\b/u', trim(explode("\n", $blob)[0] ?? ''));
        $this->assertDoesNotMatchRegularExpression('/^buğday\b/u', trim(explode("\n", $blob)[0] ?? ''));
    }


    public function test_process_surface_does_not_replace_resolved_entity(): void
    {
        $query = 'ما هي الأرض المناسبة لزراعة دوار الشمس؟';
        $plan = $this->planner->planKnowledgeQuery(['query' => $query]);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('sunflower', $csq->entity->canonicalId);
        $this->assertSame('resolved', $csq->entity->resolution);
        $this->assertNotNull($csq->process->surface);
        $this->assertNotSame(
            mb_strtolower((string) $csq->entity->surface),
            mb_strtolower((string) $csq->process->surface),
        );
        $blob = $this->compiledBlob(['query' => $query]);
        $this->assertStringContainsString('helianthus annuus', $blob);
        $this->assertFalse($this->blobHasProcessAsSoleEntity($blob, 'sunflower', 'Helianthus annuus'));
        $this->assertTrue($this->everyVariantKeepsResolvedIdentity($blob, 'Helianthus annuus', 'sunflower'));
    }



    public function test_unresolved_process_token_does_not_invent_an_entity(): void
    {
        $plan = $this->planner->planKnowledgeQuery(['query' => 'ما هي درجة الحرارة المناسبة للنمو؟']);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertTrue(
            $csq->entity->normalized === null
            || $csq->entity->resolution !== 'resolved'
            || $csq->entity->canonicalId === null
        );
        $blob = mb_strtolower(implode("\n", $this->builder->buildVariantsFromPlan($plan)));
        $this->assertStringNotContainsString('gossypium', $blob);
        $this->assertStringNotContainsString('triticum', $blob);
    }


    public function test_comparative_operands_remain_in_compiled_variants(): void
    {
        $plan = $this->planner->planKnowledgeQuery([
            'query' => 'مقارنة إنتاج القمح والذرة',
        ]);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotEmpty($csq->relation->operands);
        $blob = implode("\n", $this->builder->buildVariantsFromPlan($plan));
        foreach ($csq->relation->operands as $operand) {
            $surface = trim((string) ($operand['surface'] ?? ''));
            if ($surface !== '') {
                $this->assertStringContainsString($surface, $blob);
            }
        }
    }

    /**
     * @return list<array{0: string, 1: list<string>}>
     */
    public static function recommendationConstraintQuestions(): array
    {
        return [
            ['ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟', ['saline', 'salinity', 'arid', 'desert']],
            ['What crops can be grown in arid regions with saline water?', ['saline', 'salinity', 'arid', 'desert']],
            ['Which crops are suitable for saline water in arid regions?', ['saline', 'salinity', 'arid', 'desert']],
            ['Quelles cultures conviennent aux sols salins ?', ['saline', 'salinity']],
            ['Hangi ürünler tuzlu topraklarda yetişebilir?', ['saline', 'salinity']],
            ["Quelles cultures peuvent être cultivées avec de l'eau salée ?", ['saline', 'salinity']],
            ['Tuzlu su ile hangi ürünler yetiştirilebilir?', ['saline', 'salinity']],
            ['What crops need little water?', ['water scarcity', 'limited water']],
            ['ما المحاصيل التي تحتاج إلى مياه قليلة؟', ['water scarcity', 'limited water']],
        ];
    }

    /**
     * @dataProvider recommendationConstraintQuestions
     * @param  list<string>  $needles
     */
    public function test_recommendation_compile_keeps_canonical_constraint_terms(
        string $query,
        array $needles,
    ): void {
        $plan = $this->planner->planKnowledgeQuery(['query' => $query]);
        $this->assertSame('recommendation', $plan->normalizedQuery->constraints['question_type'] ?? null, $query);
        $env = $plan->normalizedQuery->constraints['environmental_constraints'] ?? [];
        $this->assertNotEmpty($env, $query);
        $blob = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($plan)));
        $hit = false;
        foreach ($needles as $needle) {
            if (str_contains($blob, mb_strtolower($needle))) {
                $hit = true;
                break;
            }
        }
        $this->assertTrue($hit, $query.' compiled='.$blob);
    }

    public function test_measurement_compile_does_not_become_entity_selection(): void
    {
        $plan = $this->planner->planKnowledgeQuery([
            'query' => 'What temperature is suitable for wheat growth?',
        ]);
        $this->assertSame('range', $plan->normalizedQuery->constraints['question_type'] ?? null);
        $blob = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($plan)));
        $this->assertStringContainsString('triticum aestivum', $blob);
        $this->assertStringContainsString('temperature', $blob);
        $this->assertStringNotContainsString('crop recommendation', $blob);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function compiledBlob(array $input): string
    {
        $plan = $this->planner->planKnowledgeQuery($input);

        return mb_strtolower(implode("\n", $this->builder->buildVariantsFromPlan($plan)));
    }

    private function blobHasProcessAsSoleEntity(string $blob, string $cropId, string $scientificName): bool
    {
        $first = trim(explode("\n", $blob)[0] ?? '');
        if ($first === '') {
            return true;
        }
        $hasIdentity = str_contains($first, mb_strtolower($scientificName))
            || str_contains($first, mb_strtolower($cropId));

        return ! $hasIdentity;
    }

    private function everyVariantKeepsResolvedIdentity(string $blob, string $scientificName, string $cropId): bool
    {
        $scientific = mb_strtolower($scientificName);
        $id = mb_strtolower(str_replace('-', ' ', $cropId));
        foreach (preg_split("/\n+/", trim($blob)) ?: [] as $variant) {
            $variant = trim($variant);
            if ($variant === '') {
                continue;
            }
            if (! str_contains($variant, $scientific) && ! str_contains($variant, $id)) {
                return false;
            }
        }

        return trim($blob) !== '';
    }
}
