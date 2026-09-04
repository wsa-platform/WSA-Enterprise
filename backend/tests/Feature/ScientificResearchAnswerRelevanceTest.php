<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
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
 * Scientific answer relevance: multilingual crop resolution, topic factors,
 * controlled query construction, relevance gate, and composer filtering.
 */
class ScientificResearchAnswerRelevanceTest extends TestCase
{
    public function test1_arabic_ginger_temperature_entity_and_rejects_psychology_economics(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);

        $this->assertSame('ginger', $understood->cropId);
        $this->assertSame('Zingiber officinale', $understood->scientificName);
        $this->assertSame('environmental_requirements', $understood->researchIntent);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);

        $this->assertStringContainsString('Zingiber officinale', $searchQuery);
        $this->assertStringContainsString('temperature', $searchQuery);
        $this->assertStringNotContainsString('الزنجبيل', $searchQuery);
        $this->assertStringNotContainsString('درجة الحرارة', $searchQuery);

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $relevant = $gate->assess(
            $plan,
            'Optimal temperature requirements for Zingiber officinale cultivation',
            'Ginger rhizome growth responds to temperature under agricultural field conditions.',
            '10.1000/ginger-temp',
        );
        $this->assertTrue($relevant['relevant']);
        $this->assertTrue($relevant['entity_matched']);
        $this->assertTrue($relevant['topic_matched']);

        $psych = $gate->assess(
            $plan,
            'Ginger psychology and consumer anxiety disorder therapy',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
            '10.1000/ginger-psych',
        );
        $this->assertFalse($psych['relevant']);

        $econ = $gate->assess(
            $plan,
            'Ginger market economics and stock market volatility',
            'Economics of ginger commodity trading and stock market pricing.',
            '10.1000/ginger-econ',
        );
        $this->assertFalse($econ['relevant']);

        $matcher = app(ClaimEvidenceMatcher::class);
        $psychResult = new ScientificSearchResult(
            'openalex', 'W-psych', 'Ginger psychology and consumer anxiety disorder therapy', ['A'], 2022,
            '10.1000/ginger-psych', 'https://doi.org/10.1000/ginger-psych',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
            'Journal', ['openalex'],
        );
        $psychMatch = $matcher->match(
            $plan,
            $psychResult,
            'Psychological effects of ginger aroma on anxiety disorder patients.',
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $psychMatch['relationship']);

        $composer = app(AnswerComposer::class);
        $psychEvidence = $this->usableEvidence(
            'psych',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Ginger psychology and consumer anxiety disorder therapy',
                'doi' => '10.1000/ginger-psych',
            ],
        );
        $relevantEvidence = $this->usableEvidence(
            'temp',
            'Zingiber officinale grows best under warm temperature regimes in tropical agriculture.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Optimal temperature requirements for Zingiber officinale cultivation',
                'doi' => '10.1000/ginger-temp',
            ],
        );

        $psychOnly = $composer->compose($plan, $this->validationReport([$psychEvidence]));
        $this->assertSame('no_validated_evidence', $psychOnly->status);
        $this->assertStringContainsString('أدلة', $psychOnly->answer);

        $withRelevant = $composer->compose($plan, $this->validationReport([$psychEvidence, $relevantEvidence]));
        $this->assertNotSame('no_validated_evidence', $withRelevant->status);
        $this->assertStringContainsString('Zingiber', $withRelevant->answer);
        $this->assertStringNotContainsString('anxiety', mb_strtolower($withRelevant->answer));
    }

    public function test2_salinity_wheat_arabic(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'تأثير الملوحة على القمح',
        ]);

        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('Triticum aestivum', $understood->scientificName);
        $this->assertContains('salinity', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('environmental_requirements', $understood->researchIntent);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'تأثير الملوحة على القمح']);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertStringContainsString('Triticum aestivum', $searchQuery);
        $this->assertStringContainsString('salinity', $searchQuery);

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertTrue($gate->isRelevant(
            $plan,
            'Salinity tolerance of Triticum aestivum under irrigation',
            'Wheat salinity stress reduces yield.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Salinity effects on tomato fruit quality',
            'Tomato salinity stress physiology.',
        ));
    }

    public function test3_corn_water_arabic(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'احتياج الذرة من الماء',
        ]);

        $this->assertSame('corn', $understood->cropId);
        $this->assertSame('Zea mays', $understood->scientificName);
        $this->assertContains('water', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('irrigation', $understood->researchIntent);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'احتياج الذرة من الماء']);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertStringContainsString('Zea mays', $searchQuery);
        $this->assertStringContainsString('water', $searchQuery);
    }

    public function test4_tomato_temperature_and_germination(): void
    {
        $temp = app(QueryUnderstandingService::class)->understand([
            'query' => 'درجة حرارة إنبات الطماطم',
        ]);
        $this->assertSame('tomato', $temp->cropId);
        $this->assertSame('Solanum lycopersicum', $temp->scientificName);
        $this->assertContains('temperature', $temp->constraints['scientific_factors'] ?? []);
        $this->assertContains('germination', $temp->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'درجة حرارة إنبات الطماطم']);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertStringContainsString('Solanum lycopersicum', $searchQuery);
        $this->assertTrue(
            str_contains($searchQuery, 'temperature') || str_contains($searchQuery, 'germination'),
        );

        $pepper = app(QueryUnderstandingService::class)->understand([
            'query' => 'إنبات الفلفل',
        ]);
        $this->assertSame('pepper', $pepper->cropId);
        $this->assertContains('germination', $pepper->constraints['scientific_factors'] ?? []);
        $this->assertSame('cultivation', $pepper->researchIntent);
    }

    public function test5_english_ginger_temperature_equivalent(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'What is the optimal temperature for ginger cultivation?',
        ]);

        $this->assertSame('ginger', $understood->cropId);
        $this->assertSame('Zingiber officinale', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('environmental_requirements', $understood->researchIntent);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the optimal temperature for ginger cultivation?',
        ]);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertStringContainsString('Zingiber officinale', $searchQuery);
        $this->assertStringContainsString('temperature', $searchQuery);
    }

    public function test6_mixed_arabic_english_ginger_temperature(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'الزنجبيل temperature requirement',
        ]);

        $this->assertSame('ginger', $understood->cropId);
        $this->assertSame('Zingiber officinale', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'الزنجبيل temperature requirement',
        ]);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertStringContainsString('Zingiber officinale', $searchQuery);
        $this->assertStringContainsString('temperature', $searchQuery);
        $this->assertStringNotContainsString('الزنجبيل', $searchQuery);
    }

    public function test_general_query_without_crop_still_works(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'general farming practices for smallholders',
        ]);

        $this->assertNull($understood->cropId);
        $this->assertSame('general_knowledge', $understood->researchIntent);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'general farming practices for smallholders',
        ]);
        $searchQuery = app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan);
        $this->assertNotSame('', trim($searchQuery));

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertTrue($gate->isRelevant(
            $plan,
            'General farming practices for smallholders',
            'Farming systems for smallholder agriculture.',
        ));
    }

    public function test_potassium_tomato_topic_factor(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'نقص البوتاسيوم في الطماطم',
        ]);

        $this->assertSame('tomato', $understood->cropId);
        $this->assertContains('potassium', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('plant_nutrition', $understood->researchIntent);
    }

    public function test_doi_alone_insufficient_for_crop_topic_question(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $matcher = app(ClaimEvidenceMatcher::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-doi', 'Unrelated marine biology ecosystems', ['A'], 2021,
            '10.1000/doi-only', 'https://doi.org/10.1000/doi-only',
            'Marine biology ecosystems without crop context.',
            'Journal', ['openalex'],
        );

        $match = $matcher->match(
            $plan,
            $result,
            'Marine biology ecosystems without crop context.',
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );

        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
        $this->assertTrue($match['factors']['doi_alone_insufficient'] ?? false);
    }

    public function test_query_variants_are_limited_and_english_controlled(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);

        $this->assertLessThanOrEqual(5, count($variants));
        $this->assertGreaterThanOrEqual(2, count($variants));
        $this->assertNotEmpty($variants);
        foreach ($variants as $variant) {
            $this->assertStringNotContainsString('الزنجبيل', $variant);
            $this->assertMatchesRegularExpression('/[A-Za-z]/', $variant);
        }
        $joined = implode(' | ', $variants);
        $this->assertStringContainsString('Zingiber officinale', $joined);
        $this->assertTrue(
            str_contains($joined, 'temperature')
            || str_contains($joined, 'thermal')
            || str_contains($joined, 'growth'),
        );
    }

    /** A — growth/climate ginger prefers cultivation sense, rejects MAE extraction. */
    public function test_a_ginger_growth_rejects_mae_extraction_papers(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $growth = $gate->assess(
            $plan,
            'Optimal temperature requirements for Zingiber officinale cultivation',
            'Ginger rhizome growth responds to temperature under agricultural field conditions.',
            '10.1000/ginger-growth',
        );
        $this->assertTrue($growth['relevant']);
        $this->assertTrue($growth['context_adequate']);
        $this->assertTrue($growth['context_matched'] || $growth['sense_matched']);

        $mae = $gate->assess(
            $plan,
            'Microwave-assisted extraction of bioactive compounds from Zingiber officinale: effect of temperature',
            'MAE process temperature optimization for ginger oleoresin extraction yield.',
            '10.1000/ginger-mae',
        );
        $this->assertFalse($mae['relevant']);
        $this->assertContains('negative_context_sense', $mae['rejection_reasons']);

        $composer = app(AnswerComposer::class);
        $maeOnly = $composer->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'mae',
                'MAE process temperature optimization for ginger oleoresin extraction yield.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Microwave-assisted extraction of bioactive compounds from Zingiber officinale: effect of temperature',
                    'doi' => '10.1000/ginger-mae',
                ],
            ),
        ]));
        $this->assertSame('no_validated_evidence', $maeOnly->status);
        $this->assertStringContainsString('أدلة', $maeOnly->answer);
    }

    /** B — drying intent allows drying/temperature evidence. */
    public function test_b_ginger_drying_intent_accepts_drying_papers(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'درجة حرارة تجفيف الزنجبيل',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('drying', $understood->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'درجة حرارة تجفيف الزنجبيل',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $this->assertTrue($gate->isRelevant(
            $plan,
            'Drying temperature effects on Zingiber officinale rhizome quality',
            'Hot air drying of ginger at controlled temperature preserves quality.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Microwave-assisted extraction of Zingiber officinale oleoresin at process temperature',
            'MAE extraction yield of ginger bioactive compounds.',
        ));
    }

    /** C — storage intent allows storage evidence. */
    public function test_c_ginger_storage_intent_accepts_storage_papers(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'تخزين الزنجبيل ودرجة الحرارة',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('storage', $understood->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'تخزين الزنجبيل ودرجة الحرارة',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $this->assertTrue($gate->isRelevant(
            $plan,
            'Storage temperature for Zingiber officinale rhizomes',
            'Post-harvest storage of ginger under controlled temperature.',
        ));
    }

    /** D — wheat salinity (already covered; reinforce sense gate). */
    public function test_d_wheat_salinity_sense_gate(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'تأثير الملوحة على القمح']);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $ok = $gate->assess(
            $plan,
            'Salinity tolerance of Triticum aestivum under irrigation',
            'Wheat salinity stress reduces yield in irrigated field crops.',
        );
        $this->assertTrue($ok['relevant']);
        $this->assertTrue($ok['context_adequate']);

        $this->assertFalse($gate->isRelevant(
            $plan,
            'Salinity effects on tomato fruit quality',
            'Tomato salinity stress physiology.',
        ));
    }

    /** E — corn water. */
    public function test_e_corn_water_relevance(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'احتياج الذرة من الماء',
        ]);
        $this->assertSame('corn', $understood->cropId);
        $this->assertContains('water', $understood->constraints['scientific_factors'] ?? []);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'احتياج الذرة من الماء']);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $this->assertTrue($gate->isRelevant(
            $plan,
            'Water requirement of Zea mays under irrigation',
            'Corn crop growth water use and irrigation scheduling.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Solvent extraction of Zea mays oil at process temperature',
            'Industrial extraction processing of corn oil.',
        ));
    }

    /** F — tomato germination. */
    public function test_f_tomato_germination_temperature(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'درجة حرارة إنبات الطماطم',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $this->assertTrue($gate->isRelevant(
            $plan,
            'Germination temperature of Solanum lycopersicum seeds',
            'Tomato seed germination responds to temperature regimes.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Microwave-assisted extraction of tomato lycopene: temperature effects',
            'MAE extraction of lycopene from tomato processing waste.',
        ));
    }

    /** G — English ginger temperature equivalent with sense. */
    public function test_g_english_ginger_growth_sense(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the optimal temperature for ginger cultivation?',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $this->assertTrue($gate->isRelevant(
            $plan,
            'Optimal growing temperature for Zingiber officinale cultivation',
            'Field crop growth of ginger under climate temperature regimes.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Temperature-assisted extraction of ginger oleoresin using MAE',
            'Process temperature for microwave assisted extraction of ginger.',
        ));
    }

    /** H — bare entity + temperature insufficient without growth sense. */
    public function test_h_entity_plus_temperature_alone_insufficient(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $bare = $gate->assess(
            $plan,
            'Zingiber officinale temperature measurements in laboratory apparatus',
            'Instrument temperature readings for ginger samples in a lab reactor.',
            '10.1000/bare-temp',
        );
        $this->assertFalse($bare['relevant']);
        $this->assertFalse($bare['context_adequate']);
    }

    /** I — general query without crop still works. */
    public function test_i_general_query_without_crop_still_works(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'general farming practices for smallholders',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertTrue($gate->isRelevant(
            $plan,
            'General farming practices for smallholders',
            'Farming systems for smallholder agriculture.',
        ));
    }

    /** J — composer blocks psych/econ/extraction; keeps growth evidence. */
    public function test_j_composer_blocks_wrong_sense_keeps_growth(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما درجة الحرارة المناسبة لزراعة الزنجبيل؟',
        ]);
        $composer = app(AnswerComposer::class);

        $psych = $this->usableEvidence(
            'psych',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Ginger psychology and consumer anxiety disorder therapy',
                'doi' => '10.1000/ginger-psych',
            ],
        );
        $econ = $this->usableEvidence(
            'econ',
            'Economics of ginger commodity trading and stock market pricing.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Ginger market economics and stock market volatility',
                'doi' => '10.1000/ginger-econ',
            ],
        );
        $extraction = $this->usableEvidence(
            'ext',
            'MAE process temperature optimization for ginger oleoresin extraction.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Microwave-assisted extraction of Zingiber officinale: temperature effects',
                'doi' => '10.1000/ginger-ext',
            ],
        );
        $growth = $this->usableEvidence(
            'growth',
            'Zingiber officinale grows best under warm temperature regimes in tropical agriculture.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Optimal temperature requirements for Zingiber officinale cultivation',
                'doi' => '10.1000/ginger-growth',
            ],
        );

        $blocked = $composer->compose($plan, $this->validationReport([$psych, $econ, $extraction]));
        $this->assertSame('no_validated_evidence', $blocked->status);

        $ok = $composer->compose($plan, $this->validationReport([$psych, $econ, $extraction, $growth]));
        $this->assertNotSame('no_validated_evidence', $ok->status);
        $this->assertStringContainsString('Zingiber', $ok->answer);
        $this->assertStringNotContainsString('anxiety', mb_strtolower($ok->answer));
        $this->assertStringNotContainsString('extraction', mb_strtolower($ok->answer));
        $this->assertStringNotContainsString('stock market', mb_strtolower($ok->answer));
    }

    /**
     * Domain classification: economics/psychology are context-aware (Cases C–J + incidental).
     * Bare domain words must not hard-reject agriculturally on-topic evidence.
     */
    public function test_domain_classification_context_aware_economics_psychology(): void
    {
        $growthPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        // C — agricultural economics + crop + temperature: no hard reject
        $agEcon = $gate->assess(
            $growthPlan,
            'Economic analysis of ginger production under different temperature regimes',
            'Agricultural economics of Zingiber officinale crop production and farm profitability under temperature regimes.',
            '10.1000/ag-econ',
        );
        $this->assertTrue($agEcon['relevant']);
        $this->assertNotContains('irrelevant_domain', $agEcon['rejection_reasons']);
        $this->assertFalse($agEcon['factors']['domain_hard_reject'] ?? true);

        // Case 3 / Sanewski-like — incidental "economics" in ag growth abstract
        $incidental = $gate->assess(
            $growthPlan,
            'Rhizome development of Zingiber officinale under temperature and daylength',
            'Ginger rhizome growth responds to temperature under field crop conditions. Further investigation of the economics of applying extended daylength is warranted.',
            '10.1000/incidental-econ',
        );
        $this->assertTrue($incidental['relevant']);
        $this->assertNotContains('irrelevant_domain', $incidental['rejection_reasons']);
        $this->assertSame('economics_incidental', $incidental['factors']['domain_class'] ?? null);

        // D — general economics without ag relationship
        $generalEcon = $gate->assess(
            $growthPlan,
            'Ginger market economics and stock market volatility',
            'Economics of ginger commodity trading and stock market pricing.',
            '10.1000/gen-econ',
        );
        $this->assertFalse($generalEcon['relevant']);
        $this->assertContains('irrelevant_domain', $generalEcon['rejection_reasons']);

        // E — agricultural economics + crop production (explicit branch)
        $e = $gate->assess(
            $growthPlan,
            'Agricultural economics of Zingiber officinale crop production under climate temperature',
            'Farm economics and crop production yields of ginger under temperature stress in agriculture.',
        );
        $this->assertTrue($e['relevant']);

        // F — agricultural extension + farmer behavior (psychology word OK with ag rescue)
        $ext = $gate->assess(
            $growthPlan,
            'Agricultural extension adoption behavior among ginger farmers under temperature regimes',
            'Farmer behavior and agricultural extension psychology of Zingiber officinale cultivation decisions under climate temperature.',
        );
        $this->assertTrue($ext['relevant']);
        $this->assertNotContains('irrelevant_domain', $ext['rejection_reasons']);

        // G — general clinical psychology
        $psych = $gate->assess(
            $growthPlan,
            'Ginger psychology and consumer anxiety disorder therapy',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
        );
        $this->assertFalse($psych['relevant']);
        $this->assertContains('irrelevant_domain', $psych['rejection_reasons']);

        // H — agricultural psychology / farmer behavior
        $agPsych = $gate->assess(
            $growthPlan,
            'Agricultural psychology of farmer behavior in Zingiber officinale temperature management',
            'Farmer behaviour and agricultural psychology for ginger crop growth under temperature.',
        );
        $this->assertTrue($agPsych['relevant']);
        $this->assertNotContains('irrelevant_domain', $agPsych['rejection_reasons']);

        // I — agricultural engineering + irrigation for irrigation question
        $irrigationPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'احتياج الذرة من الماء',
        ]);
        $eng = $gate->assess(
            $irrigationPlan,
            'Agricultural engineering of irrigation scheduling for Zea mays',
            'Crop water requirement and irrigation management for corn field growth.',
        );
        $this->assertTrue($eng['relevant']);

        // J — food science / processing: rejected by topic-sense for growth query, not domain-only
        $food = $gate->assess(
            $growthPlan,
            'Food science processing of Zingiber officinale at controlled temperature',
            'Industrial food processing temperature for ginger products; no crop growth study.',
        );
        $this->assertFalse($food['relevant']);
        $this->assertContains('negative_context_sense', $food['rejection_reasons']);
        $this->assertNotContains('irrelevant_domain', $food['rejection_reasons']);
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
            claimTopic: 'topic',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: ['not_scientific_certainty' => true],
            sourceAttribution: ['organization' => 'University of Agriculture', 'source_type' => 'university_research'],
        );
    }
}
