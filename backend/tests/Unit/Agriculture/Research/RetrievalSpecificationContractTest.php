<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqConditions;
use App\Services\Agriculture\Research\CsqContext;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\CsqEvidenceRequirement;
use App\Services\Agriculture\Research\CsqGeography;
use App\Services\Agriculture\Research\CsqResolution;
use App\Services\Agriculture\Research\CsqTime;
use App\Services\Agriculture\Research\RetrievalSpecification;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RetrievalSpecificationContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        class_exists(RetrievalSpecification::class);
    }

    public function test_from_canonical_is_pure_csq_projection(): void
    {
        $method = new ReflectionMethod(RetrievalSpecification::class, 'fromCanonical');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(CanonicalScientificQuestion::class, $params[0]->getType()?->getName());
    }

    public function test_every_csq_role_is_accounted_or_waived(): void
    {
        $spec = RetrievalSpecification::fromCanonical($this->fullCsq());
        foreach ([
            'entity', 'target', 'process', 'property', 'relation', 'context',
            'conditions', 'time', 'geography', 'evidence', 'resolution', 'crop_binding',
        ] as $role) {
            $this->assertArrayHasKey($role, $spec->roleAccounting, $role);
            $class = $spec->roleAccounting[$role];
            $this->assertContains($class, [
                RetrievalSpecification::CLASS_REQUIRED,
                RetrievalSpecification::CLASS_SUPPORTING,
                RetrievalSpecification::CLASS_CONTEXTUAL,
                RetrievalSpecification::CLASS_EXECUTION_ONLY,
                RetrievalSpecification::CLASS_EXPLICITLY_WAIVED,
            ], $role);
            if ($class === RetrievalSpecification::CLASS_EXPLICITLY_WAIVED) {
                $this->assertNotSame('', trim((string) ($spec->waivers[$role] ?? '')), $role);
            }
        }
        $this->assertSame(RetrievalSpecification::SOURCE_CSQ, $spec->source);
    }

    public function test_entity_only_projection(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat', normalized: 'wheat', resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('wheat', $spec->projections['entity']['surface']);
        $this->assertNull($spec->projections['target']['surface']);
        $this->assertNotSame($spec->projections['entity']['surface'], $spec->projections['target']['surface']);
    }

    public function test_target_only_stays_distinct_from_entity(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(
                surface: 'grain production',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertNull($spec->projections['entity']['surface']);
        $this->assertSame('grain production', $spec->projections['target']['surface']);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $spec->projections['target']['kind']);
        $this->assertNotSame($spec->projections['target']['surface'], $spec->projections['entity']['surface']);
    }

    public function test_process_only_is_not_converted_to_target(): void
    {
        $csq = $this->csq(
            process: CanonicalScientificQuestion::process(surface: 'irrigation', resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('irrigation', $spec->projections['process']['surface']);
        $this->assertNull($spec->projections['target']['surface']);
        $this->assertNull($spec->projections['property']['surface']);
    }

    public function test_property_only_keeps_key_surface_and_of_role(): void
    {
        $csq = $this->csq(
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
                unit: 't/ha',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('quantity', $spec->projections['property']['key']);
        $this->assertSame('yield', $spec->projections['property']['surface']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $spec->projections['property']['ofRole']);
        $this->assertSame('t/ha', $spec->projections['property']['unit']);
        $this->assertNotSame($spec->projections['property']['key'], $spec->projections['property']['surface']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $spec->propertyOfRole);
    }

    public function test_entity_and_target_remain_independent(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle'),
            target: CanonicalScientificQuestion::target(surface: 'milk production', kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('cattle', $spec->projections['entity']['surface']);
        $this->assertSame('milk production', $spec->projections['target']['surface']);
        $this->assertNotSame($spec->projections['entity']['surface'], $spec->projections['target']['surface']);
    }

    public function test_entity_and_property_do_not_invent_target(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
            property: CanonicalScientificQuestion::property(key: 'irrigation', surface: 'water requirement', ofRole: CanonicalScientificQuestion::PROPERTY_OF_ENTITY),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('wheat', $spec->projections['entity']['surface']);
        $this->assertNull($spec->projections['target']['surface']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_ENTITY, $spec->projections['property']['ofRole']);
    }

    public function test_target_and_property_keep_of_role_target(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(surface: 'biomass'),
            property: CanonicalScientificQuestion::property(key: 'quantity', surface: 'weight', ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('biomass', $spec->projections['target']['surface']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $spec->projections['property']['ofRole']);
        $this->assertNull($spec->projections['entity']['surface']);
    }

    public function test_entity_process_target_stay_three_roles(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('cattle', $spec->projections['entity']['surface']);
        $this->assertSame('feeding', $spec->projections['process']['surface']);
        $this->assertSame('milk production', $spec->projections['target']['surface']);
    }

    public function test_full_causal_graph_preserves_endpoints(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            property: CanonicalScientificQuestion::property(key: 'quantity', surface: 'yield', ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_TARGET,
                state: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $spec->relationFrom);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $spec->relationTo);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_RESOLVED, $spec->projections['relation']['state']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $spec->propertyOfRole);
        $this->assertArrayNotHasKey('effect', array_flip($spec->requiredTerms()));
    }

    public function test_comparative_relation_is_not_collapsed_to_causal(): void
    {
        $spec = RetrievalSpecification::fromCanonical($this->csq(
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_COMPARATIVE,
                from: CanonicalScientificQuestion::ROLE_ENTITY,
                to: CanonicalScientificQuestion::ROLE_ENTITY,
            ),
        ));
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $spec->relationType);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
    }

    public function test_associative_relation_survives(): void
    {
        $spec = RetrievalSpecification::fromCanonical($this->csq(
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_ASSOCIATIVE),
        ));
        $this->assertSame(CanonicalScientificQuestion::RELATION_ASSOCIATIVE, $spec->relationType);
    }

    public function test_descriptive_relation_survives(): void
    {
        $spec = RetrievalSpecification::fromCanonical($this->csq(
            relation: CanonicalScientificQuestion::relation(type: CanonicalScientificQuestion::RELATION_DESCRIPTIVE),
        ));
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $spec->relationType);
    }

    public function test_unresolved_target_keeps_surface_and_invents_no_id(): void
    {
        $csq = $this->csq(
            target: CanonicalScientificQuestion::target(
                surface: 'unknown output',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('unknown output', $spec->projections['target']['surface']);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $spec->projections['target']['resolution']);
        $this->assertNull($spec->projections['target']['canonicalId']);
        $this->assertNull($spec->projections['target']['canonicalNamespace']);
        $this->assertNull($spec->projections['entity']['canonicalId']);
        $this->assertNull($spec->projections['process']['canonicalId']);
    }

    public function test_crop_binding_does_not_become_question_entity(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle'),
            cropBinding: new CsqCropBinding(cropId: 'wheat', cropLabel: 'wheat', context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('cattle', $spec->projections['entity']['surface']);
        $this->assertSame('wheat', $spec->projections['crop_binding']['cropId']);
        $this->assertNotSame($spec->projections['crop_binding']['cropId'], $spec->projections['entity']['surface']);
        $this->assertSame(RetrievalSpecification::CLASS_CONTEXTUAL, $spec->roleAccounting['crop_binding']);
    }

    public function test_context_is_not_promoted_to_process(): void
    {
        $csq = $this->csq(
            context: new CsqContext(domain: 'agronomy', scientificSense: 'agriculture', researchIntent: 'general_knowledge', questionType: 'definition'),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('agriculture', $spec->projections['context']['scientificSense']);
        $this->assertNull($spec->projections['process']['surface']);
        $this->assertSame(RetrievalSpecification::CLASS_SUPPORTING, $spec->roleAccounting['context']);
    }

    public function test_populated_conditions_are_contextual_not_discarded(): void
    {
        $csq = $this->csq(
            conditions: new CsqConditions([['type' => 'environment', 'value' => 'arid', 'label' => 'arid']]),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(RetrievalSpecification::CLASS_CONTEXTUAL, $spec->roleAccounting['conditions']);
        $this->assertSame('arid', $spec->projections['conditions'][0]['value'] ?? null);
        $this->assertArrayNotHasKey('conditions', array_filter($spec->waivers));
    }

    public function test_time_is_copied_and_explicitly_waived_for_scholarly_nl(): void
    {
        $csq = $this->csq(time: new CsqTime(year: 2022, period: 'calendar_year'));
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame(2022, $spec->projections['time']['year']);
        $this->assertSame(RetrievalSpecification::CLASS_EXPLICITLY_WAIVED, $spec->roleAccounting['time']);
        $this->assertNotSame('', $spec->waivers['time']);
        $this->assertStringContainsString('FAOSTAT', $spec->waivers['time']);
    }

    public function test_geography_is_contextual_not_entity(): void
    {
        $csq = $this->csq(geography: new CsqGeography(label: 'Egypt', country: 'EG'));
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame('Egypt', $spec->projections['geography']['label']);
        $this->assertNull($spec->projections['entity']['surface']);
        $this->assertSame(RetrievalSpecification::CLASS_CONTEXTUAL, $spec->roleAccounting['geography']);
    }

    public function test_evidence_is_execution_only_and_not_a_query_term(): void
    {
        $csq = $this->csq(
            evidence: new CsqEvidenceRequirement(requiredEvidenceType: 'peer_reviewed', requiresFactualDirect: null),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertContains($spec->roleAccounting['evidence'], [
            RetrievalSpecification::CLASS_EXECUTION_ONLY,
            RetrievalSpecification::CLASS_EXPLICITLY_WAIVED,
        ]);
        $this->assertSame('peer_reviewed', $spec->projections['evidence']['requiredEvidenceType']);
        $this->assertNotContains('peer_reviewed', $spec->requiredTerms());
        $this->assertNotSame('', $spec->waivers['evidence']);
    }

    public function test_compile_term_does_not_replace_frozen_surface_with_key(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'أبقار', normalized: 'cattle'),
            property: CanonicalScientificQuestion::property(key: 'quantity', surface: 'yield'),
        );
        $spec = RetrievalSpecification::fromCanonical($csq);
        $entity = $spec->conceptsForRole(CanonicalScientificQuestion::ROLE_ENTITY)[0];
        $property = $spec->conceptsForRole(CanonicalScientificQuestion::ROLE_PROPERTY)[0];
        $this->assertSame('أبقار', $spec->frozenSurface($entity));
        $this->assertSame('yield', $spec->frozenSurface($property));
        $this->assertSame('quantity', $spec->projections['property']['key']);
        $this->assertSame('أبقار', $spec->projections['entity']['surface']);
    }

    public function test_from_legacy_query_remains_isolated(): void
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'legacy',
            normalizedQuestion: 'legacy',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: [],
            constraints: [
                'scientific_topics' => ['should-not-appear-in-canonical'],
                'requested_property' => 'irrigation',
                'target_surface' => 'legacy-target',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );
        $legacy = RetrievalSpecification::fromLegacyQuery($query);
        $this->assertSame(RetrievalSpecification::SOURCE_LEGACY, $legacy->source);
        $canonical = RetrievalSpecification::fromCanonical($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'wheat'),
        ));
        $this->assertSame(RetrievalSpecification::SOURCE_CSQ, $canonical->source);
        $this->assertSame('wheat', $canonical->projections['entity']['surface']);
        $this->assertNotSame('legacy-target', $canonical->projections['target']['surface']);
        $this->assertNotContains('should-not-appear-in-canonical', $canonical->requiredTerms());
    }

    public function test_round_trip_array_preserves_projections_and_waivers(): void
    {
        $spec = RetrievalSpecification::fromCanonical($this->fullCsq());
        $restored = RetrievalSpecification::fromArray($spec->toArray());
        $this->assertSame($spec->source, $restored->source);
        $this->assertSame($spec->relationType, $restored->relationType);
        $this->assertSame($spec->propertyOfRole, $restored->propertyOfRole);
        $this->assertSame($spec->projections['target']['surface'], $restored->projections['target']['surface']);
        $this->assertSame($spec->waivers['time'], $restored->waivers['time']);
        $this->assertSame($spec->roleAccounting['evidence'], $restored->roleAccounting['evidence']);
    }

    private function fullCsq(): CanonicalScientificQuestion
    {
        return $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle', normalized: 'cattle', resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED),
            target: CanonicalScientificQuestion::target(surface: 'milk production', kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            property: CanonicalScientificQuestion::property(key: 'quantity', surface: 'yield', ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_TARGET,
            ),
            context: new CsqContext(scientificSense: 'production_quantity'),
            conditions: new CsqConditions([['type' => 'environment', 'label' => 'arid']]),
            time: new CsqTime(year: 2020),
            geography: new CsqGeography(label: 'Brazil'),
            evidence: new CsqEvidenceRequirement(requiredEvidenceType: 'peer_reviewed'),
            resolution: new CsqResolution(ambiguityState: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED),
            cropBinding: new CsqCropBinding(cropId: 'wheat', context: CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE),
        );
    }

    private function csq(
        mixed $entity = null,
        mixed $target = null,
        mixed $process = null,
        mixed $property = null,
        mixed $relation = null,
        ?CsqContext $context = null,
        ?CsqConditions $conditions = null,
        ?CsqTime $time = null,
        ?CsqGeography $geography = null,
        ?CsqEvidenceRequirement $evidence = null,
        ?CsqResolution $resolution = null,
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
            conditions: $conditions ?? new CsqConditions(),
            time: $time ?? new CsqTime(),
            geography: $geography ?? new CsqGeography(),
            evidenceRequirement: $evidence ?? new CsqEvidenceRequirement(),
            resolution: $resolution ?? new CsqResolution(),
            cropBinding: $cropBinding ?? new CsqCropBinding(),
        );
    }
}
