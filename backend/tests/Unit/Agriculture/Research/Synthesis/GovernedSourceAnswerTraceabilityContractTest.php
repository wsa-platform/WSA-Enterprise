<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Onboarding\AcceptedOnboardingDecisionCatalog;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * EXIT-09 / EXIT-10: ACTIVE governed source evidence must retain source_key into AnswerComposer citations.
 *
 * Advances §8.1 C3 to PARTIAL only — not SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class GovernedSourceAnswerTraceabilityContractTest extends TestCase
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

    public function test_each_active_governed_source_key_traces_into_primary_citations(): void
    {
        $keys = AcceptedOnboardingDecisionCatalog::activeSourceKeys();
        sort($keys);
        $this->assertSame(['agris', 'bhl', 'dr_duke', 'gbif', 'organic_eprints'], $keys);

        $items = [];
        foreach ($keys as $key) {
            $items[] = $this->directItem($key);
        }

        $method = new ReflectionMethod(AnswerComposer::class, 'buildCitations');
        $method->setAccessible(true);
        /** @var list<ResearchAnswerCitation> $citations */
        $citations = $method->invoke($this->composer, $items, []);

        $this->assertCount(5, $citations);
        $citedKeys = array_map(static fn (ResearchAnswerCitation $c): string => (string) $c->sourceKey, $citations);
        $this->assertEqualsCanonicalizing($keys, $citedKeys);

        foreach ($citations as $citation) {
            $this->assertNotEmpty($citation->sourceKey);
            $this->assertContains($citation->sourceKey, $citation->contributingProviders);
            $this->assertStringContainsString((string) $citation->sourceKey, (string) $citation->url);
        }
    }

    private function directItem(string $sourceKey): ScientificEvidenceItem
    {
        $id = 'ev-gov-'.$sourceKey;

        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: $sourceKey,
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Governed '.$sourceKey.' evidence for wheat nitrogen efficiency',
            authors: ['Author A'],
            institution: 'University Research Institute',
            journal: 'Agronomy Journal',
            doi: '10.1000/'.$id,
            url: 'https://example.org/governed/'.$sourceKey.'/'.$id,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'crop_nutrition',
            claimTopic: 'nitrogen fertilizer efficiency',
            evidenceText: 'Governed '.$sourceKey.' evidence reports measured nitrogen fertilizer efficiency for wheat.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 0.9,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [
                'organization' => 'University Research Institute',
                'source_type' => 'peer_reviewed_journal',
                'found_by_sources' => [$sourceKey],
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );
    }
}
