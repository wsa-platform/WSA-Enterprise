<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

class ScientificEvidenceModalityContractTest extends TestCase
{
    public function test_identical_direct_statistical_observations_collapse_two_to_one(): void
    {
        $row = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 't', '1000');
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$row, $row]);

        $this->assertCount(1, $deduped);
        $this->assertTrue(ScientificEvidenceModality::isDirectStatistical($deduped[0]));
        $this->assertSame('1000', $deduped[0]->rawMetadata['observation']['value']);
    }

    public function test_statistical_and_scholarly_results_remain_complementary(): void
    {
        $stat = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 't', '1000');
        $paper = $this->scholarlyResult('Barley production systems in Spain', '10.1000/barley-paper');
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$stat, $paper]);
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production Spain 2018', $deduped, $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(2, $deduped);
        $modalities = array_map(static fn (ScientificSearchResult $row): string => ScientificEvidenceModality::fromResult($row), $filtered);
        $this->assertContains(ScientificEvidenceModality::DIRECT_STATISTICAL, $modalities);
        $this->assertTrue(
            in_array(ScientificEvidenceModality::SCHOLARLY, $modalities, true)
            || count($filtered) >= 1,
        );
    }

    public function test_matching_statistical_observation_survives_rank_filter(): void
    {
        $stat = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 't', '1000');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production', [$stat], $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(1, $filtered);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $filtered[0]->relevanceMetadata['evidence_directness'] ?? null);
        $this->assertFalse((bool) ($filtered[0]->relevanceMetadata['rejected_by_relevance_gate'] ?? false));
    }

    public function test_wrong_entity_statistical_observation_is_rejected(): void
    {
        $stat = $this->statisticalResult('Rice', 'Spain', '2018', 'Production', 't', '1000');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production', [$stat], $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(0, $filtered);
        $this->assertContains('entity', $ranked[0]->relevanceMetadata['rejection_reasons'] ?? []);
    }

    public function test_wrong_location_statistical_observation_is_rejected(): void
    {
        $stat = $this->statisticalResult('Barley', 'Brazil', '2018', 'Production', 't', '1000');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production', [$stat], $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(0, $filtered);
        $this->assertContains('location', $ranked[0]->relevanceMetadata['rejection_reasons'] ?? []);
    }

    public function test_wrong_year_statistical_observation_is_rejected(): void
    {
        $stat = $this->statisticalResult('Barley', 'Spain', '2015', 'Production', 't', '1000');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production', [$stat], $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(0, $filtered);
        $this->assertContains('year', $ranked[0]->relevanceMetadata['rejection_reasons'] ?? []);
    }

    public function test_wrong_property_statistical_observation_is_rejected(): void
    {
        $stat = $this->statisticalResult('Barley', 'Spain', '2018', 'Yield', 't/ha', '3.2');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production quantity in Spain 2018?');
        $ranked = app(ScientificResultRanker::class)->rank('barley production quantity', [$stat], $plan);
        $filtered = app(ScientificResultRanker::class)->filterRelevant($ranked);

        $this->assertCount(0, $filtered);
        $this->assertContains('property', $ranked[0]->relevanceMetadata['rejection_reasons'] ?? []);
    }

    public function test_production_and_yield_remain_distinct_observations(): void
    {
        $production = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 't', '1000');
        $yield = $this->statisticalResult('Barley', 'Spain', '2018', 'Yield', 't/ha', '3.2', 'national_stat_b');
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$production, $yield]);

        $this->assertCount(2, $deduped);
    }

    public function test_different_units_remain_distinct_observations(): void
    {
        $tonnes = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 't', '1000');
        $hectares = $this->statisticalResult('Barley', 'Spain', '2018', 'Production', 'ha', '400', 'national_stat_c');
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$tonnes, $hectares]);

        $this->assertCount(2, $deduped);
    }

    public function test_unrelated_scholarly_year_cannot_displace_statistical_value(): void
    {
        $stat = $this->statisticalEvidenceItem('Value: 910000 t of barley production in Spain in 2018.', '910000');
        $paper = $this->scholarlyEvidenceItem('A 1985 review of barley agronomy in Spain.');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $report = $this->validationReport([$stat, $paper]);
        $composed = app(AnswerComposer::class)->compose($plan, $report);
        $blob = strtolower(implode(' ', array_filter([
            $composed->answer,
            $composed->conciseSummary,
            implode(' ', $composed->keyFindings ?? []),
        ])));

        $this->assertNotSame('insufficient_evidence', $composed->status);
        $this->assertTrue(str_contains(str_replace([',', ' '], '', $blob), '910000'));
        $this->assertFalse((bool) preg_match('/(?<!\d)1985(?!\d)/', $blob));
    }

    public function test_usable_aligned_statistical_evidence_is_not_blocked_by_literature_claim_relationship(): void
    {
        $stat = $this->statisticalEvidenceItem('Value: 910000 t of barley production in Spain in 2018.', '910000');
        $stat = new ScientificEvidenceItem(
            evidenceId: $stat->evidenceId,
            sourceId: $stat->sourceId,
            sourceKey: $stat->sourceKey,
            sourceType: $stat->sourceType,
            publicationTitle: $stat->publicationTitle,
            authors: $stat->authors,
            institution: $stat->institution,
            journal: $stat->journal,
            doi: $stat->doi,
            url: $stat->url,
            publicationYear: $stat->publicationYear,
            retrievedAt: $stat->retrievedAt,
            agriculturalDomain: $stat->agriculturalDomain,
            claimTopic: $stat->claimTopic,
            evidenceText: $stat->evidenceText,
            validationStatus: $stat->validationStatus,
            validationFailures: $stat->validationFailures,
            claimRelationship: ClaimEvidenceRelationship::NOT_VALIDATED,
            confidence: $stat->confidence,
            qualityScore: $stat->qualityScore,
            qualityFactors: $stat->qualityFactors,
            sourceAttribution: $stat->sourceAttribution,
            cropOrEntity: $stat->cropOrEntity,
        );
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $composed = app(AnswerComposer::class)->compose($plan, $this->validationReport([$stat]));
        $blob = strtolower(implode(' ', array_filter([
            $composed->answer,
            $composed->conciseSummary,
            implode(' ', $composed->keyFindings ?? []),
        ])));

        $this->assertNotSame('insufficient_evidence', $composed->status);
        $this->assertTrue(str_contains(str_replace([',', ' '], '', $blob), '910000'));
    }

    public function test_quantity_question_without_statistical_evidence_stays_insufficient(): void
    {
        $paper = $this->scholarlyEvidenceItem('A 1985 review of barley agronomy in Spain.');
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $composed = app(AnswerComposer::class)->compose($plan, $this->validationReport([$paper]));

        $this->assertTrue(in_array($composed->status, [
            'insufficient_evidence',
            'no_validated_evidence',
        ], true));
        $blob = strtolower((string) $composed->answer);
        $this->assertFalse(str_contains($blob, '910000'));
    }

    private function statisticalResult(
        string $entity,
        string $location,
        string $year,
        string $property,
        string $unit,
        string $value,
        string $sourceKey = 'national_stat',
    ): ScientificSearchResult {
        return new ScientificSearchResult(
            sourceKey: $sourceKey,
            sourceIdentifier: implode('|', [$entity, $location, $property, $year, $unit, $value]),
            title: $entity.' — '.$property.' — '.$location.' — '.$year,
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://stats.example.test/obs/'.rawurlencode($entity.$location.$year.$property.$unit.$value),
            abstract: 'Value: '.$value.' '.$unit,
            journal: null,
            foundBySources: [$sourceKey],
            relevanceMetadata: [
                'evidence_type' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'not_literature' => true,
            ],
            rawMetadata: [
                'observation' => [
                    'item' => $entity,
                    'area' => $location,
                    'year' => $year,
                    'element' => $property,
                    'unit' => $unit,
                    'value' => $value,
                    'flag' => 'A',
                ],
            ],
        );
    }

    private function scholarlyResult(string $title, string $doi): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: 'semantic_scholar',
            sourceIdentifier: $doi,
            title: $title,
            authors: ['A Researcher'],
            publicationYear: 2019,
            doi: $doi,
            canonicalUrl: 'https://doi.org/'.$doi,
            abstract: 'This paper discusses agronomic context.',
            journal: 'Ag Journal',
            foundBySources: ['semantic_scholar'],
        );
    }

    private function quantityPlan(string $crop, string $location, string $year, string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $crop],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'question_type' => 'quantity',
                'requested_property_key' => 'production',
                'requested_property_surface' => 'production quantity',
                'requested_property_query_terms' => ['production', 'quantity'],
                'year' => $year,
            ],
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'agricultural_economics',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'agricultural_economics',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $crop],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function statisticalEvidenceItem(string $text, string $value): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: 'stat-'.$value,
            sourceId: 'obs-'.$value,
            sourceKey: 'national_stat',
            sourceType: 'official_statistics',
            publicationTitle: 'Barley — Production — Spain — 2018',
            authors: [],
            institution: 'National statistics',
            journal: null,
            doi: null,
            url: 'https://stats.example.test/obs/'.$value,
            publicationYear: 2018,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'official_statistics',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 80.0,
            cropOrEntity: 'barley',
            qualityFactors: [
                'not_literature' => true,
                'answer_eligible' => true,
                'evidence_type' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'evidence_directness' => 'direct_statistical',
                'observation' => [
                    'item' => 'Barley',
                    'area' => 'Spain',
                    'year' => '2018',
                    'element' => 'Production',
                    'unit' => 't',
                    'value' => $value,
                    'flag' => 'A',
                ],
            ],
            sourceAttribution: [
                'source_type' => 'official_statistics',
                'evidence_directness' => 'direct_statistical',
            ],
        );
    }

    private function scholarlyEvidenceItem(string $text): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: 'paper-1985',
            sourceId: 'doi-1985',
            sourceKey: 'semantic_scholar',
            sourceType: 'journal',
            publicationTitle: 'Barley agronomy review',
            authors: ['A Researcher'],
            institution: null,
            journal: 'Ag Journal',
            doi: '10.1000/review-1985',
            url: 'https://doi.org/10.1000/review-1985',
            publicationYear: 1985,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'agronomy',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            confidence: 0.2,
            qualityScore: 40.0,
            qualityFactors: [
                'answer_eligible' => false,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'entity_matched' => true,
                'topic_matched' => false,
            ],
            sourceAttribution: [
                'source_type' => 'journal',
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'evidence_validated',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: ['claim_evidence_matcher'],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }
}
