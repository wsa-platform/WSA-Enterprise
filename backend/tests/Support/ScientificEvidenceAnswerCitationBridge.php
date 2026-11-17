<?php

namespace Tests\Support;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
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
use PHPUnit\Framework\Assert;
use ReflectionMethod;

/**
 * Test-only C1→C3 handoff: ScientificSearchResult identity → AnswerComposer citations.
 *
 * Does not invent a production path. Does not imply SCIENTIFIC_INTEGRATION_VERIFIED.
 */
final class ScientificEvidenceAnswerCitationBridge
{
    public static function makeComposer(): AnswerComposer
    {
        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        return new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );
    }

    /**
     * Map an orchestrator search hit into citation-eligible evidence while preserving
     * source_key, source_identifier, URL/DOI, and provenance attribution.
     */
    public static function evidenceFromSearchResult(
        ScientificSearchResult $result,
        string $claimTopic,
        string $scientificIdentityPhrase,
        string $agriculturalDomain = 'agronomy',
    ): ScientificEvidenceItem {
        Assert::assertNotSame('', $result->sourceKey, 'source_key must survive retrieval');
        Assert::assertNotNull($result->sourceIdentifier, 'source_identifier must survive retrieval');
        Assert::assertNotSame('', trim((string) $result->sourceIdentifier), 'source_identifier must be non-empty');

        $sourceId = (string) $result->sourceIdentifier;
        $evidenceId = 'ev-'.$result->sourceKey.'-'.$sourceId;
        $title = $result->title;
        if (! str_contains(mb_strtolower($title), mb_strtolower($scientificIdentityPhrase))) {
            $title = $result->title.' '.$scientificIdentityPhrase;
        }

        $evidenceText = trim(($result->abstract ?? $result->title).' '.$scientificIdentityPhrase);
        if ($evidenceText === '') {
            $evidenceText = $scientificIdentityPhrase;
        }

        $prov = $result->primaryProvenance();

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: $sourceId,
            sourceKey: $result->sourceKey,
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $title,
            authors: $result->authors !== [] ? $result->authors : ['Governed Source Fixture'],
            institution: 'University Research Institute',
            journal: $result->journal ?? 'Agronomy Journal',
            doi: $result->doi ?? ('10.1000/'.$result->sourceKey.'-'.$sourceId),
            url: $result->canonicalUrl ?? ('https://example.org/'.$result->sourceKey.'/'.$sourceId),
            publicationYear: $result->publicationYear ?? 2023,
            retrievedAt: $prov?->retrievedAt ?? now()->toIso8601String(),
            agriculturalDomain: $agriculturalDomain,
            claimTopic: $claimTopic,
            evidenceText: $evidenceText,
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
                'found_by_sources' => $result->foundBySources !== []
                    ? $result->foundBySources
                    : [$result->sourceKey],
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'provenance' => [
                    'source_key' => $result->sourceKey,
                    'source_identifier' => $result->sourceIdentifier,
                    'adapter_class' => $prov?->adapterClass,
                    'access_family' => $prov?->accessFamily,
                    'contract_version' => $prov?->contractVersion,
                    'retrieved_at' => $prov?->retrievedAt,
                ],
            ],
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ResearchAnswerCitation>
     */
    public static function buildCitations(AnswerComposer $composer, array $items): array
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'buildCitations');
        $method->setAccessible(true);

        /** @var list<ResearchAnswerCitation> $citations */
        $citations = $method->invoke($composer, $items, []);

        return $citations;
    }

    public static function assertCitationPreservesSourceIdentity(
        ResearchAnswerCitation $citation,
        ScientificSearchResult $result,
    ): void {
        Assert::assertSame($result->sourceKey, $citation->sourceKey);
        Assert::assertSame((string) $result->sourceIdentifier, $citation->sourceId);
        Assert::assertSame('ev-'.$result->sourceKey.'-'.$result->sourceIdentifier, $citation->evidenceId);
        Assert::assertContains($result->sourceKey, $citation->contributingProviders);
        Assert::assertNotEmpty($citation->url);
        if ($result->canonicalUrl !== null && $result->canonicalUrl !== '') {
            Assert::assertSame($result->canonicalUrl, $citation->url);
        }
    }
}
