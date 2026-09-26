<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqContext;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class ScientificSearchQueryBuilderAuthorityTest extends TestCase
{
    private ScientificSearchQueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        class_exists(RetrievalSpecification::class);
        $this->builder = new ScientificSearchQueryBuilder();
    }

    public function test_conflicting_legacy_fields_cannot_change_csq_variants(): void
    {
        $csq = $this->causalProductionCsq();
        $clean = $this->builder->buildVariantsFromPlan($this->plan($csq, [
            'scientific_topics' => [],
            'requested_property' => 'quantity',
        ]));
        $contaminated = $this->builder->buildVariantsFromPlan($this->plan($csq, [
            'scientific_topics' => ['photosynthesis', 'climate change'],
            'requested_property' => 'temperature',
            'requested_property_query_terms' => ['temperature range'],
            'semantic_target' => ['entity_surface' => 'wheat'],
            'scientific_sense' => 'plant_physiology',
            'scientific_factors' => ['salinity'],
        ], cropId: 'wheat', topic: 'agriculture', intent: 'cultivation', context: [
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]));

        $this->assertSame($clean, $contaminated);
        $primary = mb_strtolower($contaminated[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringContainsString('feeding', $primary);
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringContainsString('yield', $primary);
        $this->assertStringNotContainsString('photosynthesis', $primary);
        $this->assertStringNotContainsString('temperature', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
        $this->assertStringNotContainsString('quantity', $primary);
    }

    public function test_entity_and_target_remain_distinct_for_livestock_and_crop(): void
    {
        $livestock = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            target: CanonicalScientificQuestion::target(surface: 'milk production', kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT),
        )));
        $crop = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
            target: CanonicalScientificQuestion::target(surface: 'wheat production', kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT),
        )));
        $this->assertStringContainsString('cattle', mb_strtolower($livestock[0] ?? ''));
        $this->assertStringContainsString('milk production', mb_strtolower($livestock[0] ?? ''));
        $this->assertStringContainsString('wheat', mb_strtolower($crop[0] ?? ''));
        $this->assertStringContainsString('wheat production', mb_strtolower($crop[0] ?? ''));
        $this->assertNotSame('wheat production', 'wheat');
    }

    public function test_process_is_preserved_across_domains(): void
    {
        $feeding = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
        )));
        $irrigation = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            process: CanonicalScientificQuestion::process(surface: 'irrigation'),
            target: CanonicalScientificQuestion::target(surface: 'wheat production'),
        )));
        $this->assertStringContainsString('feeding', mb_strtolower($feeding[0] ?? ''));
        $this->assertStringContainsString('milk production', mb_strtolower($feeding[0] ?? ''));
        $this->assertStringContainsString('irrigation', mb_strtolower($irrigation[0] ?? ''));
        $this->assertStringContainsString('wheat production', mb_strtolower($irrigation[0] ?? ''));
        $this->assertStringNotContainsString('agriculture', mb_strtolower($feeding[0] ?? ''));
    }

    public function test_property_surface_is_not_replaced_by_key(): void
    {
        $variants = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            ),
        )));
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('yield', $joined);
        $this->assertStringContainsString('milk production', $joined);
        $this->assertStringNotContainsString('quantity', mb_strtolower($variants[0] ?? ''));
    }

    public function test_relation_types_do_not_collapse_to_causal_wording(): void
    {
        $causal = $this->builder->buildVariantsFromPlan($this->plan($this->causalProductionCsq()));
        $this->assertStringContainsString('feeding', mb_strtolower($causal[0] ?? ''));
        $this->assertStringContainsString('milk production', mb_strtolower($causal[0] ?? ''));

        $comparative = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
            property: CanonicalScientificQuestion::property(surface: 'yield'),
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_COMPARATIVE),
        )));
        $associative = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'maize'),
            process: CanonicalScientificQuestion::process(surface: 'pollination'),
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_ASSOCIATIVE),
        )));
        $descriptive = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'rice'),
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_DESCRIPTIVE),
        )));
        $this->assertStringContainsString('wheat', mb_strtolower($comparative[0] ?? ''));
        $this->assertStringContainsString('maize', mb_strtolower($associative[0] ?? ''));
        $this->assertStringContainsString('pollination', mb_strtolower($associative[0] ?? ''));
        $this->assertStringContainsString('rice', mb_strtolower($descriptive[0] ?? ''));
        $this->assertStringNotContainsString('effect', mb_strtolower($comparative[0] ?? ''));
        $this->assertStringNotContainsString('effect', mb_strtolower($descriptive[0] ?? ''));
    }

    public function test_unresolved_target_surface_is_kept_without_invented_id(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(
                surface: 'إنتاج اللبن',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        );
        $this->assertNull($csq->target->canonicalId);
        $primary = $this->builder->buildVariantsFromPlan($this->plan($csq))[0] ?? '';
        $this->assertStringContainsString('إنتاج اللبن', $primary);
        $this->assertStringNotContainsString('faostat', mb_strtolower($primary));
    }

    public function test_crop_binding_does_not_replace_question_entity(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            cropBinding: new CsqCropBinding(cropId: 'wheat', cropLabel: 'wheat', context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE),
        );
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($this->plan($csq, [], cropId: 'wheat', context: [
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]))[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringContainsString('feeding', $primary);
        $this->assertStringNotContainsString('wheat', $primary);
    }

    public function test_required_roles_survive_variant_budget(): void
    {
        $variants = $this->builder->buildVariantsFromPlan($this->plan($this->causalProductionCsq()));
        $this->assertLessThanOrEqual(5, count($variants));
        $primary = mb_strtolower($variants[0] ?? '');
        $this->assertStringContainsString('cattle', $primary);
        $this->assertStringContainsString('feeding', $primary);
        $this->assertStringContainsString('milk production', $primary);
        $this->assertStringContainsString('yield', $primary);
    }

    public function test_null_csq_keeps_legacy_entity_from_crop_id(): void
    {
        $plan = $this->plan(null, [
            'scientific_topics' => ['irrigation'],
            'requested_property' => 'irrigation',
        ], cropId: 'barley', topic: 'irrigation', intent: 'irrigation');
        $this->assertNull($plan->normalizedQuery->canonicalQuestion);
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($plan)[0] ?? '');
        $this->assertStringContainsString('barley', $primary);
    }

    public function test_arabic_causal_graph_keeps_all_required_roles(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'أبقار', normalized: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'التغذية'),
            target: CanonicalScientificQuestion::target(
                surface: 'إنتاج اللبن',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
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
        );
        $primary = $this->builder->buildVariantsFromPlan($this->plan($csq, [
            'scientific_topics' => ['agriculture'],
            'requested_property' => 'temperature',
        ]))[0] ?? '';
        $this->assertStringContainsString('أبقار', $primary);
        $this->assertStringContainsString('التغذية', $primary);
        $this->assertStringContainsString('إنتاج اللبن', $primary);
        $this->assertStringContainsString('yield', $primary);
        $this->assertStringNotContainsString('temperature', mb_strtolower($primary));
        $this->assertStringNotContainsString('quantity', mb_strtolower($primary));
    }

    public function test_target_only_is_not_promoted_to_entity_or_dropped(): void
    {
        $primary = $this->builder->buildVariantsFromPlan($this->plan($this->csq(
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
        )))[0] ?? '';
        $this->assertSame('milk production', $primary);
    }

    /**
     * @param  array<string, mixed>  $constraints
     * @param  array<string, mixed>  $context
     */
    private function plan(
        ?CanonicalScientificQuestion $csq,
        array $constraints = [],
        ?string $cropId = null,
        string $topic = 'scientific_research',
        string $intent = 'scientific_research',
        array $context = [],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'probe',
            normalizedQuestion: 'probe',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: [],
            constraints: $constraints,
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
            canonicalQuestion: $csq,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            topics: [$topic, ...$this->stringList($constraints['scientific_topics'] ?? [])],
            subtopics: [],
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

    private function causalProductionCsq(): CanonicalScientificQuestion
    {
        return $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(
                surface: 'milk production',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
            ),
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
        );
    }

    private function csq(
        mixed $entity = null,
        mixed $target = null,
        mixed $process = null,
        mixed $property = null,
        mixed $relation = null,
        ?CsqContext $context = null,
        ?CsqCropBinding $cropBinding = null,
    ): CanonicalScientificQuestion {
        return new CanonicalScientificQuestion(
            originalQuestion: 'probe',
            language: 'en',
            normalizedForm: 'probe',
            researchContext: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
            entity: $entity ?? CanonicalScientificQuestion::entity(),
            target: $target ?? CanonicalScientificQuestion::target(),
            process: $process ?? CanonicalScientificQuestion::process(),
            property: $property ?? CanonicalScientificQuestion::property(),
            relation: $relation ?? CanonicalScientificQuestion::relation(),
            context: $context ?? new CsqContext(),
            cropBinding: $cropBinding ?? new CsqCropBinding(),
        );
    }

    /**
     * @param  mixed  $values
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }
        $out = [];
        foreach ($values as $value) {
            $label = trim((string) $value);
            if ($label !== '') {
                $out[] = $label;
            }
        }

        return $out;
    }
}
