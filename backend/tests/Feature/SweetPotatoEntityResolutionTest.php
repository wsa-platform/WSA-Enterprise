<?php

namespace Tests\Feature;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
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
 * Q4 — sweet potato vs potato entity resolution on the production QUS path.
 * Canonical ids: potato / sweet-potato (FieldCropTaxonomyCatalog + AgriculturalEntityCatalog).
 */
class SweetPotatoEntityResolutionTest extends TestCase
{
    public function test_core_entity_identity_aliases_resolve_to_correct_crop(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $cases = [
            ['البطاطا', 'potato', 'Solanum tuberosum'],
            ['البطاطا الحلوة', 'sweet-potato', 'Ipomoea batatas'],
            ['potato', 'potato', 'Solanum tuberosum'],
            ['sweet potato', 'sweet-potato', 'Ipomoea batatas'],
            ['sweet-potato', 'sweet-potato', 'Ipomoea batatas'],
            ['sweetpotato', 'sweet-potato', 'Ipomoea batatas'],
            ['Ipomoea batatas', 'sweet-potato', 'Ipomoea batatas'],
        ];

        foreach ($cases as [$input, $cropId, $scientific]) {
            $understood = $qus->understand(['query' => $input]);
            $this->assertSame($cropId, $understood->cropId, $input);
            $this->assertSame($scientific, $understood->scientificName, $input);
            $this->assertSame(
                $scientific,
                FieldCropTaxonomyCatalog::scientificNameFor((string) $understood->cropId),
                $input,
            );
        }
    }

    public function test_negative_potato_and_sweet_potato_do_not_collide(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $sweetPotatoInputs = ['البطاطا الحلوة', 'sweet potato', 'sweet-potato', 'Ipomoea batatas'];
        foreach ($sweetPotatoInputs as $input) {
            $understood = $qus->understand(['query' => $input]);
            $this->assertSame('sweet-potato', $understood->cropId, $input);
            $this->assertNotSame('potato', $understood->cropId, $input);
            $this->assertSame('Ipomoea batatas', $understood->scientificName, $input);
            $this->assertNotSame('Solanum tuberosum', $understood->scientificName, $input);
        }

        $potato = $qus->understand(['query' => 'البطاطا']);
        $this->assertSame('potato', $potato->cropId);
        $this->assertNotSame('sweet-potato', $potato->cropId);
        $this->assertSame('Solanum tuberosum', $potato->scientificName);
    }

    public function test_regional_similar_terms_are_not_invented_in_taxonomy(): void
    {
        $this->assertNull(FieldCropTaxonomyCatalog::entryFor('jerusalem-artichoke'));
        $this->assertNull(FieldCropTaxonomyCatalog::entryFor('cassava'));
        $this->assertNull(FieldCropTaxonomyCatalog::entryFor('taro'));
        $this->assertSame('', FieldCropTaxonomyCatalog::scientificNameFor('jerusalem-artichoke'));
        $this->assertSame('', FieldCropTaxonomyCatalog::scientificNameFor('cassava'));
        $this->assertSame('', FieldCropTaxonomyCatalog::scientificNameFor('taro'));
    }

    public function test_general_crop_regression_for_supported_catalog_crops(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $tomato = $qus->understand(['query' => 'الطماطم']);
        $this->assertSame('tomato', $tomato->cropId);
        $this->assertSame('Solanum lycopersicum', $tomato->scientificName);

        $pepper = $qus->understand(['query' => 'الفلفل']);
        $this->assertSame('pepper', $pepper->cropId);
        $this->assertSame('Capsicum annuum', $pepper->scientificName);

        foreach (['الخيار', 'البصل', 'الثوم', 'الجزر', 'الباذنجان'] as $unsupported) {
            $understood = $qus->understand(['query' => $unsupported]);
            $this->assertNull($understood->cropId, $unsupported);
        }
    }

    public function test_normalization_variants_keep_sweet_potato_identity(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $variants = [
            'البطاطا الحلوة',
            '  البطاطا   الحلوة  ',
            'البطاطا الحلوة؟',
            'Sweet Potato',
            'SWEET POTATO',
            'sweet-potato',
            'Sweet-Potato',
        ];

        foreach ($variants as $input) {
            $understood = $qus->understand(['query' => $input]);
            $this->assertSame('sweet-potato', $understood->cropId, $input);
            $this->assertSame('Ipomoea batatas', $understood->scientificName, $input);
            $this->assertNotSame('potato', $understood->cropId, $input);
        }
    }

    public function test_specific_phrase_beats_generic_potato_token(): void
    {
        $normalized = 'ما افضل درجة حرارة لانبات بذور البطاطا الحلوة';
        $recognized = AgriculturalEntityCatalog::recognizeCrop($normalized);
        $this->assertNotNull($recognized);
        $this->assertSame('sweet-potato', $recognized['crop_id']);
        $this->assertTrue(
            mb_strlen((string) $recognized['label']) > mb_strlen('البطاطا'),
            'specific sweet-potato phrase must outrank generic potato',
        );

        $qus = app(QueryUnderstandingService::class);
        $q4 = $qus->understand(['query' => 'ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟']);
        $this->assertSame('sweet-potato', $q4->cropId);
        $this->assertSame('Ipomoea batatas', $q4->scientificName);
        $this->assertNotSame('potato', $q4->cropId);
    }

    public function test_q4_query_construction_preserves_sweet_potato_identity(): void
    {
        $question = 'ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟';
        $understood = app(QueryUnderstandingService::class)->understand(['query' => $question]);
        $this->assertSame('sweet-potato', $understood->cropId);
        $this->assertSame('Ipomoea batatas', $understood->scientificName);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->cropId);
        $this->assertSame('Ipomoea batatas', $plan->normalizedQuery->scientificName);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotSame([], $variants);
        $joined = implode(' | ', $variants);

