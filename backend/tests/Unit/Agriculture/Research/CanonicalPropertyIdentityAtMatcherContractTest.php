<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use Tests\TestCase;

/**
 * RC-2: matcher property identity is the canonical family / measurement class,
 * not the user-language surface phrase and not topic_matched.
 */
class CanonicalPropertyIdentityAtMatcherContractTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function surfaceLanguageSameFamily(): array
    {
        return [
            'en_surface' => ['en', 'optimal temperature', 'wheat'],
            'fr_surface' => ['fr', 'température optimale', 'wheat'],
            'tr_surface' => ['tr', 'optimum sıcaklık', 'wheat'],
            'ar_surface' => ['ar', 'درجة الحرارة', 'wheat'],
        ];
    }

    /**
     * @dataProvider surfaceLanguageSameFamily
     */
    public function test_canonical_term_evidence_is_not_missing_requested_property(
        string $language,
        string $propertySurface,
        string $cropId,
    ): void {
        $match = $this->matchPlan(
            $this->temperaturePlan($language, $propertySurface, $cropId),
            'Triticum aestivum wheat temperature requirement for plant growth.',
        );

        $this->assertNotSame('missing_requested_property', $match['factors']['reason'] ?? null);
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ]);
    }

    /**
     * @dataProvider surfaceLanguageSameFamily
     */
    public function test_compatible_unit_evidence_is_not_missing_requested_property(
        string $language,
        string $propertySurface,
        string $cropId,
    ): void {
        $match = $this->matchPlan(
            $this->temperaturePlan($language, $propertySurface, $cropId),
            'Wheat germinated best at 18 °C under controlled growth chambers.',
        );

        $this->assertNotSame('missing_requested_property', $match['factors']['reason'] ?? null);
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ]);
    }

    public function test_rainfall_unit_does_not_support_temperature_property(): void
    {
        $match = $this->matchPlan(
            $this->temperaturePlan('fr', 'température optimale', 'wheat'),
            'Wheat yield increased under 450 mm seasonal rainfall in field trials.',
        );

        $this->assertTrue(
            ($match['factors']['reason'] ?? null) === 'missing_requested_property'
            || $match['relationship'] === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
        );
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
    }

    public function test_topic_matched_growth_without_temperature_is_missing_property(): void
    {
        $plan = $this->temperaturePlan('fr', 'température optimale', 'wheat');
        $query = $plan->normalizedQuery;
        $constraints = $query->constraints;
        $constraints['scientific_topics'] = ['growth', 'physiology'];
        $plan = $this->withConstraints($plan, $constraints);

        $match = $this->matchPlan(
            $plan,
            'Wheat plant growth and physiology in field agronomy without thermal regimes.',
        );

        $this->assertSame('missing_requested_property', $match['factors']['reason'] ?? null);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_generic_cultivation_is_not_temperature_support(): void
    {
        $match = $this->matchPlan(
            $this->temperaturePlan('en', 'temperature', 'wheat'),
            'Wheat cultivation practices and sowing dates for Triticum aestivum.',
        );

        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            ClaimEvidenceRelationship::NOT_VALIDATED,
        ]);
    }

    public function test_number_without_unit_is_not_temperature_support(): void
    {
        $match = $this->matchPlan(
            $this->temperaturePlan('en', 'optimal temperature', 'wheat'),
            'Triticum aestivum wheat growth was scored on 18 replicated field plots.',
        );

        $this->assertTrue(
            ($match['factors']['reason'] ?? null) === 'missing_requested_property'
            || $match['relationship'] === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
        );
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
    }

    public function test_unrelated_lexical_noise_is_not_supported(): void
    {
        $match = $this->matchPlan(
            $this->temperaturePlan('en', 'temperature', 'wheat'),
            'Nationwide macroeconomic inventory of banking and tourism.',
        );

        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            ClaimEvidenceRelationship::NOT_VALIDATED,
        ]);
    }

    public function test_live_french_question_matches_english_temperature_evidence(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'Quelle est la température optimale pour la croissance du blé ?',
        ]);
        $this->assertSame('temperature', $plan->normalizedQuery->canonicalQuestion?->property->key);

        $match = $this->matchPlan(
            $plan,
            'Triticum aestivum wheat temperature requirement for plant growth.',
        );

        $this->assertNotSame('missing_requested_property', $match['factors']['reason'] ?? null);
    }

    public function test_land_offtopic_matcher_path_remains_insufficient(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'What are the land types in Egypt?',
        ]);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the land types in Egypt?',
        ]);
        $this->assertNotNull($understood->canonicalQuestion);

        $match = $this->matchPlan(
            $plan,
            'Greenhouse ornamental cultivation of potted flowers under drip irrigation.',
        );

        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function matchPlan(KnowledgeQueryPlan $plan, string $evidenceText): array
    {
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-rc2-'.substr(sha1($evidenceText), 0, 8),
            title: $evidenceText,
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/rc2-'.substr(sha1($evidenceText), 0, 8),
            canonicalUrl: 'https://example.test/rc2',
            abstract: $evidenceText,
            journal: 'Fixture',
            foundBySources: ['openalex'],
        );

        return app(ClaimEvidenceMatcher::class)->match(
            $plan,
            $result,
            $evidenceText,
            EvidenceValidationStatus::EVIDENCE_USABLE,
        );
    }

    private function temperaturePlan(string $language, string $propertySurface, string $cropId): KnowledgeQueryPlan
    {
        $scientific = match ($cropId) {
            'rice' => 'Oryza sativa',
            default => 'Triticum aestivum',
        };
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $propertySurface.' '.$cropId,
            normalizedQuestion: $propertySurface.' '.$cropId,
            language: $language,
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropId],
            crop: $cropId,
            cropId: $cropId,
            scientificName: $scientific,
            topic: 'temperature',
            subtopic: null,
            requestedInformation: ['temperature'],
            constraints: [
                'answer_language' => $language,
                'requested_property' => $propertySurface,
                'requested_property_key' => $propertySurface,
                'requested_property_surface' => $propertySurface,
                'requested_property_query_terms' => [$propertySurface],
                'scientific_factors' => ['temperature'],
                'scientific_sense' => 'plant_growth',
                'scientific_topics' => ['temperature'],
                'scientific_intent_qualifier' => 'optimal_range',
                'question_type' => 'range',
                'entity_dependent' => true,
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'environmental_requirements',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'environmental_requirements',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['temperature'],
            subtopics: [],
            requestedInformation: ['temperature'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function withConstraints(KnowledgeQueryPlan $plan, array $constraints): KnowledgeQueryPlan
    {
        $q = $plan->normalizedQuery;

        return new KnowledgeQueryPlan(
            normalizedQuery: $q->copyPreservingCanonical($constraints, $q->crop, $q->cropId),
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $plan->topics,
            subtopics: $plan->subtopics,
            requestedInformation: $plan->requestedInformation,
            evidenceRequirements: $plan->evidenceRequirements,
            sourcePriorities: $plan->sourcePriorities,
            primaryResearchStrategy: $plan->primaryResearchStrategy,
            researchSequence: $plan->researchSequence,
            ambiguityState: $plan->ambiguityState,
            clarificationRequirements: $plan->clarificationRequirements,
            readyForStage3: $plan->readyForStage3,
        );
    }
}
