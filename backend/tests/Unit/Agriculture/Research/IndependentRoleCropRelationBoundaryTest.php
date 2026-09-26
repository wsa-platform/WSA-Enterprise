<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class IndependentRoleCropRelationBoundaryTest extends TestCase
{
    private ScientificSearchQueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        class_exists(RetrievalSpecification::class);
        $this->builder = new ScientificSearchQueryBuilder();
    }

    public function test_crop_only_context_is_not_an_independent_question(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat', normalized: 'wheat'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertFalse(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $this->assertFalse(RetrievalSpecification::fromCanonical($csq)->hasIndependentQuestionRoles);
        $variants = $this->canonicalVariants($csq, cropContext: true);
        $this->assertIsArray($variants);
        $this->assertStringContainsString('wheat', mb_strtolower($variants[0] ?? ''));
    }

    public function test_crop_plus_target_is_independent(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_crop_plus_process_is_independent(): void
    {
        $csq = $this->csq(
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('feeding', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_crop_plus_property_surface_is_independent(): void
    {
        $csq = $this->csq(
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            ),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('yield', $primary);
        $this->assertStringNotContainsString('quantity', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_property_key_without_surface_is_not_independent(): void
    {
        $csq = $this->csq(
            property: CanonicalScientificQuestion::property(key: 'quantity'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertFalse(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
    }

    public function test_crop_plus_entity_alone_follows_existing_contract(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertFalse(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $variants = $this->canonicalVariants($csq, cropContext: true);
        $this->assertIsArray($variants);
        $this->assertStringContainsString('cattle', mb_strtolower($variants[0] ?? ''));
        $this->assertStringNotContainsString('wheat', mb_strtolower($variants[0] ?? ''));
    }

    public function test_crop_plus_entity_and_target_is_independent(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_crop_plus_full_causal_graph_is_independent(): void
    {
        $csq = $this->causalCattleCsq();
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringContainsString('feeding', $primary);
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringContainsString('yield', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
        $this->assertStringNotContainsString('effect', $primary);
    }

    public function test_crop_binding_does_not_overwrite_question_entity(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            cropBinding: $this->wheatBinding(),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('cattle', $spec->projections['entity']['surface']);
        $this->assertSame('wheat', $spec->projections['crop_binding']['cropId']);
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_crop_binding_does_not_overwrite_question_target(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            cropBinding: $this->wheatBinding(),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('milk production', $spec->projections['target']['surface']);
        $this->assertNotSame('wheat', $spec->projections['target']['surface']);
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '');
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_causal_relation_preserves_process_and_target_endpoints(): void
    {
        $csq = $this->causalCattleCsq();
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $spec->relationFrom);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $spec->relationTo);
        $joined = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))));
        $this->assertStringContainsString('feeding', $joined);
        $this->assertStringContainsString('milk production', $joined);
        $this->assertStringNotContainsString('effect', $joined);
    }

    public function test_comparative_endpoints_are_preserved_without_effect(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
            target: CanonicalScientificQuestion::target(surface: 'maize'),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_COMPARATIVE,
                from: CanonicalScientificQuestion::ROLE_ENTITY,
                to: CanonicalScientificQuestion::ROLE_TARGET,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $spec->relationType);
        $this->assertSame(CanonicalScientificQuestion::ROLE_ENTITY, $spec->relationFrom);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $spec->relationTo);
        $joined = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($this->plan($csq))));
        $this->assertStringContainsString('wheat', $joined);
        $this->assertStringContainsString('maize', $joined);
        $this->assertStringNotContainsString('effect', $joined);
    }

    public function test_associative_relation_is_not_rewritten_as_causal(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'maize'),
            process: CanonicalScientificQuestion::process(surface: 'pollination'),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_ASSOCIATIVE,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_ENTITY,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_ASSOCIATIVE, $spec->relationType);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
        $joined = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($this->plan($csq))));
        $this->assertStringContainsString('maize', $joined);
        $this->assertStringContainsString('pollination', $joined);
        $this->assertStringNotContainsString('effect', $joined);
    }

    public function test_descriptive_relation_does_not_become_causal(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'rice'),
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_DESCRIPTIVE),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertTrue($spec->hasIndependentQuestionRoles);
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $spec->relationType);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
        $joined = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($this->plan($csq))));
        $this->assertStringContainsString('rice', $joined);
        $this->assertStringNotContainsString('effect', $joined);
    }

    public function test_unresolved_target_is_preserved(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(
                surface: 'إنتاج اللبن',
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
            cropBinding: $this->wheatBinding(),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('إنتاج اللبن', $spec->projections['target']['surface']);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $spec->projections['target']['resolution']);
        $this->assertNull($spec->projections['target']['canonicalId']);
        $primary = $this->builder->buildVariantsFromPlan($this->plan($csq, cropContext: true))[0] ?? '';
        $this->assertStringContainsString('إنتاج اللبن', $primary);
    }

    public function test_relation_does_not_invent_missing_endpoint(): void
    {
        $csq = $this->csq(
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_TARGET,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertNull($spec->projections['target']['surface']);
        $this->assertTrue($spec->hasIndependentQuestionRoles);
        $joined = mb_strtolower(implode(' | ', $this->builder->buildVariantsFromPlan($this->plan($csq))));
        $this->assertStringContainsString('feeding', $joined);
        $this->assertStringNotContainsString('milk', $joined);
        $this->assertStringNotContainsString('effect', $joined);
    }

    public function test_context_fields_alone_are_not_independent(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
            cropBinding: $this->wheatBinding(),
        );
        $this->assertFalse(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $this->assertFalse(RetrievalSpecification::fromCanonical($csq)->hasIndependentQuestionRoles);
    }

    public function test_arabic_causal_on_crop_page_is_independent_and_preserves_roles(): void
    {
        $csq = $this->causalCattleCsq();
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($csq));
        $this->assertSame('cattle', $csq->entity->surface);
        $this->assertSame('milk production', $csq->target->surface);
        $this->assertSame('feeding', $csq->process->surface);
        $this->assertSame('quantity', $csq->property->key);
        $this->assertSame('yield', $csq->property->surface);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame('wheat', $csq->cropBinding->cropId);

        $qus = new QueryUnderstandingService();
        $live = $qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]);
        $liveCsq = $live->canonicalQuestion;
        $this->assertNotNull($liveCsq);
        $this->assertTrue(RetrievalSpecification::csqHasIndependentQuestionRoles($liveCsq));
        $this->assertSame('cattle', $liveCsq->entity->normalized);
        $this->assertSame('إنتاج اللبن', $liveCsq->target->surface);
        $this->assertSame('التغذية', $liveCsq->process->surface);
        $this->assertSame('quantity', $liveCsq->property->key);
        $this->assertSame('yield', $liveCsq->property->surface);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $liveCsq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $liveCsq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $liveCsq->relation->to);
        $this->assertSame('wheat', $liveCsq->cropBinding->cropId);
        $this->assertNotSame('wheat', $liveCsq->entity->normalized);
    }

    /**
     * @return list<string>|null
     */
    private function canonicalVariants(CanonicalScientificQuestion $csq, bool $cropContext): ?array
    {
        $method = new \ReflectionMethod(ScientificSearchQueryBuilder::class, 'buildVariantsFromCanonicalSpecification');
        $method->setAccessible(true);

        /** @var list<string> $variants */
        $variants = $method->invoke($this->builder, $this->plan($csq, cropContext: $cropContext));

        return $variants;
    }

    private function causalCattleCsq(): CanonicalScientificQuestion
    {
        return $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            ),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_TARGET,
            ),
            cropBinding: $this->wheatBinding(),
        );
    }

    private function wheatBinding(): CsqCropBinding
    {
        return new CsqCropBinding(
            cropId: 'wheat',
            cropLabel: 'wheat',
            context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE,
        );
    }

    private function plan(CanonicalScientificQuestion $csq, bool $cropContext = false): KnowledgeQueryPlan
    {
        $cropId = $cropContext ? 'wheat' : null;
        $context = $cropContext
            ? ['selected_crop_id' => 'wheat', 'selected_crop_name' => 'wheat']
            : [];

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'probe',
            normalizedQuestion: 'probe',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: 'scientific_research',
            subtopic: $cropContext ? 'farming-needs' : null,
            requestedInformation: [],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: $csq,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'agronomy',
            subjectEntity: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            topics: ['scientific_research'],
            subtopics: $cropContext ? ['farming-needs'] : [],
            requestedInformation: [],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $context,
            readyForStage3: true,
        );
    }

    private function csq(
        mixed $entity = null,
        mixed $target = null,
        mixed $process = null,
        mixed $property = null,
        mixed $relation = null,
        ?CsqCropBinding $cropBinding = null,
    ): CanonicalScientificQuestion {
        return new CanonicalScientificQuestion(
            originalQuestion: 'probe',
            language: 'en',
            normalizedForm: 'probe',
            researchContext: $cropBinding !== null
                ? CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE
                : CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
            entity: $entity ?? CanonicalScientificQuestion::entity(),
            target: $target ?? CanonicalScientificQuestion::target(),
            process: $process ?? CanonicalScientificQuestion::process(),
            property: $property ?? CanonicalScientificQuestion::property(),
            relation: $relation ?? CanonicalScientificQuestion::relation(),
            cropBinding: $cropBinding ?? new CsqCropBinding(),
        );
    }
}
