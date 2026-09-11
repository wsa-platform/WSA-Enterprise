<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use Tests\TestCase;

/**
 * Q4 evidence matching: genus relevance is not exact species identity.
 */
class SpeciesIdentityEvidenceMatchingTest extends TestCase
{
    public function test_exact_species_ipomoea_batatas_remains_matched(): void
    {
        $this->assertExactSpeciesSupport(
            $this->sweetPotatoPlan(),
            'Optimal temperature for Ipomoea batatas seed germination',
            'Ipomoea batatas seed germination temperature requirements were measured under controlled conditions.',
        );
    }

    public function test_common_name_sweet_potato_is_exact_species(): void
    {
        $this->assertExactSpeciesSupport(
            $this->sweetPotatoPlan(),
            'Optimal temperature for sweet potato seed germination',
            'Sweet potato seed germination temperature requirements were measured under controlled conditions.',
        );
    }

    public function test_same_genus_ipomoea_nil_is_not_exact_species(): void
    {
        $this->assertWrongSpecies(
            $this->sweetPotatoPlan(),
            'Effect of Temperature and Light on Germination Characteristics of Japanese Morning Glory (Ipomoea nil): Determination of Cardinal Temperatures of Germination',
            'Cardinal temperatures of Ipomoea nil seed germination were determined under controlled light and temperature.',
            'genus_only',
        );
    }

    public function test_ipomoea_obscura_15c_is_not_species_specific_support(): void
    {
        [$assess, $match] = $this->assessAndMatch(
            $this->sweetPotatoPlan(),
            'Seed germination of Ipomoea obscura at 15 °C',
            'Ipomoea obscura seeds germinated at 15 °C under laboratory conditions.',
        );

        $this->assertSame('genus_only', $assess['species_relation']);
        $this->assertFalse($assess['exact_species_matched']);
        $this->assertTrue($assess['entity_matched']);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
        $this->assertSame('species_identity_mismatch', $match['factors']['reason'] ?? null);
        $this->assertStringNotContainsString('15', json_encode($match['factors']));
    }

    public function test_ipomoea_coccinea_range_is_not_exact_species(): void
    {
        $this->assertWrongSpecies(
            $this->sweetPotatoPlan(),
            'Germination of Ipomoea coccinea between 15–30 °C',
            'Ipomoea coccinea seed germination occurred from 15–30 °C.',
            'genus_only',
        );
    }

    public function test_ipomoea_purpurea_is_not_exact_species(): void
    {
        $this->assertWrongSpecies(
            $this->sweetPotatoPlan(),
            'Seed germination of Ipomoea purpurea',
            'Ipomoea purpurea seed germination temperature responses were recorded.',
            'genus_only',
        );
    }

    public function test_cross_genus_solanum_does_not_match_sweet_potato(): void
    {
        [$assess, $match] = $this->assessAndMatch(
            $this->sweetPotatoPlan(),
            'Optimal temperature for Solanum tuberosum seed germination',
            'Potato (Solanum tuberosum) seed germination temperature was measured under controlled conditions.',
        );

        $this->assertFalse($assess['entity_matched']);
        $this->assertFalse($assess['exact_species_matched']);
        $this->assertNotSame('exact_species', $assess['species_relation']);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_sweet_potato_does_not_match_potato_target(): void
    {
        [$assess, $match] = $this->assessAndMatch(
            $this->potatoPlan(),
            'Optimal temperature for Ipomoea batatas seed germination',
            'Sweet potato (Ipomoea batatas) seed germination temperature requirements were measured.',
        );

        $this->assertFalse($assess['exact_species_matched']);
        $this->assertSame('different_species', $assess['species_relation']);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
        $this->assertSame('species_identity_mismatch', $match['factors']['reason'] ?? null);
    }

    public function test_exact_potato_species_remains_matched(): void
    {
        $this->assertExactSpeciesSupport(
            $this->potatoPlan(),
            'Optimal temperature for Solanum tuberosum seed germination',
            'Solanum tuberosum seed germination temperature was measured under controlled conditions.',
        );
    }

    public function test_entity_less_on_topic_evidence_remains_direct_under_d7(): void
    {
        $plan = $this->entityLessPlan(
            'How does soil organic matter mineralization affect nitrogen availability?',
            'plant_nutrition',
            ['nitrogen'],
            'causes',
        );

        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Soil organic matter mineralization and nitrogen availability in agricultural soils',
            'This study measures nitrogen mineralization rates and plant-available nitrogen after organic matter decomposition in agricultural soils.',
        );

        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertContains('topic_sense_aligned_without_required_entity', $assessment['reasons']);
        $this->assertFalse($assessment['entity_matched']);
    }

    public function test_crossref_citation_metadata_cannot_be_direct_scientific_proof(): void
    {
        $plan = $this->entityLessPlan(
            'How does soil organic matter mineralization affect nitrogen availability?',
            'plant_nutrition',
            ['nitrogen'],
            'causes',
        );

        $result = new ScientificSearchResult(
            sourceKey: 'crossref',
            sourceIdentifier: '10.1000/example.nitrogen',
            title: 'Soil organic matter mineralization and nitrogen availability in agricultural soils',
            authors: ['Example Author'],
            publicationYear: 2024,
            doi: '10.1000/example.nitrogen',
            canonicalUrl: 'https://doi.org/10.1000/example.nitrogen',
            abstract: 'This study measures nitrogen mineralization rates and plant-available nitrogen after organic matter decomposition in agricultural soils.',
            journal: 'Example Journal',
            foundBySources: ['crossref'],
        );

        $assessment = app(EvidenceVerificationLayer::class)->assess($plan, $result);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertContains('crossref_citation_metadata_not_scientific_proof', $assessment['reasons']);
    }

