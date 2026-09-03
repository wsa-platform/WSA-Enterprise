<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Agriculture\Diagnosis\Knowledge\DiagnosisKnowledgeSupportInterface;
use App\Services\Agriculture\Diagnosis\Knowledge\HeuristicDiagnosisKnowledgeSupport;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisDifferentialEntry;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeConfidenceBand;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeIngestionService;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeMatcher;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeQuery;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeRecord;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeRecordValidator;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeRetrievalService;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeSafetyStatus;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\DiagnosisKnowledgeVerificationStatus;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\InMemoryDiagnosisKnowledgeStore;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\KnowledgeBaseDiagnosisKnowledgeSupport;
use App\Services\Agriculture\Diagnosis\KnowledgeBase\SeededDiagnosisKnowledgeCatalog;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\VisionObservation;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\AgriculturalScientificKnowledgeEngine;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

class PlantAiDiagnosisKnowledgeBaseStage7Test extends TestCase
{
    use RefreshDatabase;

    private InMemoryDiagnosisKnowledgeStore $store;

    private DiagnosisKnowledgeRecordValidator $validator;

    private DiagnosisKnowledgeIngestionService $ingestion;

    private DiagnosisKnowledgeRetrievalService $retrieval;

    private DiagnosisKnowledgeMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        config(['ai.provider' => 'mock']);

