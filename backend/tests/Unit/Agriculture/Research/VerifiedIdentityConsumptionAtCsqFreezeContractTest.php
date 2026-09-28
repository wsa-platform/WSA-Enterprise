<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\RetrievalSpecification;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * RC-1: verified crop-profile identity is consumed at CSQ freeze when ENTITY
 * is empty, process-role, or knowledge-option, and is never invented on Home.
 */
class VerifiedIdentityConsumptionAtCsqFreezeContractTest extends TestCase
{
    private QueryUnderstandingService $qus;

    private ReflectionMethod $canonicalize;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
        $this->canonicalize = new ReflectionMethod(QueryUnderstandingService::class, 'canonicalizeRoleGraphForFreeze');
        $this->canonicalize->setAccessible(true);
    }

    /**
     * @return list<array{0: string, 1: array<string, mixed>, 2: CsqCropBinding}>
     */
    public static function nonEntitySlotsWithVerifiedBinding(): array
    {
        $binding = self::cropBinding('potato', 'Potato', 'Solanum tuberosum');

        return [
            'empty_en' => ['en', [], $binding],
            'process_growth_en' => ['en', ['entity_surface' => 'growth', 'entity_normalized' => 'growth'], $binding],
            'process_croissance_fr' => ['fr', ['entity_surface' => 'croissance', 'entity_normalized' => 'croissance'], $binding],
            'process_nomo_ar' => ['ar', ['entity_surface' => 'نمو', 'entity_normalized' => 'نمو'], $binding],
            'process_buyume_tr' => ['tr', ['entity_surface' => 'büyüme', 'entity_normalized' => 'büyüme'], $binding],
            'option_scientific_research' => ['en', ['entity_surface' => 'scientific-research', 'entity_normalized' => 'scientific-research'], $binding],
            'option_farming_needs' => ['en', ['entity_surface' => 'farming-needs', 'entity_normalized' => 'farming-needs'], $binding],
            'process_wrapper' => ['en', ['entity_surface' => 'growth potato', 'entity_normalized' => 'growth potato'], $binding],
        ];
    }

    /**
     * @dataProvider nonEntitySlotsWithVerifiedBinding
     *
     * @param  array<string, mixed>  $graph
     */
    public function test_freeze_consumes_verified_binding_when_entity_is_non_entity(
        string $language,
        array $graph,
        CsqCropBinding $binding,
    ): void {
        $this->assertNotSame('', $language);
        $after = $this->canonicalize->invoke($this->qus, $graph, $binding);
        $this->assertSame('potato', $after['entity_canonical_id'] ?? null);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP, $after['entity_canonical_namespace'] ?? null);
        $this->assertSame('potato', $after['entity_normalized'] ?? null);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_RESOLVED, $after['entity_resolution'] ?? null);
        $this->assertSame('Potato', $after['entity_surface'] ?? null);
        $this->assertNotNull(FieldCropTaxonomyCatalog::entryFor('potato'));
    }

    public function test_independently_verified_different_crop_is_not_replaced(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'rice',
            'entity_normalized' => 'rice',
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertSame('rice', $after['entity_canonical_id'] ?? null);
        $this->assertSame('rice', $after['entity_normalized'] ?? null);
        $this->assertNotSame('wheat', $after['entity_canonical_id'] ?? null);
    }

    public function test_process_wrapper_around_different_crop_is_not_replaced_by_binding(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'cultivation rice',
            'entity_normalized' => 'cultivation rice',
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertSame('rice', $after['entity_canonical_id'] ?? null);
        $this->assertNotSame('wheat', $after['entity_canonical_id'] ?? null);
        $this->assertNotSame('Wheat', $after['entity_surface'] ?? null);
    }

    public function test_independently_verified_same_crop_is_left_intact(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'wheat',
            'entity_normalized' => 'wheat',
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertSame('wheat', $after['entity_canonical_id'] ?? null);
        $this->assertSame('wheat', $after['entity_surface'] ?? null);
    }

    public function test_independently_verified_livestock_is_not_replaced(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'أبقار',
            'entity_normalized' => 'cattle',
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertSame('cattle', $after['entity_canonical_id'] ?? null);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $after['entity_canonical_namespace'] ?? null);
        $this->assertSame('أبقار', $after['entity_surface'] ?? null);
    }

    public function test_plant_family_surface_is_not_replaced_by_crop_binding(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'Fabaceae',
            'entity_normalized' => 'Fabaceae',
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertNotSame('wheat', $after['entity_canonical_id'] ?? null);
        $this->assertSame('Fabaceae', $after['entity_surface'] ?? null);
    }

    public function test_unresolved_named_entity_is_not_replaced(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'زيرقون',
            'entity_normalized' => 'زيرقون',
            'entity_resolution' => CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
        ], self::cropBinding('wheat', 'Wheat', 'Triticum aestivum'));

        $this->assertNull($after['entity_canonical_id'] ?? null);
        $this->assertSame('زيرقون', $after['entity_surface'] ?? null);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $after['entity_resolution'] ?? null);
    }

    public function test_unverified_binding_is_not_consumed(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [], new CsqCropBinding(
            cropId: 'not-a-taxonomy-crop',
            cropLabel: 'Fake',
            scientificName: null,
            context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE,
        ));

        $this->assertNull($after['entity_canonical_id'] ?? null);
        $this->assertArrayNotHasKey('entity_resolution', $after);
        $this->assertNull(FieldCropTaxonomyCatalog::entryFor('not-a-taxonomy-crop'));
    }

    public function test_home_binding_is_not_a_universal_fallback(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'growth',
            'entity_normalized' => 'growth',
        ], new CsqCropBinding(
            cropId: 'wheat',
            cropLabel: 'Wheat',
            scientificName: 'Triticum aestivum',
            context: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
        ));

        $this->assertNull($after['entity_canonical_id'] ?? null);
        $this->assertNotSame('wheat', $after['entity_normalized'] ?? null);
    }

    public function test_empty_home_question_does_not_invent_identity(): void
    {
        $query = $this->qus->understand([
            'query' => 'What is crop rotation in general agriculture?',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME, $csq->researchContext);
        $this->assertNull($csq->cropBinding->cropId);
        $this->assertNull($csq->entity->canonicalId);
        $this->assertNotSame(CanonicalScientificQuestion::RESOLUTION_RESOLVED, $csq->entity->resolution);
    }

    public function test_home_recognized_identity_stays_on_the_question_entity(): void
    {
        $query = $this->qus->understand([
            'query' => 'Quelle est la température optimale de germination du blé ?',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME, $csq->researchContext);
        $this->assertSame('wheat', $csq->entity->canonicalId);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
    }

    /**
     * @return list<array{0: string, 1: array<string, string>}>
     */
    public static function cropProfileProcessOrOptionQuestions(): array
    {
        return [
            'tr_process_without_crop_token' => ['barley', [
                'query' => 'büyüme sıcaklığı nedir?',
                'selected_crop_id' => 'barley',
                'selected_crop_name' => 'Arpa',
                'scientific_name' => 'Hordeum vulgare',
                'knowledge_option' => 'farming-needs',
            ]],
            'fr_process_without_crop_token' => ['tomato', [
                'query' => 'Quelle est la croissance optimale ?',
                'selected_crop_id' => 'tomato',
                'selected_crop_name' => 'Tomate',
                'scientific_name' => 'Solanum lycopersicum',
                'knowledge_option' => 'farming-needs',
            ]],
            'en_option_slug' => ['sesame', [
                'query' => 'scientific-research',
                'selected_crop_id' => 'sesame',
                'selected_crop_name' => 'Sesame',
                'scientific_name' => 'Sesamum indicum',
                'knowledge_option' => 'scientific-research',
            ]],
            'ar_empty_query_option' => ['rice', [
                'query' => '',
                'selected_crop_id' => 'rice',
                'selected_crop_name' => 'الأرز',
                'scientific_name' => 'Oryza sativa',
                'knowledge_option' => 'scientific-research',
            ]],
        ];
    }

    /**
     * @dataProvider cropProfileProcessOrOptionQuestions
     *
     * @param  array<string, string>  $input
     */
    public function test_crop_profile_csq_carries_verified_binding_identity(string $cropId, array $input): void
    {
        $query = $this->qus->understand($input);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE, $csq->researchContext);
        $this->assertSame($cropId, $csq->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP, $csq->entity->canonicalNamespace);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_RESOLVED, $csq->entity->resolution);
        $this->assertSame($cropId, $csq->cropBinding->cropId);
        $this->assertNotSame('scientific-research', $csq->entity->surface);
        $this->assertNotSame('farming-needs', $csq->entity->canonicalId);
        $this->assertSame($cropId, $this->specEntity(RetrievalSpecification::fromCanonical($csq))['canonicalId']);
    }

    public function test_crop_page_livestock_question_keeps_livestock_on_csq(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'scientific_name' => 'Triticum aestivum',
            'knowledge_option' => 'farming-needs',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('cattle', $csq->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $csq->entity->canonicalNamespace);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
    }

    public function test_crop_page_named_different_crop_keeps_that_crop(): void
    {
        $query = $this->qus->understand([
            'query' => 'irrigation requirements for barley',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'scientific_name' => 'Triticum aestivum',
            'knowledge_option' => 'farming-needs',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('barley', $csq->entity->canonicalId);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
    }

    public function test_retrieval_specification_projects_frozen_identity_without_reinference(): void
    {
        $query = $this->qus->understand([
            'query' => 'büyüme sıcaklığı nedir?',
            'selected_crop_id' => 'barley',
            'selected_crop_name' => 'Arpa',
            'scientific_name' => 'Hordeum vulgare',
            'knowledge_option' => 'farming-needs',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('barley', $csq->entity->canonicalId);

        $spec = RetrievalSpecification::fromCanonical($csq);
        $projected = $this->specEntity($spec);
        $this->assertSame('barley', $projected['canonicalId']);
        $this->assertSame($csq->entity->canonicalNamespace, $projected['canonicalNamespace']);
        $this->assertSame($csq->entity->resolution, $projected['resolution']);

        $plan = (new ResearchPlanner($this->qus))->planKnowledgeQuery([
            'query' => 'büyüme sıcaklığı nedir?',
            'selected_crop_id' => 'barley',
            'selected_crop_name' => 'Arpa',
            'scientific_name' => 'Hordeum vulgare',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertSame(
            $csq->entity->canonicalId,
            $plan->normalizedQuery->canonicalQuestion?->entity->canonicalId,
        );
    }

    public function test_process_is_not_the_canonical_entity_after_consumption(): void
    {
        $after = $this->canonicalize->invoke($this->qus, [
            'entity_surface' => 'croissance',
            'process_surface' => 'croissance',
        ], self::cropBinding('tomato', 'Tomate', 'Solanum lycopersicum'));

        $this->assertSame('tomato', $after['entity_canonical_id'] ?? null);
        $this->assertNotSame('croissance', $after['entity_canonical_id'] ?? null);
        $this->assertNotSame('croissance', $after['entity_normalized'] ?? null);
        $this->assertSame('croissance', $after['process_surface'] ?? null);
    }

    public function test_missing_binding_does_not_fill_process_or_option_slots(): void
    {
        $empty = new CsqCropBinding(context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE);
        foreach ([
            ['entity_surface' => 'growth'],
            ['entity_surface' => 'scientific-research'],
        ] as $graph) {
            $after = $this->canonicalize->invoke($this->qus, $graph, $empty);
            $this->assertNull($after['entity_canonical_id'] ?? null, (string) ($graph['entity_surface'] ?? ''));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function specEntity(RetrievalSpecification $spec): array
    {
        $array = $spec->toArray();
        $entity = $array['projections']['entity'] ?? null;
        if (! is_array($entity)) {
            $this->fail('RetrievalSpecification did not project an entity.');
        }

        return $entity;
    }

    private static function cropBinding(string $cropId, string $label, string $scientificName): CsqCropBinding
    {
        class_exists(CanonicalScientificQuestion::class);

        return new CsqCropBinding(
            cropId: $cropId,
            cropLabel: $label,
            scientificName: $scientificName,
            context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE,
        );
    }
}
