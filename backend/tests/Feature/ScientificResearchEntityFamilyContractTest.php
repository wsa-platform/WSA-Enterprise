<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use Tests\TestCase;

/**
 * Universal research answer pipeline — QUS contract + entity-family propagation.
 * Potato/cucurbit are regression evidence of the generic table-driven path.
 */
class ScientificResearchEntityFamilyContractTest extends TestCase
{
    public function test_multi_class_qus_contract_ar_en_definition_classification_varieties_family(): void
    {
        $qus = app(QueryUnderstandingService::class);

        // Legitimate general_knowledge / definition retention (no entity over-classification).
        $ag = $qus->understand(['query' => 'What is agriculture?']);
        $this->assertSame('general_knowledge', $ag->researchIntent);
        $this->assertSame('definition', $ag->constraints['question_type'] ?? null);
        $this->assertNull($ag->subject);
        $this->assertNull($ag->cropId);

        $agAr = $qus->understand(['query' => 'ما هو تعريف الزراعة؟']);
        $this->assertSame('definition', $agAr->constraints['question_type'] ?? null);
        $this->assertNull($agAr->subject);

        // Crop varieties / types (generic crop path — potato as regression evidence).
        foreach ([
            'ما هي اصناف البطاطا',
            'What are potato varieties?',
        ] as $query) {
            $u = $qus->understand(['query' => $query]);
            $this->assertSame('potato', $u->cropId, $query);
            $this->assertSame('varieties', $u->researchIntent, $query);
            $this->assertSame('varieties', $u->constraints['scientific_sense'] ?? null, $query);
            $this->assertSame('classification', $u->constraints['question_type'] ?? null, $query);
            $this->assertSame('crop', $u->subject['type'] ?? null, $query);
            $this->assertSame(
                'classification_or_types_inventory',
                $u->constraints['required_evidence_type'] ?? null,
                $query,
            );
        }

        // Botanical / entity-family member inventory — ANY alias-table family (cucurbit + fabaceae).
        $familyCases = [
            ['ما هي نبات العائلة القرعية', 'Cucurbitaceae'],
            ['What plants belong to the cucurbit family?', 'Cucurbitaceae'],
            ['What are the members of Cucurbitaceae?', 'Cucurbitaceae'],
            ['What plants belong to the legume family?', 'Fabaceae'],
            ['ما هي نباتات العائلة البقولية؟', 'Fabaceae'],
        ];
        foreach ($familyCases as [$query, $family]) {
            $u = $qus->understand(['query' => $query]);
            $this->assertSame('plant_family', $u->subject['type'] ?? null, $query);
            $this->assertSame($family, $u->subject['value'] ?? null, $query);
            $this->assertSame('plant_family_members', $u->researchIntent, $query);
            $this->assertSame('plant_family_members', $u->constraints['scientific_sense'] ?? null, $query);
            $this->assertSame('species', $u->constraints['question_type'] ?? null, $query);
            $this->assertSame(
                'species_list_or_taxonomy',
                $u->constraints['required_evidence_type'] ?? null,
                $query,
            );
            $this->assertNull($u->cropId, $query);
        }

        // Catalog resolve is table-driven (not cucurbit-hardcoded in QUS).
        $this->assertSame(
            'Cucurbitaceae',
            AgriculturalEntityCatalog::resolveBotanicalFamily('plants of the cucurbit family'),
        );
        $this->assertSame(
            'Fabaceae',
            AgriculturalEntityCatalog::resolveBotanicalFamily('legume family members'),
        );
        $this->assertNull(
            AgriculturalEntityCatalog::resolveBotanicalFamily('what is a family farm business'),
        );
    }

