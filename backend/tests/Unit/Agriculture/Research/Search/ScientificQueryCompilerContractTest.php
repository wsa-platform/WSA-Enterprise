<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\CsqEntity;
use App\Services\Agriculture\Research\CsqGeography;
use App\Services\Agriculture\Research\CsqProcess;
use App\Services\Agriculture\Research\CsqProperty;
use App\Services\Agriculture\Research\CsqRelation;
use App\Services\Agriculture\Research\CsqTarget;
use App\Services\Agriculture\Research\CsqTime;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificQueryCompiler;
use App\Services\Agriculture\Research\Search\ScientificSourceProfileCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceQueryModality;
use PHPUnit\Framework\TestCase;

final class ScientificQueryCompilerContractTest extends TestCase
{
    private ScientificQueryCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiler = new ScientificQueryCompiler();
    }

    public function test_compiler_does_not_require_or_invoke_adapters(): void
    {
        $question = $this->wheatTemperatureCsq();
        $plan = $this->plan($question);
        $profile = ScientificSourceProfileCatalog::get('openalex');
        $this->assertNotNull($profile);

        $bundle = $this->compiler->compile($question, $plan, $profile);

        $this->assertTrue($bundle->representable);
        $this->assertSame('openalex', $bundle->sourceKey);
        $this->assertStringContainsString('wheat', mb_strtolower($bundle->sourceQuery));
        $this->assertStringContainsString('temperature', mb_strtolower($bundle->sourceQuery));
    }

    public function test_same_csq_different_sources_preserve_scientific_identity(): void
    {
        $question = $this->wheatYieldEgypt2022Csq();
        $plan = $this->plan($question);
        $openAlex = $this->compiler->compile($question, $plan, ScientificSourceProfileCatalog::get('openalex'));
        $fao = $this->compiler->compile($question, $plan, ScientificSourceProfileCatalog::get('fao_stat'));

        // Lexical tokens may overlap; representation style and filters must differ by modality.
        $this->assertSame('natural_language', ScientificSourceProfileCatalog::get('openalex')->queryStyle);
        $this->assertSame('structured_filters', ScientificSourceProfileCatalog::get('fao_stat')->queryStyle);
        $this->assertArrayHasKey('entity', $fao->structuredFilters);
        $this->assertArrayHasKey('property', $fao->structuredFilters);
        $this->assertArrayHasKey('geography', $fao->structuredFilters);
        $this->assertArrayHasKey('year', $fao->structuredFilters);
        $this->assertSame($openAlex->scientificIdentity['entity'], $fao->scientificIdentity['entity']);
        $this->assertSame($openAlex->scientificIdentity['property'], $fao->scientificIdentity['property']);
        $this->assertSame($openAlex->scientificIdentity['geography'], $fao->scientificIdentity['geography']);
        $this->assertSame($openAlex->scientificIdentity['time'], $fao->scientificIdentity['time']);
        $this->assertSame('wheat', $openAlex->scientificIdentity['entity']['normalized']);
        $this->assertSame('yield', $openAlex->scientificIdentity['property']['key']);
        $this->assertSame('Egypt', $openAlex->scientificIdentity['geography']['country']);
        $this->assertSame(2022, $openAlex->scientificIdentity['time']['year']);
    }

    public function test_entity_target_process_property_relation_preserved(): void
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'What is the effect of irrigation on wheat yield?',
            language: 'en',
            normalizedForm: 'normalized',
            researchContext: 'home',
            entity: new CsqEntity(
                surface: 'wheat',
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            target: new CsqTarget(
                surface: 'yield',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                normalizedKey: 'yield',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            process: new CsqProcess(
                surface: 'irrigation',
                normalized: 'irrigation',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            property: new CsqProperty(
                key: 'yield',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            relation: new CsqRelation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: 'process',
                to: 'target',
                state: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
        );

        $identity = $this->compiler->projectScientificIdentity($question);
        $this->assertSame('wheat', $identity['entity']['normalized']);
        $this->assertSame('yield', $identity['target']['normalized']);
        $this->assertSame('irrigation', $identity['process']['normalized']);
        $this->assertSame('yield', $identity['property']['key']);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $identity['relation']['type']);
        $this->assertNotSame($identity['entity']['normalized'], $identity['process']['normalized']);
        $this->assertNotSame($identity['entity']['normalized'], $identity['target']['normalized']);
    }

    public function test_unresolved_fields_do_not_invent_canonical_ids(): void
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'What is the temperature?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'crop',
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
            property: new CsqProperty(
                surface: 'temperature',
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        );

        $identity = $this->compiler->projectScientificIdentity($question);
        $this->assertNull($identity['entity']['canonical_id']);
        $this->assertNull($identity['entity']['canonical_namespace']);
        $bundle = $this->compiler->compile(
            $question,
            $this->plan($question),
            ScientificSourceProfileCatalog::get('openalex'),
        );
        $this->assertArrayNotHasKey('entity_canonical_id', $bundle->structuredFilters);
    }

    public function test_crop_binding_does_not_replace_entity(): void
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'Livestock feed requirement?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'cattle',
                normalized: 'cattle',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'cattle',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK,
            ),
            property: new CsqProperty(key: 'feed_requirement', surface: 'feed'),
            cropBinding: new CsqCropBinding(
                cropId: 'wheat',
                cropLabel: 'Wheat',
                scientificName: 'Triticum aestivum',
                context: 'crop_profile',
            ),
        );

        $identity = $this->compiler->projectScientificIdentity($question);
        $bundle = $this->compiler->compile(
            $question,
            $this->plan($question),
            ScientificSourceProfileCatalog::get('openalex'),
        );

        $this->assertSame('cattle', $identity['entity']['normalized']);
        $this->assertTrue($identity['crop_binding_present']);
        $this->assertStringContainsString('cattle', mb_strtolower($bundle->sourceQuery));
        $this->assertStringNotContainsString('wheat', mb_strtolower($bundle->sourceQuery));
        $this->assertStringNotContainsString('triticum', mb_strtolower($bundle->sourceQuery));
    }

    public function test_geography_and_time_preservation(): void
    {
        $question = $this->wheatYieldEgypt2022Csq();
        $identity = $this->compiler->projectScientificIdentity($question);
        $this->assertSame('Egypt', $identity['geography']['country']);
        $this->assertSame(2022, $identity['time']['year']);

        $fao = $this->compiler->compile(
            $question,
            $this->plan($question),
            ScientificSourceProfileCatalog::get('fao_stat'),
        );
        $this->assertSame('Egypt', $fao->structuredFilters['geography']);
        $this->assertSame(2022, $fao->structuredFilters['year']);
        $this->assertSame('wheat', $fao->structuredFilters['entity']);
        $this->assertSame('yield', $fao->structuredFilters['property']);
    }

    /**
     * @dataProvider multilingualWheatQuestions
     */
    public function test_multilingual_surfaces_share_scientific_identity(string $original, string $language): void
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: $original,
            language: $language,
            entity: new CsqEntity(
                surface: $original,
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            process: new CsqProcess(normalized: 'growth', surface: 'growth'),
            property: new CsqProperty(key: 'temperature', surface: 'temperature'),
        );

        $identity = $this->compiler->projectScientificIdentity($question);
        $this->assertSame('wheat', $identity['entity']['normalized']);
        $this->assertSame('wheat', $identity['entity']['canonical_id']);
        $this->assertSame('temperature', $identity['property']['key']);

        $bundle = $this->compiler->compile(
            $question,
            $this->plan($question),
            ScientificSourceProfileCatalog::get('semantic_scholar'),
        );
        $this->assertStringContainsString('wheat', mb_strtolower($bundle->sourceQuery));
        $this->assertStringContainsString('temperature', mb_strtolower($bundle->sourceQuery));
    }

    public function test_inactive_expansion_profile_is_not_representable(): void
    {
        $profile = ScientificSourceProfileCatalog::get('agris');
        $this->assertNotNull($profile);
        $this->assertFalse($profile->active);
        $this->assertFalse($this->compiler->canRepresent($this->wheatTemperatureCsq(), $profile));
    }

    public function test_literature_and_scientific_data_modalities_are_distinct(): void
    {
        $this->assertSame(
            ScientificSourceQueryModality::LITERATURE,
            ScientificSourceProfileCatalog::get('openalex')->modality,
        );
        $this->assertSame(
            ScientificSourceQueryModality::SCIENTIFIC_DATA,
            ScientificSourceProfileCatalog::get('fao_stat')->modality,
        );
        $this->assertNotSame(
            ScientificSourceProfileCatalog::get('openalex')->modality,
            ScientificSourceProfileCatalog::get('fao_stat')->modality,
        );
    }

    public function test_comparative_relation_preserved_in_identity(): void
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'مقارنة إنتاج القمح والذرة',
            language: 'ar',
            entity: new CsqEntity(normalized: 'wheat', surface: 'القمح'),
            relation: new CsqRelation(
                type: CanonicalScientificQuestion::RELATION_COMPARATIVE,
                state: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                operands: [
                    ['surface' => 'القمح', 'normalized' => 'wheat', 'resolution' => 'resolved'],
                    ['surface' => 'الذرة', 'normalized' => 'maize', 'resolution' => 'resolved'],
                ],
            ),
            property: new CsqProperty(key: 'production', surface: 'إنتاج'),
        );

        $identity = $this->compiler->projectScientificIdentity($question);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $identity['relation']['type']);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $identity['relation']['type']);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function multilingualWheatQuestions(): array
    {
        return [
            ['ما هي احتياجات القمح من الماء؟', 'ar'],
            ['What is the optimal temperature for wheat growth?', 'en'],
            ['Quelle est la température optimale pour le blé?', 'fr'],
            ['Buğday için optimum sıcaklık nedir?', 'tr'],
        ];
    }

    private function wheatTemperatureCsq(): CanonicalScientificQuestion
    {
        return new CanonicalScientificQuestion(
            originalQuestion: 'What is the optimal temperature for wheat growth?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'wheat',
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            process: new CsqProcess(normalized: 'growth', surface: 'growth'),
            property: new CsqProperty(key: 'temperature', surface: 'temperature'),
        );
    }

    private function wheatYieldEgypt2022Csq(): CanonicalScientificQuestion
    {
        return new CanonicalScientificQuestion(
            originalQuestion: 'What is wheat yield in Egypt in 2022?',
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
            time: new CsqTime(year: 2022),
        );
    }

    private function plan(CanonicalScientificQuestion $question): KnowledgeQueryPlan
    {
        $normalized = new AgriculturalKnowledgeQuery(
            originalQuestion: $question->originalQuestion,
            normalizedQuestion: $question->normalizedForm !== '' ? $question->normalizedForm : $question->originalQuestion,
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
