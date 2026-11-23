<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLanguageCapabilityCatalog;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Validation\EvidencePipelineStatus;
use Tests\TestCase;

/**
 * Focused contract tests for 2026-11-23 Arabic scientific search remediation.
 */
class ArabicScientificSearchRemediationContractTest extends TestCase
{
    public function test_ar_en_wheat_irrigation_share_crop_water_requirement_sense(): void
    {
        $planner = app(ResearchPlanner::class);
        $en = $planner->planKnowledgeQuery([
            'query' => 'What are the effects of deficit irrigation on wheat yield?',
            'force_execute' => true,
        ]);
        $ar = $planner->planKnowledgeQuery([
            'query' => 'ما تأثير الري الناقص على إنتاجية القمح؟',
            'force_execute' => true,
        ]);

        $this->assertSame('crop_water_requirement', $en->normalizedQuery->constraints['scientific_sense'] ?? null);
        $this->assertSame('crop_water_requirement', $ar->normalizedQuery->constraints['scientific_sense'] ?? null);
        $this->assertSame('ar', $ar->normalizedQuery->constraints['answer_language'] ?? null);
    }

    public function test_arabic_wheat_variants_have_no_arabic_script(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير الري الناقص على إنتاجية القمح؟',
            'force_execute' => true,
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $joined = implode(' ', $variants);
        $this->assertSame(0, preg_match('/\p{Arabic}/u', $joined), $joined);
        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'irrigation')
            || str_contains(mb_strtolower($joined), 'water'),
            $joined,
        );
    }

    public function test_organic_arabic_selects_organic_eprints_and_keeps_concepts(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي ممارسات الزراعة العضوية التي تحسن خصوبة التربة وفقًا للبحوث الزراعية؟',
            'force_execute' => true,
        ]);
        $this->assertTrue(
            app(GovernedScientificSourceEligibility::class)->isEligible('organic_eprints', $plan),
        );
        $selected = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertContains('organic_eprints', $selected);

        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        $bag = mb_strtolower(implode(' ', [
            ...(is_array($topics) ? $topics : []),
            ...(is_array($factors) ? $factors : []),
            implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)),
        ]));
        $this->assertTrue(
            str_contains($bag, 'organic') || in_array('organic', $factors, true),
            'organic concept missing: '.$bag,
        );
        $this->assertTrue(
            str_contains($bag, 'soil') || str_contains($bag, 'fertility') || in_array('soil', $factors, true),
            'soil fertility concept missing: '.$bag,
        );
    }

    public function test_compile_term_does_not_emit_arabic_surface(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير الري الناقص على إنتاجية القمح؟',
            'force_execute' => true,
        ]);
        $csq = $plan->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $retrieval = RetrievalSpecification::fromCanonical($csq);
        $process = is_array($retrieval->projections['process'] ?? null) ? $retrieval->projections['process'] : [];
        $this->assertNotSame([], $process);
        $compiled = $retrieval->compileTerm($process);
        $this->assertSame(0, preg_match('/\p{Arabic}/u', $compiled), 'compiled='.$compiled);
        $frozen = $retrieval->frozenSurface($process);
        $this->assertNotSame('', $frozen);
    }

    public function test_evidence_pipeline_status_canonicalizes_legacy_aliases(): void
    {
        $this->assertSame(
            EvidencePipelineStatus::NO_VALIDATED_EVIDENCE,
            EvidencePipelineStatus::canonicalize('no_valid_evidence'),
        );
        $this->assertSame(
            EvidencePipelineStatus::NO_SEARCH_RESULTS,
            EvidencePipelineStatus::canonicalize('no_search_result'),
        );
        $this->assertSame(
            EvidencePipelineStatus::NO_VALIDATED_EVIDENCE,
            EvidencePipelineStatus::NO_VALIDATED_EVIDENCE,
        );
    }

    public function test_source_language_capability_catalog_covers_production_keys(): void
    {
        $keys = [
            'openalex', 'crossref', 'semantic_scholar', 'consensus', 'fao_stat',
            'agris', 'organic_eprints', 'dr_duke', 'gbif', 'bhl',
        ];
        foreach ($keys as $key) {
            $cap = ScientificSourceLanguageCapabilityCatalog::for($key);
            $this->assertNotNull($cap, $key);
            $this->assertSame($key, $cap->sourceKey);
            $this->assertNotSame('', $cap->evidenceReference);
            if (in_array($key, ['agris', 'organic_eprints', 'openalex'], true)) {
                $this->assertTrue($cap->requiresEnglishRetrievalCompilation(), $key);
            }
        }
    }

    public function test_irrigation_sense_not_applied_to_incidental_water_or_disease(): void
    {
        $planner = app(ResearchPlanner::class);
        $disease = $planner->planKnowledgeQuery([
            'query' => 'ما أمراض صدأ القمح الشائعة؟',
            'force_execute' => true,
        ]);
        $this->assertSame('disease', $disease->normalizedQuery->constraints['scientific_sense'] ?? null);

        $varieties = $planner->planKnowledgeQuery([
            'query' => 'Which tomato varieties are recommended for Egypt near the Nile water system?',
            'force_execute' => true,
        ]);
        $this->assertNotSame(
            'crop_water_requirement',
            $varieties->normalizedQuery->constraints['scientific_sense'] ?? null,
        );
        $this->assertSame('varieties', $varieties->normalizedQuery->constraints['scientific_sense'] ?? null);

        $salinity = $planner->planKnowledgeQuery([
            'query' => 'How does soil salinity affect wheat growth?',
            'force_execute' => true,
        ]);
        $this->assertSame('salinity_physiology', $salinity->normalizedQuery->constraints['scientific_sense'] ?? null);
    }

    public function test_helper_recognizes_deficit_irrigation_without_named_crop_side_channel(): void
    {
        $this->assertTrue(
            \App\Services\Agriculture\Research\AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
                'ما تأثير الري الناقص على إنتاجية القمح؟'
            )
        );
        $this->assertTrue(
            \App\Services\Agriculture\Research\AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
                'What are the effects of deficit irrigation on wheat yield?'
            )
        );
        $this->assertFalse(
            \App\Services\Agriculture\Research\AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
                'Which tomato varieties are recommended for Egypt near the Nile water system?'
            )
        );
    }

    public function test_arabic_cwr_process_fallback_is_not_always_deficit_irrigation(): void
    {
        $planner = app(ResearchPlanner::class);
        $qty = $planner->planKnowledgeQuery([
            'query' => 'ما كمية الري المناسبة للقمح؟',
            'force_execute' => true,
        ]);
        $variants = implode(' ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($qty));
        $this->assertSame(0, preg_match('/\p{Arabic}/u', $variants), $variants);
        $this->assertTrue(
            str_contains(mb_strtolower($variants), 'crop water requirement')
            || str_contains(mb_strtolower($variants), 'irrigation'),
            $variants
        );
        // Quantity questions must not collapse every CWR process to deficit irrigation only.
        if (str_contains(mb_strtolower($variants), 'deficit irrigation')) {
            $this->assertTrue(
                str_contains(mb_strtolower($variants), 'crop water requirement')
                || str_contains(mb_strtolower($variants), 'water requirement'),
                'quantity CWR should retain requirement terms: '.$variants
            );
        }
    }
}
