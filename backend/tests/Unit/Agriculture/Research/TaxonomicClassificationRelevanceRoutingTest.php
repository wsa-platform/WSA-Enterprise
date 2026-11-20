<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use PHPUnit\Framework\TestCase;

/**
 * GS-06 regression: taxonomic classification of a binomial must not be routed as
 * land/variety inventory, which previously wiped Stage-3 survivors (all_results_rejected_by_relevance).
 */
class TaxonomicClassificationRelevanceRoutingTest extends TestCase
{
    public function test_mentha_taxonomic_classification_routes_to_species_evidence_contract(): void
    {
        $qus = new QueryUnderstandingService();
        $understood = $qus->understand([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);

        $this->assertSame(
            'Mentha piperita',
            $understood->scientificName,
            'binomial must be recovered after lowercase normalization',
        );
        $this->assertSame(
            'species',
            $understood->constraints['question_type'] ?? null,
        );
        $this->assertSame(
            'species_list_or_taxonomy',
            $understood->constraints['required_evidence_type'] ?? null,
        );
        $this->assertNotSame(
            'classification_or_types_inventory',
            $understood->constraints['required_evidence_type'] ?? null,
        );
        $this->assertStringNotContainsString(
            'taxonomic',
            mb_strtolower((string) ($understood->constraints['named_entity_surface'] ?? $understood->crop ?? '')),
        );
        $this->assertNotContains(
            'named_soil_or_land_classes',
            $understood->constraints['required_evidence_characteristics'] ?? [],
        );
    }

    public function test_mentha_taxonomy_paper_passes_stage3_relevance_gate(): void
    {
        $qus = new QueryUnderstandingService();
        $understood = $qus->understand([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);
        $plan = new KnowledgeQueryPlan(
            normalizedQuery: $understood,
            researchIntent: (string) $understood->researchIntent,
            agriculturalDomain: (string) $understood->agriculturalDomain,
            subjectEntity: is_array($understood->subject) ? $understood->subject : null,
            topics: array_values(array_filter([
                $understood->topic,
                ...((array) ($understood->constraints['scientific_topics'] ?? [])),
            ], static fn ($v): bool => is_string($v) && trim($v) !== '')),
            subtopics: array_values(array_filter([$understood->subtopic], static fn ($v): bool => is_string($v) && $v !== '')),
            requestedInformation: $understood->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );

        $gate = new ScientificEvidenceRelevanceGate();
        $assessment = $gate->assess(
            $plan,
            'Taxonomic classification and phylogeny of Mentha piperita L.',
            'Mentha piperita (peppermint) belongs to Lamiaceae. We review genus Mentha taxonomy and species delimitation.',
            '10.1000/example.taxonomy',
            null,
            'openalex',
            null,
        );

        $this->assertTrue($assessment['relevant'], json_encode($assessment, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($assessment['entity_matched']);
        $this->assertNotContains('missing_crop_or_entity', $assessment['rejection_reasons']);

        // Stage-3 ranker feeds source_key into DirectnessAssessor; without it, identity-oriented
        // scholarly hits were mislabeled IRRELEVANT and wiped (live Mentha GS-06).
        $directness = (new \App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor($gate))->assess(
            $plan,
            'Taxonomic classification and phylogeny of Mentha piperita L.',
            'Mentha piperita (peppermint) belongs to Lamiaceae. We review genus Mentha taxonomy and species delimitation.',
            '10.1000/example.taxonomy',
            null,
            'openalex',
            null,
        );
        $this->assertNotSame(
            \App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            $directness['directness'],
            json_encode($directness, JSON_UNESCAPED_UNICODE),
        );
    }

    public function test_land_soil_classification_is_not_rerouted_to_species(): void
    {
        $qus = new QueryUnderstandingService();
        $understood = $qus->understand([
            'query' => 'What are the soil types and land classification inventory of Morocco?',
        ]);

        $this->assertNotSame('species', $understood->constraints['question_type'] ?? null);
        $this->assertNotSame(
            'species_list_or_taxonomy',
            $understood->constraints['required_evidence_type'] ?? null,
        );
    }

    public function test_residual_entity_strips_taxonomic_qualifier(): void
    {
        $target = AgriculturalEntityCatalog::extractSemanticTarget(
            'what is the taxonomic classification of mentha piperita',
        );
        $this->assertNotNull($target);
        $surface = mb_strtolower((string) ($target['entity_surface'] ?? ''));
        $this->assertStringNotContainsString('taxonomic', $surface);
        $this->assertStringContainsString('mentha', $surface);
        $this->assertStringContainsString('piperita', $surface);
    }
}