        $this->assertTrue($this->hasSweetPotatoIdentity($joined), $joined);
        $this->assertFalse($this->hasPotatoSpeciesIdentity($joined), $joined);
        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'germinat'),
            $joined,
        );
        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'temperature'),
            $joined,
        );
        $this->assertStringNotContainsString('Solanum tuberosum', $joined);
    }

    public function test_potato_only_evidence_does_not_match_sweet_potato_claims(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟',
        ]);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->cropId);

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $matcher = app(ClaimEvidenceMatcher::class);
        $directness = app(ScientificEvidenceDirectnessAssessor::class);

        $sweetTitle = 'Optimal temperature for Ipomoea batatas seed germination';
        $sweetAbstract = 'Sweet potato (Ipomoea batatas) seed germination temperature requirements were measured under controlled conditions.';
        $sweetAssess = $gate->assess($plan, $sweetTitle, $sweetAbstract, '10.1000/sweet-potato-germ');
        $this->assertTrue($sweetAssess['entity_matched']);
        $this->assertTrue($sweetAssess['relevant']);

        $sweetResult = new ScientificSearchResult(
            'openalex',
            'oa-sweet-1',
            $sweetTitle,
            [],
            2020,
            '10.1000/sweet-potato-germ',
            'https://doi.org/10.1000/sweet-potato-germ',
            $sweetAbstract,
            null,
            ['openalex'],
        );
        $sweetMatch = $matcher->match(
            $plan,
            $sweetResult,
            $sweetAbstract,
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertTrue((bool) ($sweetMatch['factors']['entity_matched'] ?? false));
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $sweetMatch['relationship']);

        $potatoTitle = 'Optimal temperature for Solanum tuberosum seed germination';
        $potatoAbstract = 'Potato (Solanum tuberosum) seed germination temperature was measured under controlled conditions.';
        $potatoAssess = $gate->assess($plan, $potatoTitle, $potatoAbstract, '10.1000/potato-germ');
        $this->assertFalse($potatoAssess['entity_matched'], 'potato-only evidence must not match sweet potato');
        $this->assertFalse($potatoAssess['relevant']);

        $potatoDirect = $directness->assess($plan, $potatoTitle, $potatoAbstract);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $potatoDirect['directness']);

        $potatoResult = new ScientificSearchResult(
            'openalex',
            'oa-potato-1',
            $potatoTitle,
            [],
            2019,
            '10.1000/potato-germ',
            'https://doi.org/10.1000/potato-germ',
            $potatoAbstract,
            null,
            ['openalex'],
        );
        $potatoMatch = $matcher->match(
            $plan,
            $potatoResult,
            $potatoAbstract,
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertFalse((bool) ($potatoMatch['factors']['entity_matched'] ?? false));
    }

    public function test_cross_question_isolation_does_not_retarget_q1_q2_q3_q5(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $q1 = $qus->understand(['query' => 'ما درجة الحرارة المناسبة لإنبات بذور الطماطم؟']);
        $this->assertSame('tomato', $q1->cropId);
        $this->assertSame('Solanum lycopersicum', $q1->scientificName);
        $this->assertSame('seed_germination', $q1->constraints['scientific_sense'] ?? null);

        $q2 = $qus->understand(['query' => 'كيف تؤثر ملوحة التربة على امتصاص الماء بواسطة النبات؟']);
        $this->assertNull($q2->cropId);
        $this->assertSame('salinity_physiology', $q2->constraints['scientific_sense'] ?? null);

        $q3 = $qus->understand(['query' => 'ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟']);
        $this->assertNull($q3->cropId);
        $this->assertSame('irrigation', $q3->researchIntent);

        $q5 = $qus->understand(['query' => 'ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟']);
        $this->assertNull($q5->cropId);
        $this->assertNotSame('sweet-potato', $q5->cropId);
        $this->assertNotSame('potato', $q5->cropId);
    }

    public function test_q4_end_to_end_understanding_and_plan(): void
    {
        $question = 'ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟';
        $understood = app(QueryUnderstandingService::class)->understand(['query' => $question]);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);

        $this->assertSame('sweet-potato', $understood->cropId);
        $this->assertSame('Ipomoea batatas', $understood->scientificName);
        $this->assertSame('crop', $understood->subject['type'] ?? null);
        $this->assertSame('sweet-potato', $understood->subject['value'] ?? null);
        $this->assertSame('environmental_requirements', $understood->researchIntent);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->cropId);
        $this->assertTrue($this->hasSweetPotatoIdentity(implode(' | ', $variants)));
        $this->assertFalse($this->hasPotatoSpeciesIdentity(implode(' | ', $variants)));
    }

    private function hasSweetPotatoIdentity(string $joined): bool
    {
        $hay = mb_strtolower($joined);

        return str_contains($hay, 'ipomoea batatas')
            || str_contains($hay, 'sweet potato')
            || str_contains($hay, 'sweet-potato')
            || str_contains($hay, 'sweetpotato');
    }

    private function hasPotatoSpeciesIdentity(string $joined): bool
    {
        $hay = mb_strtolower($joined);
        if (str_contains($hay, 'solanum tuberosum')) {
            return true;
        }

        $stripped = preg_replace('/sweet[\s\-]?potatoes?/u', ' ', $hay) ?? $hay;

        return (bool) preg_match('/\bpotatoes?\b/u', $stripped);
    }
}
