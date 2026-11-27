<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * RCA regression: preserve non-crop scientific identity and avoid spurious clarification.
 */
final class ScientificIdentityPreservationRcaTest extends TestCase
{
    public function test_named_bee_disease_does_not_need_clarification(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        foreach ([
            'What is American Foulbrood disease in bees?',
            'What bacteria causes American Foulbrood in honey bees?',
            'مرض الحضنة في النحل',
            'ما البكتيريا المسببة لمرض تعفن الحضنة الأمريكي في نحل العسل؟',
        ] as $question) {
            $plan = $planner->planKnowledgeQuery(['query' => $question, 'force_execute' => true]);
            $this->assertNotSame(
                AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
                $plan->normalizedQuery->ambiguityState,
                'Must not demand clarification for specific brood/bee disease: '.$question
            );
            $this->assertFalse($plan->needsClarification(), $question);
            $this->assertSame('disease', $plan->researchIntent, $question);
            $variants = $builder->buildVariantsFromPlan($plan);
            $blob = mb_strtolower(implode(' | ', $variants));
            $this->assertTrue(
                str_contains($blob, 'foulbrood') || str_contains($blob, 'brood'),
                'Brood disease identity must survive into variants for: '.$question.' got '.json_encode($variants)
            );
        }
    }

    public function test_arabic_proclitic_disease_term_is_detected(): void
    {
        $this->assertTrue(
            AgriculturalEntityCatalog::containsTerm('المسببة لمرض تعفن الحضنة', 'مرض'),
            'Arabic proclitic لمرض must match مرض'
        );
    }

    public function test_pest_latin_binomial_leads_query_variants_not_crop_only(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        $plan = $planner->planKnowledgeQuery([
            'query' => 'What is the life cycle, damage, and control of Tuta absoluta on tomato?',
            'force_execute' => true,
        ]);
        $this->assertSame('pest', $plan->researchIntent, 'Pest framing must not collapse to general_knowledge');
        $variants = $builder->buildVariantsFromPlan($plan);
        $blob = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('tuta absoluta', $blob, json_encode($variants));
        $this->assertTrue(
            str_contains($blob, 'tuta absoluta')
            && (str_contains($blob, 'tomato') || str_contains($blob, 'solanum')),
            'Variants must keep pest binomial with crop host, got '.json_encode($variants)
        );
    }

    public function test_aphid_process_identity_is_preserved_in_variants(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        $plan = $planner->planKnowledgeQuery([
            'query' => 'How do aphids affect wheat yield and how can they be managed?',
            'force_execute' => true,
        ]);
        $this->assertSame('pest', $plan->researchIntent);
        $variants = $builder->buildVariantsFromPlan($plan);
        $blob = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('aphid', $blob, json_encode($variants));
        $this->assertTrue(
            str_contains($blob, 'triticum') || str_contains($blob, 'wheat'),
            'Aphid variants must keep the wheat host, got '.json_encode($variants)
        );
        foreach ($variants as $variant) {
            $folded = mb_strtolower(trim((string) $variant));
            if ($folded === 'aphids' || $folded === 'aphid') {
                $this->fail('Hostless aphid-only variant must not lead retrieval when wheat is known');
            }
        }
    }

    public function test_iron_deficiency_citrus_preserves_nutrient_and_crop_group(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        $plan = $planner->planKnowledgeQuery([
            'query' => 'What causes iron deficiency in citrus and how does it appear?',
            'force_execute' => true,
        ]);
        $this->assertSame('plant_nutrition', $plan->researchIntent);
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        $this->assertContains('iron', is_array($factors) ? $factors : [], 'iron must be a scientific factor');
        $subject = $plan->subjectEntity;
        $this->assertNotNull($subject, 'citrus crop group must resolve as subject');
        $this->assertSame('crop_category', $subject['type'] ?? null);
        $this->assertSame('citrus', $subject['value'] ?? null);
        $this->assertNotContains('nitrogen', is_array($factors) ? $factors : []);
        $variants = $builder->buildVariantsFromPlan($plan);
        $blob = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue(
            str_contains($blob, 'iron') && str_contains($blob, 'citrus'),
            'Variants must keep iron + citrus, got '.json_encode($variants)
        );
        $this->assertStringNotContainsString('what causes', $blob);
        $this->assertNotSame(
            AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
            $plan->normalizedQuery->ambiguityState
        );
    }

    public function test_multilingual_pest_intent_parity(): void
    {
        $planner = app(ResearchPlanner::class);
        foreach ([
            'en' => 'What is the life cycle and control of Tuta absoluta on tomato?',
            'fr' => 'Quel est le cycle de vie et le contrôle de Tuta absoluta sur la tomate?',
            'ar' => 'ما دورة حياة حشرة Tuta absoluta على الطماطم وطرق مكافحتها؟',
            'tr' => 'Tuta absoluta\'nın domates üzerindeki yaşam döngüsü ve mücadelesi nedir?',
        ] as $lang => $question) {
            $plan = $planner->planKnowledgeQuery(['query' => $question, 'force_execute' => true]);
            $this->assertSame(
                'pest',
                $plan->researchIntent,
                "pest intent required for {$lang}: {$question}"
            );
        }
    }

    public function test_crop_category_relevance_matches_citrus_not_unrelated_crop(): void
    {
        $planner = app(ResearchPlanner::class);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $plan = $planner->planKnowledgeQuery([
            'query' => 'What causes iron deficiency in citrus and how does it appear?',
            'force_execute' => true,
        ]);

        $relevant = $gate->assess(
            $plan,
            'Iron deficiency chlorosis in citrus: causes and leaf symptoms',
            'Iron chlorosis is a common nutrient disorder in citrus orchards.',
        );
        $this->assertTrue($relevant['relevant'], json_encode($relevant));
        $this->assertTrue($relevant['entity_matched']);

        $rejected = $gate->assess(
            $plan,
            'Nitrogen fertilizer rates for wheat yield under irrigation',
            'Wheat grain yield responded to nitrogen fertilizer applications.',
        );
        $this->assertFalse($rejected['relevant'], 'Unrelated wheat nitrogen paper must not pass citrus iron gate');
    }
}