    public function test_off_species_numeric_range_cannot_support_sweet_potato_claim(): void
    {
        [$assess, $match] = $this->assessAndMatch(
            $this->sweetPotatoPlan(),
            'Ipomoea obscura seed germination from 15–30 °C',
            'Germination of Ipomoea obscura occurred across 15–30 °C.',
        );

        $this->assertSame('genus_only', $assess['species_relation']);
        $this->assertFalse($assess['exact_species_matched']);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
        $this->assertSame('species_identity_mismatch', $match['factors']['reason'] ?? null);
    }

    public function test_cultivar_of_target_species_is_exact_species(): void
    {
        $this->assertExactSpeciesSupport(
            $this->sweetPotatoPlan(),
            'Ipomoea batatas "Beauregard" seed germination temperature',
            'Seed germination temperature of Ipomoea batatas cultivar Beauregard was measured.',
        );
    }

    public function test_abbreviated_scientific_name_is_exact_species_when_unambiguous(): void
    {
        $this->assertExactSpeciesSupport(
            $this->sweetPotatoPlan(),
            'I. batatas seed germination temperature',
            'Seed germination of I. batatas was measured across controlled temperatures.',
        );
    }

    public function test_multiple_species_paper_keeps_target_portion_and_rejects_nil_as_target(): void
    {
        $plan = $this->sweetPotatoPlan();

        [$bothAssess, $bothMatch] = $this->assessAndMatch(
            $plan,
            'Comparison of I. batatas and I. nil germination',
            'I. batatas and Ipomoea nil seed germination temperatures were compared under the same protocol.',
        );
        $this->assertTrue($bothAssess['exact_species_matched']);
        $this->assertSame('exact_species', $bothAssess['species_relation']);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $bothMatch['relationship']);

        $this->assertWrongSpecies(
            $plan,
            'Germination characteristics of Ipomoea nil',
            'Only Ipomoea nil seed germination was measured.',
            'genus_only',
        );
    }

    public function test_hybrid_is_not_automatic_exact_species(): void
    {
        [$assess, $match] = $this->assessAndMatch(
            $this->sweetPotatoPlan(),
            'Germination of Ipomoea batatas × Ipomoea trifida hybrid',
            'The Ipomoea batatas × Ipomoea trifida hybrid was tested for seed germination temperature.',
        );

        $this->assertFalse($assess['exact_species_matched']);
        $this->assertSame('genus_only', $assess['species_relation']);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_ipomoea_spp_is_genus_only_not_exact_species(): void
    {
        $this->assertWrongSpecies(
            $this->sweetPotatoPlan(),
            'Seed germination in Ipomoea spp.',
            'Ipomoea spp. seed germination temperature was reviewed without naming a crop species.',
            'genus_only',
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: array{relationship: string, confidence: float, factors: array<string, mixed>}}
     */
    private function assessAndMatch(KnowledgeQueryPlan $plan, string $title, string $abstract): array
    {
        $assess = app(ScientificEvidenceRelevanceGate::class)->assess($plan, $title, $abstract, '10.1000/species-id');
        $result = new ScientificSearchResult(
            'openalex',
            'oa-species-1',
            $title,
            [],
            2020,
            '10.1000/species-id',
            'https://doi.org/10.1000/species-id',
            $abstract,
            null,
            ['openalex'],
        );
        $match = app(ClaimEvidenceMatcher::class)->match(
            $plan,
            $result,
            $abstract,
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );

        return [$assess, $match];
    }

    private function assertExactSpeciesSupport(KnowledgeQueryPlan $plan, string $title, string $abstract): void
    {
        [$assess, $match] = $this->assessAndMatch($plan, $title, $abstract);

        $this->assertTrue($assess['entity_matched'], $title);
        $this->assertTrue($assess['exact_species_matched'], $title);
        $this->assertSame('exact_species', $assess['species_relation'], $title);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship'], $title);
        $this->assertTrue((bool) ($match['factors']['entity_matched'] ?? false), $title);
    }

    private function assertWrongSpecies(
        KnowledgeQueryPlan $plan,
        string $title,
        string $abstract,
        string $relation,
    ): void {
        [$assess, $match] = $this->assessAndMatch($plan, $title, $abstract);

        $this->assertSame($relation, $assess['species_relation'], $title);
        $this->assertFalse($assess['exact_species_matched'], $title);
        $this->assertTrue($assess['entity_matched'], $title.' genus relevance should remain');
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship'], $title);
        $this->assertSame('species_identity_mismatch', $match['factors']['reason'] ?? null, $title);
    }

    private function sweetPotatoPlan(): KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟',
        ]);
    }

    private function potatoPlan(): KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور البطاطا؟',
        ]);
    }

    /**
     * @param  list<string>  $factors
     */
    private function entityLessPlan(
        string $question,
        string $sense,
        array $factors,
        string $questionType,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'soil', 'value' => 'soil'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'soil nitrogen',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'question_type' => $questionType,
                'scientific_sense' => $sense,
                'scientific_factors' => $factors,
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $sense,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $sense,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'soil', 'value' => 'soil'],
            topics: ['soil nitrogen'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
