<?php

namespace Tests\Unit\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqEntity;
use App\Services\Agriculture\Research\CsqGeography;
use App\Services\Agriculture\Research\CsqProperty;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\CapabilityToPathEligibilityMapper;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Projection\ProjectionSearchOptionsBinder;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

final class ProjectionSearchOptionsBinderTest extends TestCase
{
    public function test_skips_without_capability_or_path_decision(): void
    {
        $options = (new ProjectionSearchOptionsBinder())->attach([], 'csq:test', null, null, null);

        $this->assertSame(
            ProjectionSearchOptionsBinder::STATUS_SKIPPED_MISSING_DECISION,
            $options[ProjectionSearchOptionsBinder::STATUS_KEY],
        );
        $meta = $options[ProjectionSearchOptionsBinder::OPTIONS_KEY];
        $this->assertSame([], $meta['unsupported_facets']);
        $this->assertSame('PROJECTION_SKIPPED_NO_CAPABILITY_DECISION', $meta['projection_warnings'][0]['code']);
        $this->assertArrayNotHasKey('source_query', $meta);
    }

    public function test_attaches_approved_metadata_from_capability_decision(): void
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('G3-01'),
            CanonicalSourceIdentityId::fromString('cid_apis'),
        );
        $records = [
            CapabilityRecord::create(
                CapabilityRecordId::fromString('rec-species'),
                $subject,
                CapabilityDimension::scientificFacetQueryConstrainable('species'),
                CapabilityState::VERIFIED,
                CapabilityEvidenceFreshness::CURRENT,
            ),
            CapabilityRecord::create(
                CapabilityRecordId::fromString('rec-geo'),
                $subject,
                CapabilityDimension::scientificFacetQueryConstrainable('geography'),
                CapabilityState::UNAVAILABLE,
                CapabilityEvidenceFreshness::CURRENT,
            ),
        ];
        $cap = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([
                CapabilityDimension::scientificFacetQueryConstrainable('species'),
                CapabilityDimension::scientificFacetQueryConstrainable('geography'),
            ]),
            $records,
            'cap-dec-runtime-001',
            '2026-11-11T00:00:00Z',
        );
        $mapper = new CapabilityToPathEligibilityMapper();
        $path = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-runtime-001'),
            PathId::fromString('path-runtime-001'),
            PathFamily::P01,
            $cap,
            $mapper->suggestedStatus($mapper->map($cap->andResultClass)),
            decidedAt: '2026-11-11T00:00:00Z',
        );

        $options = (new ProjectionSearchOptionsBinder())->attach(
            ['include_semantic_score' => true],
            'csq:runtime-001',
            null,
            $cap,
            $path,
            ['species', 'geography'],
        );

        $this->assertSame(ProjectionSearchOptionsBinder::STATUS_ATTACHED, $options[ProjectionSearchOptionsBinder::STATUS_KEY]);
        $meta = $options[ProjectionSearchOptionsBinder::OPTIONS_KEY];
        $this->assertContains('geography', $meta['unsupported_facets']);
        $this->assertArrayNotHasKey('source_query', $meta);
        $this->assertArrayHasKey('projection_query_constraints', $options);
    }

    public function test_builder_omits_country_when_geography_unsupported(): void
    {
        $builder = new ScientificSearchQueryBuilder();
        $plan = $this->planWithEgypt();

        $withGeo = $builder->buildConsensusRequestOptions($plan);
        $this->assertArrayHasKey('country', $withGeo);

        $withoutGeo = $builder->buildConsensusRequestOptions($plan, [
            'unsupported_facets' => ['geography'],
            'omitted_facets' => [],
            'unresolved_facets' => [],
            'projection_warnings' => [],
        ]);
        $this->assertArrayNotHasKey('country', $withoutGeo);
        $this->assertTrue($withoutGeo['include_semantic_score']);
    }

    public function test_orchestrator_source_contains_projection_binder_reference(): void
    {
        $path = dirname(__DIR__, 5).DIRECTORY_SEPARATOR.'app'
            .DIRECTORY_SEPARATOR.'Services'
            .DIRECTORY_SEPARATOR.'Agriculture'
            .DIRECTORY_SEPARATOR.'Research'
            .DIRECTORY_SEPARATOR.'Search'
            .DIRECTORY_SEPARATOR.'MultiSourceScientificSearchOrchestrator.php';
        $source = (string) file_get_contents($path);
        $this->assertStringContainsString('ProjectionSearchOptionsBinder', $source);
        $this->assertStringContainsString('projectionBinder->attach', $source);
    }

    private function planWithEgypt(): KnowledgeQueryPlan
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'What is wheat yield in Egypt?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'wheat',
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            property: new CsqProperty(key: 'yield', surface: 'yield'),
            geography: new CsqGeography(country: 'Egypt', label: 'Egypt'),
        );

        $normalized = new AgriculturalKnowledgeQuery(
            originalQuestion: $question->originalQuestion,
            normalizedQuestion: $question->originalQuestion,
            language: $question->language,
            agriculturalDomain: 'crops',
            subject: null,
            crop: $question->entity->normalized,
            cropId: $question->entity->canonicalId,
            scientificName: null,
            topic: 'research',
            subtopic: null,
            requestedInformation: [],
            constraints: [],
            location: $question->geography->country,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: $question,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $normalized,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'crops',
            subjectEntity: null,
            topics: [],
            subtopics: [],
            requestedInformation: [],
            evidenceRequirements: [],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
