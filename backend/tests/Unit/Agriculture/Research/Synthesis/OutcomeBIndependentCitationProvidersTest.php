<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Semantic Scholar Outcome B regression:
 * final citations must prove independent-provider contribution when multiple
 * DIRECT-eligible providers are present — without weakening the DIRECT gate.
 */
class OutcomeBIndependentCitationProvidersTest extends TestCase
{
    private AnswerComposer $composer;

    protected function setUp(): void
    {
        parent::setUp();
        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        $this->composer = new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_two_direct_providers_appear_as_independent_citation_contributors(): void
    {
        $items = [
            $this->directItem(
                evidenceId: 'ev-oa',
                sourceKey: 'openalex',
                title: 'OpenAlex wheat nitrogen efficiency trial',
                foundBy: ['openalex'],
            ),
            $this->directItem(
                evidenceId: 'ev-ss',
                sourceKey: 'semantic_scholar',
                title: 'Semantic Scholar wheat nitrogen meta-analysis',
                foundBy: ['semantic_scholar'],
            ),
        ];

        $citations = $this->buildCitations($items);
        $this->assertCount(2, $citations);

        $contribution = $this->citationProviderContribution($citations);
        $this->assertSame(2, $contribution['independent_provider_count']);
        $this->assertEqualsCanonicalizing(
            ['openalex', 'semantic_scholar'],
            $contribution['providers'],
        );

        foreach ($citations as $citation) {
            $this->assertInstanceOf(ResearchAnswerCitation::class, $citation);
            $this->assertNotEmpty($citation->sourceKey);
            $this->assertContains($citation->sourceKey, $citation->contributingProviders);
        }
    }

    public function test_merged_found_by_sources_counts_as_multi_provider_contribution(): void
    {
        $items = [
            $this->directItem(
                evidenceId: 'ev-merged',
                sourceKey: 'openalex',
                title: 'Merged DOI record discovered by two scholarly providers',
                foundBy: ['openalex', 'semantic_scholar'],
            ),
        ];

        $citations = $this->buildCitations($items);
        $this->assertCount(1, $citations);

        $contribution = $this->citationProviderContribution($citations);
        $this->assertSame(2, $contribution['independent_provider_count']);
        $this->assertEqualsCanonicalizing(
            ['openalex', 'semantic_scholar'],
            $contribution['providers'],
        );
        $this->assertEqualsCanonicalizing(
            ['openalex', 'semantic_scholar'],
            $citations[0]->contributingProviders,
        );
    }

    public function test_supporting_only_evidence_does_not_enter_primary_citations(): void
    {
        $items = [
            $this->item(
                evidenceId: 'ev-support',
                sourceKey: 'crossref',
                title: 'Supporting-only Crossref abstract without direct claim answer',
                directness: ScientificEvidenceDirectnessAssessor::SUPPORTING,
                foundBy: ['crossref'],
            ),
        ];

        $citations = $this->buildCitations($items, ['mode' => 'insufficient']);
        $this->assertSame([], $citations);
    }

    public function test_diversify_prefers_uncovered_independent_provider_first(): void
    {
        $items = [
            $this->directItem('ev-ss-1', 'semantic_scholar', 'SS paper one', ['semantic_scholar']),
            $this->directItem('ev-ss-2', 'semantic_scholar', 'SS paper two', ['semantic_scholar']),
            $this->directItem('ev-oa-1', 'openalex', 'OpenAlex paper one', ['openalex']),
        ];

        $citations = $this->buildCitations($items);
        $this->assertCount(3, $citations);

        $firstProviders = $citations[0]->contributingProviders;
        $secondProviders = $citations[1]->contributingProviders;
        $coveredAfterTwo = array_values(array_unique([...$firstProviders, ...$secondProviders]));
        $this->assertContains('semantic_scholar', $coveredAfterTwo);
        $this->assertContains('openalex', $coveredAfterTwo);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @param  array<string, mixed>  $sufficiency
     * @return list<ResearchAnswerCitation>
     */
    private function buildCitations(array $items, array $sufficiency = []): array
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'buildCitations');
        $method->setAccessible(true);

        /** @var list<ResearchAnswerCitation> $citations */
        $citations = $method->invoke($this->composer, $items, $sufficiency);

        return $citations;
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     * @return array{independent_provider_count: int, providers: list<string>, by_provider: array<string, int>}
     */
    private function citationProviderContribution(array $citations): array
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'citationProviderContribution');
        $method->setAccessible(true);

        /** @var array{independent_provider_count: int, providers: list<string>, by_provider: array<string, int>} $result */
        $result = $method->invoke($this->composer, $citations);

        return $result;
    }

    /**
     * @param  list<string>  $foundBy
     */
    private function directItem(string $evidenceId, string $sourceKey, string $title, array $foundBy): ScientificEvidenceItem
    {
        return $this->item(
            evidenceId: $evidenceId,
            sourceKey: $sourceKey,
            title: $title,
            directness: ScientificEvidenceDirectnessAssessor::DIRECT,
            foundBy: $foundBy,
        );
    }

    /**
     * @param  list<string>  $foundBy
     */
    private function item(
        string $evidenceId,
        string $sourceKey,
        string $title,
        string $directness,
        array $foundBy,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: 'src-'.$evidenceId,
            sourceKey: $sourceKey,
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $title,
            authors: ['Author A'],
            institution: 'University Research Institute',
            journal: 'Agronomy Journal',
            doi: '10.1000/'.$evidenceId,
            url: 'https://example.org/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'crop_nutrition',
            claimTopic: 'nitrogen fertilizer efficiency',
            evidenceText: $title.' reports measured nitrogen fertilizer efficiency for wheat.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 0.9,
            qualityFactors: [
                'evidence_directness' => $directness,
            ],
            sourceAttribution: [
                'organization' => 'University Research Institute',
                'source_type' => 'peer_reviewed_journal',
                'found_by_sources' => $foundBy,
                'evidence_directness' => $directness,
            ],
        );
    }
}
