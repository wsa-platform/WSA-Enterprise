<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Answer-accuracy matrix: structured intent, multi-query, directness, grounded composer.
 */
class ScientificResearchAnswerAccuracyTest extends TestCase
{
    /** A — ginger temperature effect → growth/physiology, not extraction. */
    public function test_matrix_a_ginger_temperature_effect_directness(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertSame('Zingiber officinale', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('plant_growth', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('effect', $understood->constraints['scientific_intent_qualifier'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertGreaterThanOrEqual(2, count($variants));
        $joined = implode(' ', $variants);
        $this->assertStringContainsString('Zingiber officinale', $joined);
        $this->assertTrue(str_contains($joined, 'temperature') || str_contains($joined, 'thermal'));
        $this->assertTrue(
            str_contains($joined, 'growth')
            || str_contains($joined, 'physiology')
            || str_contains($joined, 'cultivation'),
        );

        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $direct = $assessor->assess(
            $plan,
            'Effect of temperature on Zingiber officinale rhizome growth',
            'Temperature regimes affect ginger plant growth and physiology under field cultivation.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);

        $extraction = $assessor->assess(
            $plan,
            'Microwave-assisted extraction of Zingiber officinale at process temperature',
            'MAE extraction yield of ginger oleoresin under temperature optimization.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $extraction['directness']);
    }

    /** B — tomato germination temperature; reject sewage sludge. */
    public function test_matrix_b_tomato_germination_rejects_sludge(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $this->assertSame('tomato', $understood->cropId);
        $this->assertSame('Solanum lycopersicum', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);
        $this->assertContains('germination', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('optimal_range', $understood->constraints['scientific_intent_qualifier'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);

        $direct = $assessor->assess(
            $plan,
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);

        $supporting = $assessor->assess(
            $plan,
            'Tomato growth under different temperatures',
            'Solanum lycopersicum vegetative growth responds to temperature in field crops.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $supporting['directness']);

        // Rhizobacteria / germination without °C or positive optimum → not DIRECT for optimal temp.
        $rhizo = $assessor->assess(
            $plan,
            'Plant growth-promoting rhizobacteria effects on tomato seed germination',
            'Rhizobacteria isolates from Solanum lycopersicum were selected for germination testing without reporting temperature optima or thermal ranges.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $rhizo['directness']);

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Sewage sludge effect on tomato growth',
            'Municipal sewage sludge amendments alter tomato biomass and vegetative canopy development.',
        ));

        $composer = app(AnswerComposer::class);
        $sludgeOnly = $composer->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'sludge',
                'Municipal sewage sludge amendments alter tomato biomass and vegetative canopy development.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Sewage sludge effect on tomato growth',
                    'doi' => '10.1000/sludge',
                    'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                ],
            ),
        ]));
        $this->assertContains($sludgeOnly->status, ['no_validated_evidence', 'insufficient_evidence']);
    }

    /** C — wheat water requirement. */
    public function test_matrix_c_wheat_water_requirement(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما احتياجات القمح من المياه؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('Triticum aestivum', $understood->scientificName);
        $this->assertContains('water', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'ما احتياجات القمح من المياه؟']);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = implode(' ', $variants);
        $this->assertStringContainsString('Triticum aestivum', $joined);
        $this->assertTrue(
            str_contains($joined, 'water')
            || str_contains($joined, 'irrigation')
            || str_contains($joined, 'evapotranspiration'),
        );

        $direct = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Crop water requirement of Triticum aestivum under irrigation',
            'Wheat irrigation requirement and evapotranspiration water use in field production.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    /** D — wheat salinity. */
    public function test_matrix_d_wheat_salinity(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما تأثير الملوحة على القمح؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertContains('salinity', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('salinity_physiology', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'ما تأثير الملوحة على القمح؟']);
        $direct = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Salinity stress effects on Triticum aestivum yield and physiology',
            'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    /** E — ginger drying temperature (processing sense). */
    public function test_matrix_e_ginger_drying_sense(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما تأثير درجة حرارة تجفيف الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('drying', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('drying_processing', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير درجة حرارة تجفيف الزنجبيل؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Drying temperature effects on Zingiber officinale rhizome quality',
            'Hot air drying of ginger at controlled temperature preserves quality.',
        ));
    }

    /** F — ginger storage temperature. */
    public function test_matrix_f_ginger_storage_sense(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أفضل درجة حرارة لتخزين الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('storage', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('storage', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لتخزين الزنجبيل؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Storage temperature for Zingiber officinale rhizomes',
            'Post-harvest storage of ginger under controlled temperature shelf life.',
        ));
    }

    /** G — agricultural economics remains valid. */
    public function test_matrix_g_agricultural_economics(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما الجدوى الاقتصادية لإنتاج القمح؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('agricultural_economics', $understood->researchIntent);
        $this->assertSame('agricultural_economics', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما الجدوى الاقتصادية لإنتاج القمح؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Agricultural economics of wheat production profitability',
            'Farm economics and agricultural economics feasibility of Triticum aestivum production.',
        ));
    }

    /** H — agricultural extension / farmer adoption. */
    public function test_matrix_h_agricultural_extension(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أثر الإرشاد الزراعي على تبني المزارعين لتقنيات الري؟',
        ]);
        $this->assertSame('agricultural_extension', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أثر الإرشاد الزراعي على تبني المزارعين لتقنيات الري؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Agricultural extension effects on farmer adoption of irrigation technologies',
            'Farmer behavior and agricultural extension adoption of irrigation practices.',
        ));
    }

    /** I — unrelated psych/econ blocked on crop physiology. */
    public function test_matrix_i_unrelated_psych_econ_blocked(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Ginger psychology and consumer anxiety disorder therapy',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Ginger market economics and stock market volatility',
            'Economics of ginger commodity trading and stock market pricing.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Effect of ginger and organic selenium on broiler chickens under high ambient temperature',
            'Ginger at 5 g/kg improved growth performance in broiler chickens exposed to high ambient temperature.',
        ));
    }

    public function test_adversarial_wrong_topic_wrong_crop_wrong_sense(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $matcher = app(ClaimEvidenceMatcher::class);

        // Entity correct, topic wrong
        $wrongTopic = $assessor->assess(
            $plan,
            'Potassium deficiency in Solanum lycopersicum leaves',
            'Tomato potassium nutrition and leaf chlorosis under greenhouse fertigation.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $wrongTopic['directness']);

        // Topic correct, crop wrong
        $wrongCrop = $assessor->assess(
            $plan,
            'Effect of temperature on wheat seed germination',
            'Triticum aestivum seed germination under temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $wrongCrop['directness']);

        // Entity+topic present but sense wrong (growth vs germination)
        $wrongSense = $assessor->assess(
            $plan,
            'Tomato growth under different temperatures',
            'Solanum lycopersicum vegetative growth responds to temperature in agriculture.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $wrongSense['directness']);

        // Incidental keyword overlap
        $incidental = new ScientificSearchResult(
            'openalex', 'W-inc', 'Laboratory apparatus temperature logging near tomato samples',
            ['A'], 2021, '10.1000/inc', 'https://doi.org/10.1000/inc',
            'Instrument thermal sensor readings near tomato tissue in a laboratory reactor.',
            'Journal', ['openalex'],
        );
        $match = $matcher->match(
            $plan,
            $incidental,
            'Instrument thermal sensor readings near tomato tissue in a laboratory reactor.',
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_adversarial_sole_background_not_composed_as_answer(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $composer = app(AnswerComposer::class);

        $background = $this->usableEvidence(
            'bg',
            'Tomato fields occur in many climates; sewage sludge also affects tomato biomass at 25 C.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Sewage sludge effect on tomato growth at ambient temperature',
                'doi' => '10.1000/bg-tomato',
                'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ],
        );

        $report = $composer->compose($plan, $this->validationReport([$background]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertStringNotContainsString('25', $report->answer);
        $this->assertStringNotContainsString('sewage', mb_strtolower($report->answer));
    }

    public function test_adversarial_direct_preferred_over_supporting_for_summary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $composer = app(AnswerComposer::class);

        $supporting = $this->usableEvidence(
            'sup',
            'Tomato vegetative growth increases under warm temperature regimes in field agriculture.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Tomato growth under different temperatures',
                'doi' => '10.1000/sup-growth',
                'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
        );
        $direct = $this->usableEvidence(
            'dir',
            'Solanum lycopersicum seed germination is optimal near 25 C under controlled temperature regimes.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Effect of temperature on tomato seed germination',
                'doi' => '10.1000/dir-germ',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );

        $report = $composer->compose($plan, $this->validationReport([$supporting, $direct]));
        $this->assertNotSame('no_validated_evidence', $report->status);
        $this->assertNotSame('insufficient_evidence', $report->status);
        $this->assertStringContainsString('germination', mb_strtolower($report->answer));
        $this->assertStringContainsString('25', $report->answer);
    }

    public function test_multi_query_variants_executed_shape(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertGreaterThanOrEqual(2, count($variants));
        $this->assertLessThanOrEqual(5, count($variants));
        $this->assertSame($variants[0], app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan));
    }

    /** Weak secondary polarity must not wipe DIRECT tomato heat findings into conflict boilerplate. */
    public function test_tomato_heat_direct_surfaces_despite_secondary_conflicts(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة المرتفعة على زراعة الطماطم في الأراضي المكشوفة؟',
        ]);
        $detector = app(\App\Services\Agriculture\Research\Validation\EvidenceConflictDetector::class);
        $composer = app(AnswerComposer::class);

        $direct = $this->usableEvidence(
            'tomato-heat-direct',
            'High temperature stress reduces Solanum lycopersicum pollen viability and open-field tomato fruit set under heat regimes.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'High temperature effects on tomato growth and fruit set',
                'doi' => '10.1000/tomato-heat-direct',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'claimTopic' => 'temperature tomato',
            ],
        );
        $weakSecondary = $this->usableEvidence(
            'tomato-weak',
            'Tomato canopy may increase slightly under warm nights in some greenhouse trials.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Tomato canopy warm night notes',
                'doi' => '10.1000/tomato-weak',
                'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'claimTopic' => 'temperature tomato',
            ],
        );

        $afterConflict = $detector->detect([$direct, $weakSecondary]);
        $directAfter = $afterConflict[0];
        $this->assertFalse($directAfter->hasConflict, 'DIRECT must not be wiped by weak secondary polarity');
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $directAfter->claimRelationship);

        $report = $composer->compose($plan, $this->validationReport($afterConflict));
        $this->assertNotSame('insufficient_evidence', $report->status);
        $this->assertNotEmpty($report->keyFindings);
        $this->assertStringNotContainsString(
            'Limited or conflicting scientific evidence',
            $report->conciseSummary,
        );
        $this->assertStringNotContainsString(
            'تتوفر أدلة علمية محدودة أو متعارضة',
            $report->conciseSummary,
        );
        $answerLower = mb_strtolower($report->answer);
        $this->assertTrue(
            str_contains($answerLower, 'temperature')
            || str_contains($answerLower, 'pollen')
            || str_contains($answerLower, 'heat')
            || str_contains($answerLower, 'tomato'),
        );
    }

    /** Ginger temperature/growth remains relevant and synthesizable as DIRECT. */
    public function test_ginger_temperature_growth_direct_composed(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير ارتفاع درجة الحرارة على نمو الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $plan->normalizedQuery->cropId);
        $this->assertContains('temperature', $plan->normalizedQuery->constraints['scientific_factors'] ?? []);

        $composer = app(AnswerComposer::class);
        $direct = $this->usableEvidence(
            'ginger-temp',
            'Elevated temperature regimes reduce Zingiber officinale rhizome growth and plant physiology under field cultivation.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Effect of elevated temperature on Zingiber officinale growth',
                'doi' => '10.1000/ginger-temp-growth',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );
        $report = $composer->compose($plan, $this->validationReport([$direct]));
        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertStringContainsString('Zingiber', $report->answer);
        $this->assertNotEmpty($report->citations);
    }

    /** Incidental economics wording must not reject agriculturally relevant ginger evidence. */
    public function test_incidental_economics_does_not_reject_ag_evidence(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير ارتفاع درجة الحرارة على نمو الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $matcher = app(ClaimEvidenceMatcher::class);

        $title = 'Rhizome development of Zingiber officinale under temperature and daylength';
        $abstract = 'Ginger rhizome growth responds to temperature under field crop conditions. Further investigation of the economics of applying extended daylength is warranted.';
        $this->assertTrue($gate->isRelevant($plan, $title, $abstract));

        $result = new ScientificSearchResult(
            'openalex', 'W-inc-econ', $title, ['A'], 2020,
            '10.1000/ginger-inc-econ', 'https://doi.org/10.1000/ginger-inc-econ',
            $abstract, 'Journal', ['openalex'],
        );
        $match = $matcher->match($plan, $result, $abstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    /** Ranking must never apply country filters; Peer Review #N titles are demoted. */
    public function test_ranker_demotes_peer_review_noise_without_country_filter(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $ranker = app(\App\Services\Agriculture\Research\Search\ScientificResultRanker::class);

        $primary = new ScientificSearchResult(
            'openalex', 'W-primary', 'Effect of temperature on tomato seed germination',
            ['A'], 2022, '10.1000/tomato-germ-primary', 'https://doi.org/10.1000/tomato-germ-primary',
            'Solanum lycopersicum seed germination optimum is near 25 °C under controlled temperature regimes.',
            'Journal of Seed Science', ['openalex'],
            rawMetadata: ['openalex' => ['authorships' => [['institutions' => [['display_name' => 'Cairo University', 'country_code' => 'EG']]]]]],
        );
        $peerReview = new ScientificSearchResult(
            'openalex', 'W-pr', 'Peer Review #3 of Effect of temperature on tomato seed germination',
            ['B'], 2022, '10.1000/tomato-germ-pr', 'https://doi.org/10.1000/tomato-germ-pr',
            'Solanum lycopersicum seed germination optimum is near 25 °C under controlled temperature regimes.',
            'Journal of Seed Science', ['openalex'],
            rawMetadata: ['openalex' => ['authorships' => [['institutions' => [['display_name' => 'Wageningen University', 'country_code' => 'NL']]]]]],
        );
        $foreignPrimary = new ScientificSearchResult(
            'openalex', 'W-nl', 'Temperature regimes for Solanum lycopersicum seed germination',
            ['C'], 2021, '10.1000/tomato-germ-nl', 'https://doi.org/10.1000/tomato-germ-nl',
            'Tomato seed germination under temperature regimes in agricultural trials.',
            'Seed Science Research', ['openalex'],
            rawMetadata: ['openalex' => ['authorships' => [['institutions' => [['display_name' => 'Wageningen University', 'country_code' => 'NL']]]]]],
        );

        $ranked = $ranker->rank('Solanum lycopersicum temperature germination', [$peerReview, $primary, $foreignPrimary], $plan);
        $this->assertSame('W-primary', $ranked[0]->sourceIdentifier);
        $peer = null;
        foreach ($ranked as $item) {
            if ($item->sourceIdentifier === 'W-pr') {
                $peer = $item;
            }
        }
        $this->assertNotNull($peer);
        $this->assertTrue((bool) ($peer->relevanceMetadata['peer_review_noise'] ?? false));
        $this->assertLessThan($ranked[0]->relevanceScore ?? 0.0, $peer->relevanceScore ?? 0.0);

        $filtered = $ranker->filterRelevant($ranked);
        $this->assertTrue(collect($filtered)->contains(fn ($r) => $r->sourceIdentifier === 'W-primary'));
        $this->assertFalse(
            collect($filtered)->contains(fn ($r) => $r->sourceIdentifier === 'W-pr'),
            'Peer Review #N wrappers must be dropped when primary literature remains',
        );

        $source = (string) file_get_contents(base_path('app/Services/Agriculture/Research/Search/ScientificResultRanker.php'));
        $this->assertStringNotContainsString('country_filter', $source);
        $this->assertStringNotContainsString('excludeCountry', $source);
        $this->assertTrue(
            collect($ranked)->contains(fn ($r) => $r->sourceIdentifier === 'W-nl'),
            'Non-local country sources must remain eligible (no country exclusion)',
        );
    }

    /** Genuine strong positive-vs-negative contradiction remains CONFLICTING. */
    public function test_genuine_contradiction_still_marked_conflicting(): void
    {
        $detector = app(\App\Services\Agriculture\Research\Validation\EvidenceConflictDetector::class);
        $a = $this->usableEvidence(
            'irr-pos',
            'Improve irrigation scheduling to increase wheat yield and enhance water productivity.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Improve irrigation for wheat',
                'doi' => '10.1000/irr-pos',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'claimTopic' => 'irrigation wheat',
            ],
        );
        $b = $this->usableEvidence(
            'irr-neg',
            'Reduce irrigation scheduling to decrease waterlogging risk and avoid harm to wheat roots.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Reduce irrigation for wheat',
                'doi' => '10.1000/irr-neg',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'claimTopic' => 'irrigation wheat',
            ],
        );

        $detected = $detector->detect([$a, $b]);
        $this->assertTrue($detected[0]->hasConflict);
        $this->assertTrue($detected[1]->hasConflict);
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $detected[0]->claimRelationship);
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $detected[1]->claimRelationship);
    }

    /** Genuine insufficient evidence yields an explicit insufficient message (not conflict boilerplate). */
    public function test_insufficient_evidence_explicit_message(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $composer = app(AnswerComposer::class);
        $background = $this->usableEvidence(
            'bg-only',
            'Tomato fields occur in many climates; sewage sludge also affects tomato biomass at ambient temperature.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Sewage sludge effect on tomato growth at ambient temperature',
                'doi' => '10.1000/bg-insuff',
                'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ],
        );
        $report = $composer->compose($plan, $this->validationReport([$background]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertTrue(
            str_contains(mb_strtolower($report->answer), 'insufficient')
            || str_contains($report->answer, 'غير كافية')
            || str_contains(mb_strtolower($report->answer), 'not sufficiently relevant'),
        );
        $this->assertStringNotContainsString('Limited or conflicting scientific evidence', $report->answer);
    }

    /** Answerability: tomato optimal germination temp is DIRECT only with °C/optimum signals. */
    public function test_answerability_tomato_optimal_temp_direct_requires_celsius(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي درجة الحرارة المناسبة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);

        $withCelsius = $assessor->assess(
            $plan,
            'Optimal temperature for tomato seed germination',
            'Solanum lycopersicum seed germination optimum is 20-25 °C under controlled regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $withCelsius['directness']);

        $withoutAnswerability = $assessor->assess(
            $plan,
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination rate under controlled temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $withoutAnswerability['directness']);
    }

    /** Answerability: rhizobacteria germination papers must not become DIRECT germination-temp answers. */
    public function test_answerability_rejects_rhizobacteria_as_germination_temp(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي درجة الحرارة المناسبة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $matcher = app(ClaimEvidenceMatcher::class);

        $title = 'Plant growth-promoting rhizobacteria effects on tomato seed germination';
        $abstract = 'Plant growth-promoting rhizobacteria colonize Solanum lycopersicum roots and contribute to seed germination via IAA production without reporting temperature optima or °C ranges.';
        $directness = $assessor->assess($plan, $title, $abstract);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $directness['directness']);

        $result = new ScientificSearchResult(
            'openalex', 'W-rhizo', $title, ['A'], 2021, '10.1000/rhizo-germ',
            'https://doi.org/10.1000/rhizo-germ', $abstract, 'Journal', ['openalex'],
        );
        $match = $matcher->match($plan, $result, $abstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
    }

    /** Answerability: hydroponics / الزراعة المائية is understood as production system. */
    public function test_answerability_hydroponics_understanding(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما فوائد الزراعة المائية hydroponics؟',
        ]);
        $this->assertSame('hydroponics', $understood->constraints['production_system'] ?? null);
        $this->assertContains('hydroponics', $understood->constraints['scientific_topics'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما فوائد الزراعة المائية hydroponics؟',
        ]);
        $variants = implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan));
        $this->assertTrue(
            str_contains(mb_strtolower($variants), 'hydroponic'),
            'Query variants must include hydroponics, not only polyhouse/greenhouse fluff',
        );
        $this->assertStringNotContainsString('polyhouse', mb_strtolower($variants));
    }

    /** Answerability: Arabic مصر maps to Egypt for aquaculture geo questions only. */
    public function test_answerability_egypt_fish_location_without_country_filter(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما واقع استزراع الأسماك في مصر؟',
        ]);
        $this->assertSame('Egypt', $understood->location);
        $this->assertSame('Egypt', $understood->constraints['location'] ?? null);
        $this->assertSame('aquaculture', $understood->researchIntent);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما واقع استزراع الأسماك في مصر؟',
        ]);
        $variants = implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan));
        $this->assertStringContainsString('Egypt', $variants);

        // Non-geo crop question must not inject Egypt/Libya into variants.
        $tomatoPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $tomatoVariants = implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($tomatoPlan));
        $this->assertStringNotContainsString('Egypt', $tomatoVariants);
        $this->assertStringNotContainsString('Libya', $tomatoVariants);
    }

    /** Answerability: ginger cultivation remains synthesizable. */
    public function test_answerability_ginger_cultivation_still_ok(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $plan->normalizedQuery->cropId);
        $variants = implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan));
        $this->assertTrue(
            str_contains($variants, 'Zingiber') || str_contains(mb_strtolower($variants), 'ginger'),
        );
        $this->assertTrue(
            str_contains(mb_strtolower($variants), 'cultivation')
            || str_contains(mb_strtolower($variants), 'production'),
        );

        $composer = app(AnswerComposer::class);
        $direct = $this->usableEvidence(
            'ginger-cult',
            'Zingiber officinale cultivation requires warm moist conditions and well-drained soils for rhizome production.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Cultivation requirements of Zingiber officinale',
                'doi' => '10.1000/ginger-cultivation',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );
        $report = $composer->compose($plan, $this->validationReport([$direct]));
        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertStringContainsString('Zingiber', $report->answer);
    }

    /** EvidenceQualityRanker prefers DIRECT topical usefulness over bare peer-review authority. */
    public function test_answerability_quality_ranker_prefers_direct_over_authority(): void
    {
        $ranker = app(\App\Services\Agriculture\Research\Validation\EvidenceQualityRanker::class);
        $direct = $ranker->score(
            ['fields' => ['title' => true, 'abstract' => true]],
            ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_SUPPORTING],
            [
                'relationship' => ClaimEvidenceRelationship::SUPPORTED,
                'confidence' => 0.8,
                'factors' => [
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'match_ratio' => 0.5,
                    'synonym_support' => true,
                ],
            ],
            2022,
        );
        $peerOnly = $ranker->score(
            ['fields' => ['title' => true, 'abstract' => true]],
            ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_PEER_REVIEWED],
            [
                'relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                'confidence' => 0.1,
                'factors' => [
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                    'match_ratio' => 0.05,
                ],
            ],
            2022,
        );
        $this->assertGreaterThan($peerOnly['score'], $direct['score']);
        $this->assertTrue($direct['factors']['no_geo_preference'] ?? false);
    }

    /**
     * Residual A — temperature/optimal/germination snippets must lead with temperature answers,
     * not secondary yield/oil/biomass metrics when temperature evidence exists.
     */
    public function test_residual_a_snippet_prefers_temperature_over_secondary_metrics(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير ارتفاع درجة الحرارة على نمو الزنجبيل؟',
        ]);
        $this->assertContains('temperature', $plan->normalizedQuery->constraints['scientific_factors'] ?? []);

        $composer = app(AnswerComposer::class);
        $mixed = $this->usableEvidence(
            'ginger-temp-oil-mix',
            'Essential oil yield and biomass productivity of Zingiber officinale increased under warm nights. '
            .'Elevated temperature regimes reduce Zingiber officinale rhizome growth near 35 °C under field cultivation.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Temperature effects on Zingiber officinale growth and oil yield',
                'doi' => '10.1000/ginger-temp-oil-mix',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );

        $report = $composer->compose($plan, $this->validationReport([$mixed]));
        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertNotEmpty($report->keyFindings);
        $lead = mb_strtolower((string) $report->keyFindings[0]);
        $this->assertTrue(
            str_contains($lead, 'temperature')
            || str_contains($lead, '35')
            || str_contains($lead, 'growth'),
            'Key finding must lead with temperature/growth answer, not oil/biomass',
        );
        $this->assertFalse(
            str_starts_with($lead, 'essential oil')
            || (str_contains($lead, 'oil yield') && ! str_contains($lead, 'temperature') && ! str_contains($lead, '35')),
            'Must not lead with secondary oil/yield metrics when temperature evidence exists',
        );
        $answerLower = mb_strtolower($report->answer);
        $tempPos = min(
            array_filter([
                strpos($answerLower, '35') !== false ? strpos($answerLower, '35') : PHP_INT_MAX,
                strpos($answerLower, 'temperature') !== false ? strpos($answerLower, 'temperature') : PHP_INT_MAX,
                strpos($answerLower, 'growth') !== false ? strpos($answerLower, 'growth') : PHP_INT_MAX,
            ]),
        );
        $oilPos = strpos($answerLower, 'oil yield');
        if ($oilPos !== false) {
            $this->assertLessThan($oilPos, $tempPos, 'Temperature answer must appear before oil-yield framing');
        }
    }

    /**
     * Residual D — tomato germination must prefer germination/temperature evidence and demote
     * essential-oil papers as primary unless the user asked about oils.
     */
    public function test_residual_d_germination_demotes_essential_oil_primary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $this->assertSame('seed_germination', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);

        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $matcher = app(ClaimEvidenceMatcher::class);
        $ranker = app(\App\Services\Agriculture\Research\Search\ScientificResultRanker::class);
        $qualityRanker = app(\App\Services\Agriculture\Research\Validation\EvidenceQualityRanker::class);
        $composer = app(AnswerComposer::class);

        $germTitle = 'Effect of temperature on tomato seed germination';
        $germAbstract = 'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes with measured germination rate and percentage.';
        $oilTitle = 'Essential oil composition and oil yield of Solanum lycopersicum under temperature treatments';
        $oilAbstract = 'Volatile oil and essential-oil composition of tomato leaves changed with temperature; oil yield increased without reporting seed germination temperature requirements.';

        $germDirect = $assessor->assess($plan, $germTitle, $germAbstract);
        $oilDirect = $assessor->assess($plan, $oilTitle, $oilAbstract);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $germDirect['directness']);
        $this->assertNotSame(
            ScientificEvidenceDirectnessAssessor::DIRECT,
            $oilDirect['directness'],
            'Essential-oil primary must not be DIRECT for germination questions',
        );

        $germResult = new ScientificSearchResult(
            'openalex', 'W-germ', $germTitle, ['A'], 2022,
            '10.1000/tomato-germ-temp', 'https://doi.org/10.1000/tomato-germ-temp',
            $germAbstract, 'Seed Science', ['openalex'],
        );
        $oilResult = new ScientificSearchResult(
            'openalex', 'W-oil', $oilTitle, ['B'], 2023,
            '10.1000/tomato-oil', 'https://doi.org/10.1000/tomato-oil',
            $oilAbstract, 'Essential Oil Journal', ['openalex'],
        );

        $germMatch = $matcher->match($plan, $germResult, $germAbstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $oilMatch = $matcher->match($plan, $oilResult, $oilAbstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $this->assertTrue($germMatch['factors']['germination_evidence_preferred'] ?? false);
        $this->assertTrue($oilMatch['factors']['essential_oil_primary_demotion'] ?? false);
        $this->assertGreaterThan(0.0, $oilMatch['confidence']);
        $this->assertGreaterThan($oilMatch['confidence'], $germMatch['confidence']);

        $germQuality = $qualityRanker->score(
            ['fields' => ['title' => true, 'abstract' => true]],
            ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_PEER_REVIEWED],
            $germMatch,
            2022,
        );
        $oilQuality = $qualityRanker->score(
            ['fields' => ['title' => true, 'abstract' => true]],
            ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_PEER_REVIEWED],
            $oilMatch,
            2023,
        );
        $this->assertGreaterThan(-0.1, $oilQuality['score']);
        $this->assertGreaterThan($oilQuality['score'], $germQuality['score']);

        $ranked = $ranker->rank('Solanum lycopersicum temperature germination', [$oilResult, $germResult], $plan);
        $this->assertSame('W-germ', $ranked[0]->sourceIdentifier);

        $report = $composer->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'oil-primary',
                $oilAbstract,
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => $oilTitle,
                    'doi' => '10.1000/tomato-oil-compose',
                    'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                ],
            ),
            $this->usableEvidence(
                'germ-primary',
                $germAbstract,
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => $germTitle,
                    'doi' => '10.1000/tomato-germ-compose',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                ],
            ),
        ]));
        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $lead = mb_strtolower((string) ($report->keyFindings[0] ?? $report->answer));
        $this->assertTrue(
            str_contains($lead, 'germination') || str_contains($lead, '25'),
            'Primary finding must be germination/temperature evidence',
        );
        $this->assertStringNotContainsString('essential oil', $lead);
        $this->assertStringNotContainsString('oil yield', $lead);
    }

    /**
     * Residual — ginger growth/heat must demote essential-oil papers as primary when
     * growth/temperature DIRECT evidence exists; oils questions still allow oil evidence;
     * germination demotion must not leak into growth-only ranking incorrectly.
     */
    public function test_residual_ginger_growth_heat_demotes_essential_oil_primary(): void
    {
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $matcher = app(ClaimEvidenceMatcher::class);
        $qualityRanker = app(\App\Services\Agriculture\Research\Validation\EvidenceQualityRanker::class);
        $composer = app(AnswerComposer::class);

        $growthTitle = 'Effect of elevated temperature on Zingiber officinale rhizome growth';
        $growthAbstract = 'Elevated temperature regimes reduce Zingiber officinale rhizome growth and plant physiology near 35 °C under field cultivation.';
        $oilTitle = 'Essential oil composition and oil yield of Zingiber officinale under temperature treatments';
        $oilAbstract = 'Volatile oil and essential-oil composition of ginger rhizomes changed with temperature; oil yield increased at warm nights without reporting plant growth or rhizome biomass optima.';
        $germTitle = 'Effect of temperature on ginger seed germination';
        $germAbstract = 'Zingiber officinale seed germination is optimal near 25 °C under controlled temperature regimes with measured germination rate.';

        // B/C — growth + heat/temperature: oil must not be DIRECT primary beside growth DIRECT.
        foreach ([
            'ما تأثير ارتفاع درجة الحرارة على نمو الزنجبيل؟',
            'ما تأثير درجة الحرارة على نمو الزنجبيل؟',
        ] as $growthQuery) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $growthQuery]);
            $this->assertSame('plant_growth', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);
            $this->assertContains('temperature', $plan->normalizedQuery->constraints['scientific_factors'] ?? []);

            $growthDirect = $assessor->assess($plan, $growthTitle, $growthAbstract);
            $oilDirect = $assessor->assess($plan, $oilTitle, $oilAbstract);
            $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $growthDirect['directness']);
            $this->assertNotSame(
                ScientificEvidenceDirectnessAssessor::DIRECT,
                $oilDirect['directness'],
                'Essential-oil primary must not be DIRECT for growth/heat questions',
            );

            $growthResult = new ScientificSearchResult(
                'openalex', 'W-growth', $growthTitle, ['A'], 2022,
                '10.1000/ginger-growth', 'https://doi.org/10.1000/ginger-growth',
                $growthAbstract, 'Crop Sci', ['openalex'],
            );
            $oilResult = new ScientificSearchResult(
                'openalex', 'W-oil', $oilTitle, ['B'], 2023,
                '10.1000/ginger-oil', 'https://doi.org/10.1000/ginger-oil',
                $oilAbstract, 'Essential Oil Journal', ['openalex'],
            );

            $growthMatch = $matcher->match($plan, $growthResult, $growthAbstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
            $oilMatch = $matcher->match($plan, $oilResult, $oilAbstract, EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
            $this->assertTrue($growthMatch['factors']['growth_evidence_preferred'] ?? false);
            $this->assertTrue($oilMatch['factors']['essential_oil_primary_demotion'] ?? false);
            $this->assertFalse($oilMatch['factors']['germination_evidence_preferred'] ?? false);

            $growthQuality = $qualityRanker->score(
                ['fields' => ['title' => true, 'abstract' => true]],
                ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_PEER_REVIEWED],
                $growthMatch,
                2022,
            );
            $oilQuality = $qualityRanker->score(
                ['fields' => ['title' => true, 'abstract' => true]],
                ['confidence_level' => \App\Services\Agriculture\ScientificSourceRegistry::LEVEL_PEER_REVIEWED],
                $oilMatch,
                2023,
            );
            $this->assertGreaterThan($oilQuality['score'], $growthQuality['score']);

            $report = $composer->compose($plan, $this->validationReport([
                $this->usableEvidence(
                    'oil-primary',
                    $oilAbstract,
                    ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                    [
                        'publicationTitle' => $oilTitle,
                        'doi' => '10.1000/ginger-oil-compose',
                        'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                    ],
                ),
                $this->usableEvidence(
                    'growth-primary',
                    $growthAbstract,
                    ClaimEvidenceRelationship::SUPPORTED,
                    [
                        'publicationTitle' => $growthTitle,
                        'doi' => '10.1000/ginger-growth-compose',
                        'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    ],
                ),
            ]));
            $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
            $lead = mb_strtolower((string) ($report->keyFindings[0] ?? $report->answer));
            $this->assertTrue(
                str_contains($lead, 'growth') || str_contains($lead, '35') || str_contains($lead, 'temperature'),
                'Primary finding must be growth/temperature evidence',
            );
            $this->assertStringNotContainsString('essential oil', $lead);
            $this->assertStringNotContainsString('oil yield', $lead);
        }

        // D — oils question must still allow essential-oil evidence (no germination demotion leak).
        $oilsPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي الزيوت العطرية والمركبات في الزنجبيل؟',
        ]);
        $oilsDirect = $assessor->assess($oilsPlan, $oilTitle, $oilAbstract);
        $this->assertNotContains(
            'essential_oil_primary_demoted_for_germination',
            $oilsDirect['reasons'],
        );
        $this->assertNotContains(
            'essential_oil_off_topic_for_germination',
            $oilsDirect['reasons'],
        );
        $this->assertNotContains(
            'essential_oil_primary_demoted_for_growth',
            $oilsDirect['reasons'],
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $oilsDirect['directness']);

        // A/E — germination still demotes oil; growth demotion must not force oil DIRECT on germ queries.
        foreach ([
            'ما أفضل درجة حرارة لإنبات بذور الزنجبيل؟',
            'ما شروط إنبات الزنجبيل؟',
        ] as $germQuery) {
            $germPlan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $germQuery]);
            $this->assertSame('seed_germination', $germPlan->normalizedQuery->constraints['scientific_sense'] ?? null);
            $germOil = $assessor->assess($germPlan, $oilTitle, $oilAbstract);
            $germOk = $assessor->assess($germPlan, $germTitle, $germAbstract);
            $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $germOk['directness']);
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $germOil['directness']);
        }
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $items !== [],
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function usableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        array $overrides = [],
    ): ScientificEvidenceItem {
        $directness = $overrides['directness'] ?? ScientificEvidenceDirectnessAssessor::DIRECT;

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: (string) ($overrides['sourceId'] ?? 'source-'.$evidenceId),
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: array_key_exists('doi', $overrides) ? $overrides['doi'] : '10.1000/'.$evidenceId,
            url: array_key_exists('url', $overrides) ? $overrides['url'] : 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: (string) ($overrides['claimTopic'] ?? 'topic'),
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => $directness,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => $directness,
            ],
        );
    }
}
