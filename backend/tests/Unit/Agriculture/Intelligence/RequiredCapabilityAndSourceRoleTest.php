<?php

namespace Tests\Unit\Agriculture\Intelligence;

use App\Services\Agriculture\Intelligence\Contracts\ProviderCapability;
use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\Orchestration\CapabilityDrivenSourceSelector;
use App\Services\Agriculture\Intelligence\Orchestration\RequiredCapabilityResolver;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use Tests\TestCase;

class RequiredCapabilityAndSourceRoleTest extends TestCase
{
    public function test_literature_question_does_not_require_official_statistics(): void
    {
        $caps = app(RequiredCapabilityResolver::class)->resolve($this->plan(
            question: 'irrigation efficiency research',
            intent: 'scientific_research',
            topic: 'irrigation',
        ));

        $this->assertContains(ProviderCapability::WEB_SEARCH, $caps);
        $this->assertContains(ProviderCapability::SCIENTIFIC_SEARCH, $caps);
        $this->assertContains(ProviderCapability::CITATION_METADATA, $caps);
        $this->assertNotContains(ProviderCapability::OFFICIAL_AGRICULTURAL_DATA, $caps);
        $this->assertNotContains(ProviderCapability::WEATHER, $caps);
        $this->assertNotContains(ProviderCapability::PLANT_DISEASE_ANALYSIS, $caps);
    }

    public function test_statistical_question_requires_official_agricultural_data(): void
    {
        $caps = app(RequiredCapabilityResolver::class)->resolve($this->plan(
            question: 'national agricultural statistics and harvested area',
            intent: 'agricultural_economics',
            topic: 'production statistics',
            questionType: 'statistical',
        ));

        $this->assertContains(ProviderCapability::OFFICIAL_AGRICULTURAL_DATA, $caps);
        $this->assertContains(ProviderCapability::AGRICULTURAL_STATISTICS, $caps);
    }

    public function test_forecast_question_requires_weather_and_skips_scholarly_minimum_set(): void
    {
        $caps = app(RequiredCapabilityResolver::class)->resolve($this->plan(
            question: 'current weather forecast tomorrow',
            intent: 'weather',
            topic: 'weather',
            researchRequired: false,
            readyForStage3: true,
        ));

        $this->assertContains(ProviderCapability::WEATHER, $caps);
        $this->assertContains(ProviderCapability::WEB_SEARCH, $caps);
        $this->assertNotContains(ProviderCapability::SCIENTIFIC_SEARCH, $caps);
    }

    public function test_disease_question_requires_plant_disease_capability(): void
    {
        $caps = app(RequiredCapabilityResolver::class)->resolve($this->plan(
            question: 'plant disease pathogen identification',
            intent: 'disease',
            topic: 'plant disease',
        ));

        $this->assertContains(ProviderCapability::PLANT_DISEASE_ANALYSIS, $caps);
        $this->assertContains(ProviderCapability::SCIENTIFIC_SEARCH, $caps);
    }

    public function test_explicit_required_capabilities_are_not_overridden(): void
    {
        $caps = app(RequiredCapabilityResolver::class)->resolve(
            $this->plan('irrigation efficiency research', 'scientific_research', 'irrigation'),
            ['required_capabilities' => [ProviderCapability::WEB_SEARCH]],
        );

        $this->assertSame([ProviderCapability::WEB_SEARCH], $caps);
    }

    public function test_fao_and_crossref_source_roles_are_not_peer_reviewed_indexes(): void
    {
        $registry = app(AgriculturalProviderRegistry::class);
        $fao = $registry->get('fao_stat');
        $crossref = $registry->get('crossref');
        $openalex = $registry->get('openalex');

        $this->assertNotNull($fao);
        $this->assertNotNull($crossref);
        $this->assertNotNull($openalex);

        $this->assertSame(SourceRole::OFFICIAL_AGRICULTURAL_DATA, $fao->descriptor()->confidenceMeta['source_role'] ?? null);
        $this->assertNotContains('peer_reviewed_index', $fao->descriptor()->capabilities);
        $this->assertContains('official_agricultural_data', $fao->descriptor()->capabilities);

        $this->assertSame(SourceRole::CITATION_METADATA, $crossref->descriptor()->confidenceMeta['source_role'] ?? null);
        $this->assertNotContains('peer_reviewed_index', $crossref->descriptor()->capabilities);
        $this->assertContains('citation_metadata', $crossref->descriptor()->capabilities);

        $this->assertSame(SourceRole::SCIENTIFIC_EVIDENCE, $openalex->descriptor()->confidenceMeta['source_role'] ?? null);
        $this->assertContains('peer_reviewed_index', $openalex->descriptor()->capabilities);
    }

    public function test_selector_skips_fao_when_official_data_not_required_and_records_reason(): void
    {
        $plan = $this->plan('irrigation efficiency research', 'scientific_research', 'irrigation');
        $caps = app(RequiredCapabilityResolver::class)->resolve($plan);
        $input = new ProviderQueryInput(
            query: 'irrigation efficiency research',
            requiredCapabilities: $caps,
        );

        $trace = app(CapabilityDrivenSourceSelector::class)->selectWithTrace($plan, $input);
        $ids = $trace->selectedIds();

        $this->assertContains('openalex', $ids);
        $this->assertNotContains('fao_stat', $ids);
        $this->assertNotContains('open_meteo', $ids);

        $faoSkip = null;
        foreach ($trace->skipped as $row) {
            if ($row['id'] === 'fao_stat') {
                $faoSkip = $row['reason'];
                break;
            }
        }
        $this->assertSame('capability_not_required', $faoSkip);
    }

    public function test_registry_any_capability_match_does_not_require_all_caps_on_one_provider(): void
    {
        $registry = app(AgriculturalProviderRegistry::class);
        $matched = $registry->selectMatchingAnyCapability(
            ['scientific', 'web'],
            [ProviderCapability::WEB_SEARCH, ProviderCapability::SCIENTIFIC_SEARCH],
            true,
        );
        $ids = array_map(static fn ($p): string => $p->descriptor()->id, $matched);

        $this->assertNotEmpty($ids);
        $this->assertTrue(
            in_array('openalex', $ids, true) || in_array('semantic_scholar', $ids, true),
        );
    }

    private function plan(
        string $question,
        string $intent,
        string $topic,
        string $questionType = 'general',
        bool $researchRequired = true,
        bool $readyForStage3 = true,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => $topic],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: ['question_type' => $questionType],
            location: null,
            researchRequired: $researchRequired,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'topic', 'value' => $topic],
            topics: [$topic],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: $readyForStage3,
        );
    }
}