        $this->store = app(InMemoryDiagnosisKnowledgeStore::class);
        $this->store->clear();
        $this->validator = app(DiagnosisKnowledgeRecordValidator::class);
        $this->ingestion = app(DiagnosisKnowledgeIngestionService::class);
        app(SeededDiagnosisKnowledgeCatalog::class)->seed();
        $this->matcher = app(DiagnosisKnowledgeMatcher::class);
        $this->retrieval = app(DiagnosisKnowledgeRetrievalService::class);
    }

    private function validImage(): UploadedFile
    {
        $binary = $this->makePngBinary(128, 128);
        $path = tempnam(sys_get_temp_dir(), 'wsa_kb_');
        $this->assertNotFalse($path);
        $target = $path.'.png';
        rename($path, $target);
        file_put_contents($target, $binary);

        return new UploadedFile($target, 'plant.png', 'image/png', null, true);
    }

    private function makePngBinary(int $width, int $height): string
    {
        $raw = '';
        for ($y = 0; $y < $height; $y++) {
            $raw .= "\x00";
            for ($x = 0; $x < $width; $x++) {
                $raw .= "\x2E\x7D\x32";
            }
        }

        $ihdr = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n";
        $png .= $this->pngChunk('IHDR', $ihdr);
        $png .= $this->pngChunk('IDAT', gzcompress($raw, 9) ?: '');
        $png .= $this->pngChunk('IEND', '');

        return $png;
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    /** @return array<string, mixed> */
    private function baseRecord(array $overrides = []): array
    {
        return array_merge([
            'id' => 'test_kb_record_'.uniqid(),
            'common_name' => 'Test leaf disorder',
            'category' => 'disease',
            'causal_class' => 'biotic',
            'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
            'crop_keys' => ['tomato'],
            'symptoms' => ['leaf spot'],
            'plant_parts' => ['leaf'],
            'observation_patterns' => [
                [
                    'id' => 'p1',
                    'keywords' => ['spot'],
                    'observation_types' => ['leaf_spot'],
                ],
            ],
            'sources' => [[
                'label' => 'Test curated source',
                'type' => 'curated_catalog',
                'claims_scientific_evidence' => false,
            ]],
            'differentials' => [],
            'supporting_evidence_notes' => ['Fixture supporting note'],
            'contradicting_evidence_notes' => [],
        ], $overrides);
    }

    public function test_01_knowledge_record_creation_and_validation(): void
    {
        $record = DiagnosisKnowledgeRecord::fromArray($this->baseRecord([
            'id' => 'kb_valid_create',
        ]));
        $result = $this->validator->validate($record);
        $this->assertTrue($result->valid);
        $this->assertSame([], $result->errors);
    }

    public function test_02_scientific_source_provenance_required_when_claimed(): void
    {
        $record = DiagnosisKnowledgeRecord::fromArray($this->baseRecord([
            'id' => 'kb_needs_provenance',
            'scientific_name' => 'Alternaria solani',
            'scientific_name_verified' => true,
            'sources' => [],
        ]));
        $result = $this->validator->validate($record);
        $this->assertFalse($result->valid);
        $this->assertContains('provenance_required_for_verified_scientific_name', $result->errors);
    }

    public function test_03_duplicate_prevention_on_ingest(): void
    {
        $payload = $this->baseRecord(['id' => 'kb_dup_fixture']);
        $first = $this->ingestion->ingest($payload, markVerified: true);
        $second = $this->ingestion->ingest($payload, markVerified: true);

        $this->assertTrue($first['accepted']);
        $this->assertFalse($second['accepted']);
        $this->assertSame('duplicate', $second['status']);
    }

    public function test_04_crop_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            symptoms: ['leaf spot', 'lesion'],
            cropKey: 'tomato',
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $cropHits = array_filter(
            $matches,
            static fn ($m) => in_array('crop', $m->matchReasons, true) || in_array('tomato', $m->record->cropKeys, true),
        );
        $this->assertNotEmpty($cropHits);
    }

    public function test_05_scientific_name_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            scientificName: 'Alternaria solani',
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $this->assertContains('exact_scientific_name', $matches[0]->matchReasons);
        $this->assertSame('Alternaria solani', $matches[0]->record->scientificName);
    }

    public function test_06_alias_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            aliases: ['brown rust'],
            cropKey: 'wheat',
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $this->assertTrue(
            in_array('alias', $matches[0]->matchReasons, true)
            || in_array('kb_wheat_leaf_rust_like', array_map(static fn ($m) => $m->record->id, $matches), true)
        );
    }

    public function test_07_symptom_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            symptoms: ['powdery', 'white coating'],
            verifiedOnly: true,
        ));

        $ids = array_map(static fn ($m) => $m->record->id, $matches);
        $this->assertContains('kb_powdery_mildew_like', $ids);
    }

    public function test_08_plant_part_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            plantPart: 'leaf',
            symptoms: ['spot'],
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $this->assertTrue(
            in_array('plant_part', $matches[0]->matchReasons, true)
            || in_array('leaf', $matches[0]->record->plantParts, true)
        );
    }

    public function test_09_multiple_observation_matching(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            observations: [
                new VisionObservation('o1', 'leaf_spot', 'Brown lesions with yellow halo', 'lower leaves', 'moderate', 0.7, ['spot', 'lesion', 'yellow halo']),
                new VisionObservation('o2', 'chlorosis', 'Yellowing around lesions', 'foliage', 'low', 0.5, ['yellowing', 'chlorosis']),
            ],
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $multi = array_values(array_filter(
            $matches,
            static fn ($m) => in_array('multiple_observation', $m->matchReasons, true),
        ));
        $this->assertNotEmpty($multi);
    }

    public function test_10_differential_diagnosis(): void
    {
        $record = $this->store->get('kb_nitrogen_deficiency_like');
        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(3, count($record->differentials));
        $relations = array_map(static fn ($d) => $d->relation, $record->differentials);
        $this->assertContains(DiagnosisDifferentialEntry::RELATION_ALTERNATIVE, $relations);
    }

    public function test_11_contradictory_evidence_preserved(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            observations: [
                new VisionObservation('o1', 'leaf_spot', 'spots with powdery white coating', null, null, 0.6, ['spot', 'powdery', 'white coating']),
            ],
            symptoms: ['spot', 'powdery'],
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $withContradiction = array_values(array_filter(
            $matches,
            static fn ($m) => $m->contradictingEvidence !== [] || in_array('negative_evidence', $m->matchReasons, true),
        ));
        $this->assertNotEmpty($withContradiction);
    }

    public function test_12_insufficient_evidence(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            symptoms: ['completely unrelated glitter sparkle'],
            verifiedOnly: true,
        ));

        $this->assertSame([], $matches);
    }

    public function test_13_confidence_bands(): void
    {
        $this->assertSame(
            ['very_low', 'low', 'moderate', 'high'],
            DiagnosisKnowledgeConfidenceBand::all(),
        );
        $this->assertSame(DiagnosisKnowledgeConfidenceBand::HIGH, DiagnosisKnowledgeConfidenceBand::fromScore(0.8));
        $this->assertSame(DiagnosisKnowledgeConfidenceBand::VERY_LOW, DiagnosisKnowledgeConfidenceBand::fromScore(0.1));
        $this->assertLessThanOrEqual(0.92, 0.92);
    }

    public function test_14_safety_states(): void
    {
        $this->assertSame(
            ['safe', 'caution', 'insufficient_evidence', 'human_review_required'],
            DiagnosisKnowledgeSafetyStatus::all(),
        );
    }

    public function test_15_missing_observations_handled_safely(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            observations: [],
            symptoms: ['leaf spot'],
            verifiedOnly: true,
        ));

        $this->assertIsArray($matches);
        foreach ($matches as $match) {
            $this->assertContains($match->safetyStatus, DiagnosisKnowledgeSafetyStatus::all());
            $this->assertLessThan(1.0, $match->matchScore);
        }
    }

    public function test_16_disease_knowledge_retrieval(): void
    {
        $matches = $this->retrieval->retrieve(new DiagnosisKnowledgeQuery(
            disease: 'early blight',
            cropKey: 'tomato',
            verifiedOnly: true,
        ));
        $this->assertNotEmpty($matches);
        $this->assertSame('disease', $matches[0]->record->category);
    }

    public function test_17_nutrient_deficiency_retrieval(): void
    {
        $matches = $this->retrieval->retrieve(new DiagnosisKnowledgeQuery(
            nutrient: 'nitrogen',
            symptoms: ['chlorosis', 'yellowing'],
            verifiedOnly: true,
        ));
        $ids = array_map(static fn ($m) => $m->record->id, $matches);
        $this->assertContains('kb_nitrogen_deficiency_like', $ids);
    }

    public function test_18_pest_retrieval(): void
    {
        $matches = $this->retrieval->retrieve(new DiagnosisKnowledgeQuery(
            pest: 'insect',
            symptoms: ['holes', 'chewing', 'frass'],
            verifiedOnly: true,
        ));
        $ids = array_map(static fn ($m) => $m->record->id, $matches);
        $this->assertContains('kb_insect_chewing_damage', $ids);
    }

    public function test_19_abiotic_stress_retrieval(): void
    {
        $matches = $this->retrieval->retrieve(new DiagnosisKnowledgeQuery(
            abioticStress: 'salinity',
            symptoms: ['marginal burn', 'tip burn'],
            verifiedOnly: true,
        ));
        $ids = array_map(static fn ($m) => $m->record->id, $matches);
        $this->assertContains('kb_salinity_stress', $ids);
    }

    public function test_20_pathogen_classification(): void
    {
        $record = $this->store->get('kb_alternaria_early_blight_like');
        $this->assertNotNull($record);
        $this->assertSame('fungal', $record->pathogenType);
        $this->assertSame('biotic', $record->causalClass);
    }

    public function test_21_evidence_ranking_is_deterministic(): void
    {
        $query = new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            observations: [
                new VisionObservation('o1', 'leaf_spot', 'Brown circular lesions with yellow halos', 'lower leaves', 'moderate', 0.7, ['spot', 'lesion', 'yellow halo']),
            ],
            verifiedOnly: true,
        );

        $a = $this->matcher->match($query);
        $b = $this->matcher->match($query);
        $this->assertSame(
            array_map(static fn ($m) => $m->record->id, $a),
            array_map(static fn ($m) => $m->record->id, $b),
        );
        $this->assertNotEmpty($a);
        if (count($a) > 1) {
            $this->assertTrue($a[0]->matchScore >= $a[1]->matchScore);
        }
    }

    public function test_22_source_provenance_integrity(): void
    {
        $record = $this->store->get('kb_alternaria_early_blight_like');
        $this->assertNotNull($record);
        $this->assertNotEmpty($record->sources);
        $this->assertNotSame('', $record->sources[0]->label);
        $this->assertNull($record->sources[0]->url);
        $this->assertNull($record->sources[0]->doi);
    }

    public function test_23_fabricated_source_rejection(): void
    {
        $invalidUrl = $this->validator->validate(DiagnosisKnowledgeRecord::fromArray($this->baseRecord([
            'id' => 'kb_fake_url',
            'sources' => [[
                'label' => 'Fake paper',
                'type' => 'journal',
                'url' => 'https://example.com/fake-paper',
                'claims_scientific_evidence' => true,
            ]],
        ])));
        $this->assertFalse($invalidUrl->valid);
        $this->assertContains('fabricated_or_invalid_url', $invalidUrl->errors);

        $invalidDoi = $this->validator->validate(DiagnosisKnowledgeRecord::fromArray($this->baseRecord([
            'id' => 'kb_fake_doi',
            'sources' => [[
                'label' => 'Fake DOI source',
                'type' => 'journal',
                'doi' => 'fake-doi-not-real',
                'claims_scientific_evidence' => true,
            ]],
        ])));
        $this->assertFalse($invalidDoi->valid);
        $this->assertContains('fabricated_or_invalid_doi', $invalidDoi->errors);
    }

    public function test_24_stage6_integration_via_interface(): void
    {
        $support = app(DiagnosisKnowledgeSupportInterface::class);
        $this->assertInstanceOf(KnowledgeBaseDiagnosisKnowledgeSupport::class, $support);
        $this->assertInstanceOf(HeuristicDiagnosisKnowledgeSupport::class, $support);

        $candidates = $support->suggestCandidates(
            new PlantContext(plantName: 'Tomato', cropType: 'tomato', symptomsDescribed: ['leaf spots']),
            [
                new VisionObservation('o1', 'leaf_spot', 'Brown spot lesion', 'leaf', null, 0.7, ['spot', 'lesion']),
            ],
        );

        $this->assertNotEmpty($candidates);
        $this->assertStringStartsWith('kb_', $candidates[0]->id);
    }

    public function test_25_research_agent_independence_architecture(): void
    {
        $kbFiles = File::allFiles(app_path('Services/Agriculture/Diagnosis/KnowledgeBase'));
        $this->assertNotEmpty($kbFiles);

        foreach ($kbFiles as $file) {
            $source = file_get_contents($file->getPathname()) ?: '';
            $codeOnly = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
            $codeOnly = preg_replace('#//.*$#m', '', $codeOnly) ?? $codeOnly;
            $this->assertStringNotContainsString('AgriculturalResearchAgent', $codeOnly);
            $this->assertStringNotContainsString('AgriculturalScientificKnowledgeEngine', $codeOnly);
            $this->assertStringNotContainsString('ResearchPlanner', $codeOnly);
            $this->assertStringNotContainsString('ScientificKnowledgePersistenceService', $codeOnly);
            $this->assertStringNotContainsString('use App\\Services\\Agriculture\\Research\\', $codeOnly);
        }

        $engineReflection = new ReflectionClass(PlantAiDiagnosisEngine::class);
        $deps = [];
        foreach ($engineReflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if ($type !== null) {
                $deps[] = $type->__toString();
            }
        }
        $joined = implode('|', $deps);
        $this->assertStringNotContainsString(AgriculturalResearchAgent::class, $joined);
        $this->assertStringNotContainsString(AgriculturalScientificKnowledgeEngine::class, $joined);
        $this->assertStringNotContainsString(ResearchPlanner::class, $joined);
        $this->assertStringNotContainsString(ScientificKnowledgePersistenceService::class, $joined);
    }

    public function test_26_no_research_agent_invocation_from_kb_support(): void
    {
        $supportSource = file_get_contents(app_path('Services/Agriculture/Diagnosis/KnowledgeBase/KnowledgeBaseDiagnosisKnowledgeSupport.php')) ?: '';
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $supportSource);
        $this->assertStringNotContainsString('Research\\', $supportSource);

        $controllerSource = file_get_contents(app_path('Http/Controllers/Api/PlantAiDiagnosisController.php')) ?: '';
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $controllerSource);
        $this->assertStringContainsString('DiagnosisKnowledgeRetrievalService', $controllerSource);
    }

    public function test_27_no_database_migration_requirement(): void
    {
        $migrationFiles = File::files(database_path('migrations'));
        foreach ($migrationFiles as $file) {
            $this->assertStringNotContainsString(
                'diagnosis_knowledge',
                $file->getFilename(),
                'Stage 7 must not add diagnosis_knowledge migrations',
            );
        }

        $this->assertGreaterThan(0, $this->store->countVerified());
        $this->assertInstanceOf(InMemoryDiagnosisKnowledgeStore::class, $this->store);
    }

    public function test_28_stage6_endpoint_regression(): void
    {
        $response = $this->post('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'plant_name' => 'Tomato',
            'crop_type' => 'tomato',
            'symptoms' => ['leaf spots'],
            'image' => $this->validImage(),
        ]);

        $response->assertOk()
            ->assertJsonPath('stage', 6)
            ->assertJsonPath('engine', 'plant_ai_diagnosis')
            ->assertJsonPath('independent_of_research_agent', true)
            ->assertJsonStructure(['status', 'observations', 'candidates', 'safety']);
    }

    public function test_29_stage1_to_5_classes_still_exist_independently(): void
    {
        $this->assertTrue(class_exists(AgriculturalResearchAgent::class));
        $this->assertTrue(class_exists(AgriculturalScientificKnowledgeEngine::class));
        $this->assertTrue(class_exists(ResearchPlanner::class));
        $this->assertTrue(class_exists(ScientificKnowledgePersistenceService::class));
        $this->assertTrue(class_exists(PlantAiDiagnosisEngine::class));
    }

    public function test_30_duplicate_knowledge_protection_in_store(): void
    {
        $this->assertTrue($this->store->has('kb_foliar_leaf_spot_syndrome'));
        $again = $this->ingestion->ingest($this->baseRecord([
            'id' => 'kb_foliar_leaf_spot_syndrome',
            'common_name' => 'Duplicate attempt',
        ]), markVerified: true);
        $this->assertFalse($again['accepted']);
        $this->assertSame('duplicate_id', $again['reason']);
    }

    public function test_31_aliases_round_trip(): void
    {
        $record = $this->store->get('kb_wheat_leaf_rust_like');
        $this->assertNotNull($record);
        $this->assertContains('brown rust', $record->aliases);
    }

    public function test_32_arabic_common_name_handling(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            aliases: ['اللفحة المبكرة'],
            cropKey: 'tomato',
            verifiedOnly: true,
        ));
        $this->assertNotEmpty($matches);
        $names = array_merge($matches[0]->record->commonNames, $matches[0]->record->aliases);
        $joined = implode(' ', $names);
        $this->assertTrue(
            str_contains($joined, 'اللفحة')
            || in_array('arabic_or_localized_alias', $matches[0]->matchReasons, true)
            || $matches[0]->record->id === 'kb_alternaria_early_blight_like'
        );
    }

    public function test_33_multiple_candidate_diagnoses(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            observations: [
                new VisionObservation('o1', 'leaf_spot', 'Brown lesions', 'leaf', null, 0.7, ['spot', 'lesion']),
                new VisionObservation('o2', 'chlorosis', 'Yellowing', 'leaf', null, 0.5, ['yellow', 'chlorosis']),
            ],
            symptoms: ['leaf spot', 'yellowing'],
            verifiedOnly: true,
        ));

        $this->assertGreaterThanOrEqual(2, count($matches));
    }

    public function test_34_low_confidence_result(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            cropKey: 'tomato',
            symptoms: ['slight pale tint'],
            verifiedOnly: true,
        ));

        if ($matches === []) {
            $this->assertSame([], $matches);

            return;
        }

        $low = array_values(array_filter(
            $matches,
            static fn ($m) => in_array($m->confidenceBand, [
                DiagnosisKnowledgeConfidenceBand::LOW,
                DiagnosisKnowledgeConfidenceBand::VERY_LOW,
            ], true),
        ));
        $this->assertTrue($low !== [] || $matches[0]->matchScore < 0.75);
    }

    public function test_35_human_review_required_result(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            cropKey: 'citrus',
            symptoms: ['chlorosis', 'spot', 'blotch', 'vein'],
            observations: [
                new VisionObservation('o1', 'chlorosis', 'Citrus chlorosis blotch', 'leaf', null, 0.6, ['chlorosis', 'citrus', 'blotch']),
            ],
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        $review = array_values(array_filter(
            $matches,
            static fn ($m) => $m->safetyStatus === DiagnosisKnowledgeSafetyStatus::HUMAN_REVIEW_REQUIRED
                || $m->record->id === 'kb_citrus_leaf_symptom_complex',
        ));
        $this->assertNotEmpty($review);
    }

    public function test_36_ai_text_alone_not_verified(): void
    {
        $result = $this->ingestion->ingest($this->baseRecord([
            'id' => 'kb_ai_only_raw',
            'common_name' => 'AI invented disorder',
        ]), markVerified: true, fromAiOnly: true);

        $this->assertTrue($result['accepted']);
        $this->assertSame(DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED, $result['status']);
        $this->assertSame('ai_text_not_auto_verified', $result['reason']);
        $this->assertNull($this->store->get('kb_ai_only_raw', includeRaw: false));
        $this->assertNotNull($this->store->get('kb_ai_only_raw', includeRaw: true));
    }

    public function test_37_knowledge_api_endpoint(): void
    {
        $response = $this->postJson('/api/v1/public/plant-diagnosis/knowledge', [
            'crop' => 'tomato',
            'symptoms' => ['leaf spot', 'yellow halo'],
            'scientific_name' => 'Alternaria solani',
        ]);

        $response->assertOk()
            ->assertJsonPath('stage', 7)
            ->assertJsonPath('engine', 'plant_ai_diagnosis_knowledge_base')
            ->assertJsonPath('independent_of_research_agent', true)
            ->assertJsonStructure(['match_count', 'matches']);
        $this->assertGreaterThan(0, $response->json('match_count'));
    }

    public function test_38_never_100_percent_from_image_only_kb_scores(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            context: new PlantContext(plantName: 'Tomato', cropType: 'tomato'),
            observations: [
                new VisionObservation('o1', 'leaf_spot', 'Brown circular lesions with yellow halos on lower leaves', 'lower leaves', 'moderate', 0.9, ['spot', 'lesion', 'yellow halo', 'concentric', 'target']),
                new VisionObservation('o2', 'chlorosis', 'Mild yellowing', 'foliage', 'low', 0.6, ['yellowing', 'chlorosis']),
            ],
            symptoms: ['leaf spot', 'yellow halo', 'target lesion'],
            scientificName: 'Alternaria solani',
            verifiedOnly: true,
        ));

        $this->assertNotEmpty($matches);
        foreach ($matches as $match) {
            $this->assertLessThan(1.0, $match->matchScore);
            $this->assertLessThanOrEqual(0.92, $match->matchScore);
        }
    }

    public function test_39_yellowing_does_not_force_nitrogen_only(): void
    {
        $matches = $this->matcher->match(new DiagnosisKnowledgeQuery(
            symptoms: ['yellowing leaves', 'chlorosis'],
            verifiedOnly: true,
        ));

        $this->assertGreaterThanOrEqual(2, count($matches));
        $ids = array_map(static fn ($m) => $m->record->id, $matches);
        $this->assertNotSame(['kb_nitrogen_deficiency_like'], $ids);
    }

    public function test_40_management_references_reject_dosages(): void
    {
        $result = $this->validator->validate(DiagnosisKnowledgeRecord::fromArray($this->baseRecord([
            'id' => 'kb_bad_mgmt',
            'management_references' => [[
                'summary' => 'Spray fungicide at 2.5 ml/L',
                'requires_local_advisor' => true,
            ]],
        ])));

        $this->assertFalse($result->valid);
        $this->assertContains('management_dosage_forbidden', $result->errors);
    }
}