    public function test_plant_family_plan_variants_and_entity_match_signals(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $matcher = app(ClaimEvidenceMatcher::class);

        $plan = $planner->planKnowledgeQuery([
            'query' => 'ما هي نبات العائلة القرعية',
        ]);
        $this->assertSame('plant_family', $plan->subjectEntity['type'] ?? null);
        $this->assertSame('Cucurbitaceae', $plan->subjectEntity['value'] ?? null);
        $this->assertSame('plant_family_members', $plan->researchIntent);
        $this->assertSame('species', $plan->normalizedQuery->constraints['question_type'] ?? null);

        $joined = mb_strtolower(implode(' | ', $builder->buildVariantsFromPlan($plan)));
        $this->assertStringContainsString('cucurbitaceae', $joined);
        $this->assertTrue(
            str_contains($joined, 'species')
            || str_contains($joined, 'plants')
            || str_contains($joined, 'members'),
            $joined,
        );
        $this->assertFalse(str_contains($joined, 'hydroponic cucumber'));

        $onTopicTitle = 'Cucurbitaceae species and family members across cultivated genera';
        $onTopicAbstract = 'Members of the Cucurbitaceae botanical family include cucumber, melon, and watermelon species.';
        $assessment = $gate->assess($plan, $onTopicTitle, $onTopicAbstract, '10.1000/cucurbit-family');
        $this->assertTrue($assessment['requires_entity']);
        $this->assertTrue($assessment['entity_matched'], 'family entity must match evidence');
        $this->assertTrue($assessment['topic_matched'], 'family member inventory topic must match');
        $this->assertTrue($assessment['relevant']);

        $result = new ScientificSearchResult(
            'openalex',
            'oa-cucurbit-1',
            $onTopicTitle,
            [],
            2020,
            '10.1000/cucurbit-family',
            'https://doi.org/10.1000/cucurbit-family',
            $onTopicAbstract,
            null,
            ['openalex'],
        );
        $match = $matcher->match(
            $plan,
            $result,
            $onTopicAbstract,
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertNotSame(
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            $match['relationship'],
            'on-topic family inventory evidence must not collapse to insufficient_evidence',
        );
        $this->assertTrue((bool) ($match['factors']['entity_matched'] ?? false));

        // Irrelevant evidence stays rejected / ineligible — matcher not weakened.
        $oilTitle = 'Potato oil extraction yield in industrial processing';
        $oilAbstract = 'Essential oil yield from potato processing waste under solvent extraction.';
        $oilAssess = $gate->assess($plan, $oilTitle, $oilAbstract, '10.1000/potato-oil');
        $this->assertFalse($oilAssess['entity_matched']);
        $this->assertFalse($oilAssess['relevant']);
    }

    public function test_negative_no_over_classification_or_validation_bypass(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $planner = app(ResearchPlanner::class);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        // No botanical alias → must not invent plant_family subject.
        $farm = $qus->understand(['query' => 'What is a family farm?']);
        $this->assertNotSame('plant_family', $farm->subject['type'] ?? null);
        $this->assertNotSame('plant_family_members', $farm->researchIntent);

        // Crop varieties must not be reclassified as plant_family.
        $potato = $qus->understand(['query' => 'What are potato varieties?']);
        $this->assertSame('crop', $potato->subject['type'] ?? null);
        $this->assertSame('varieties', $potato->researchIntent);

        // Family plan still rejects entity-less / offtopic piles (no bypass).
        $plan = $planner->planKnowledgeQuery([
            'query' => 'What plants belong to the cucurbit family?',
        ]);
        $junk = $gate->assess(
            $plan,
            'Stock market volatility and consumer behavior in macroeconomics',
            'Political economy of equity markets without botanical taxonomy.',
            '10.1000/finance',
        );
        $this->assertFalse($junk['relevant']);
        $this->assertContains('missing_crop_or_entity', $junk['rejection_reasons']);
    }

    public function test_potato_and_cucurbit_regression_pipeline_contract(): void
    {
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);

        $potatoPlan = $planner->planKnowledgeQuery(['query' => 'ما هي اصناف البطاطا']);
        $this->assertSame('varieties', $potatoPlan->researchIntent);
        $this->assertSame('classification', $potatoPlan->normalizedQuery->constraints['question_type'] ?? null);
        $this->assertSame('potato', $potatoPlan->normalizedQuery->cropId);
        $potatoJoined = mb_strtolower(implode(' ', $builder->buildVariantsFromPlan($potatoPlan)));
        $this->assertTrue(str_contains($potatoJoined, 'variet') || str_contains($potatoJoined, 'cultivar'));

        $familyPlan = $planner->planKnowledgeQuery(['query' => 'ما هي نبات العائلة القرعية']);
        $this->assertSame('plant_family_members', $familyPlan->researchIntent);
        $this->assertSame('Cucurbitaceae', $familyPlan->subjectEntity['value'] ?? null);
        $familyJoined = mb_strtolower(implode(' ', $builder->buildVariantsFromPlan($familyPlan)));
        $this->assertStringContainsString('cucurbitaceae', $familyJoined);

        $direct = $assessor->assess(
            $familyPlan,
            'Cucurbitaceae species inventory and taxonomy of cultivated genera',
            'Species and family members of Cucurbitaceae include cucumber and melon.',
            '10.1000/cucurbit-taxa',
        );
        $this->assertTrue($direct['entity_matched']);
        $this->assertTrue($direct['topic_matched']);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $direct['directness']);
    }
}
