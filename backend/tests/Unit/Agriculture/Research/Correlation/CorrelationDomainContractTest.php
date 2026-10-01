<?php

namespace Tests\Unit\Agriculture\Research\Correlation;

use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Correlation\AggregatorRecordReference;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use App\Services\Agriculture\Research\Correlation\CorrelationDomainContract;
use App\Services\Agriculture\Research\Correlation\CorrelationInvariantViolation;
use App\Services\Agriculture\Research\Correlation\QuestionIdentityReference;
use App\Services\Agriculture\Research\Correlation\RetrievalEventReference;
use App\Services\Agriculture\Research\Correlation\SourceRecordReference;
use App\Services\Agriculture\Research\Correlation\VariantIdentityReference;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * IU-06 — B7 Correlation / Identity Linkage Domain Contract unit tests only.
 */
final class CorrelationDomainContractTest extends TestCase
{
    public function test_valid_correlation_chain(): void
    {
        $chain = $this->validChain();
        $payload = $chain->toArray();

        $this->assertSame('q-opaque-1', $payload['question_identity']);
        $this->assertSame('csq-opaque-1', $payload['csq_identity']);
        $this->assertSame('G3-01', $payload['adr_id']);
        $this->assertSame('cid_apis', $payload['canonical_identity_id']);
        $this->assertSame('cap-dec-001', $payload['capability_decision_identity']);
        $this->assertSame('path-inst-001', $payload['path_id']);
        $this->assertSame('path-dec-001', $payload['path_decision_identity']);
        $this->assertSame('proj-dec-001', $payload['projection_identity']);
        $this->assertFalse($payload['composite_is_canonical_source_identity']);
        $this->assertFalse($chain->isCanonicalSourceIdentity());
    }

    public function test_references_iu01_identity_types(): void
    {
        $chain = $this->validChain();
        $this->assertInstanceOf(AdrMembershipId::class, $chain->adrId);
        $this->assertInstanceOf(CanonicalSourceIdentityId::class, $chain->canonicalIdentityId);
    }

    public function test_references_iu02_capability_decision_identity(): void
    {
        $chain = $this->validChain();
        $this->assertSame('cap-dec-001', $chain->capabilityDecisionId);
    }

    public function test_references_iu03_path_identities(): void
    {
        $chain = $this->validChain();
        $this->assertInstanceOf(PathId::class, $chain->pathId);
        $this->assertInstanceOf(PathDecisionId::class, $chain->pathDecisionId);
        $this->assertNotSame($chain->pathId->value, $chain->pathDecisionId->value);
    }

    public function test_references_iu04_projection_identity(): void
    {
        $chain = $this->validChain();
        $this->assertInstanceOf(ProjectionIdentity::class, $chain->projectionIdentity);
        $this->assertInstanceOf(CanonicalQueryId::class, $chain->csqIdentity);
    }

    public function test_nullable_canonical_identity(): void
    {
        $cap = $this->capDecision(null);
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-2'),
            CanonicalQueryId::fromString('csq-2'),
            AdrMembershipId::fromString('G1-01'),
            $cap,
            PathId::fromString('path-2'),
            PathDecisionId::fromString('path-dec-2'),
            ProjectionIdentity::fromString('proj-2'),
            canonicalIdentityId: null,
        );

        $this->assertNull($chain->canonicalIdentityId);
        $this->assertNull($chain->toArray()['canonical_identity_id']);
    }

    public function test_optional_variant_absent_or_present(): void
    {
        $without = $this->validChain();
        $this->assertNull($without->variantId);

        $with = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-3'),
            CanonicalQueryId::fromString('csq-3'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-3'),
            PathDecisionId::fromString('path-dec-3'),
            ProjectionIdentity::fromString('proj-3'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            variantId: VariantIdentityReference::fromString('var-lexical-1'),
        );
        $this->assertSame('var-lexical-1', $with->variantId?->value);
    }

    public function test_retrieval_timestamp_is_metadata_not_identity(): void
    {
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-4'),
            CanonicalQueryId::fromString('csq-4'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-4'),
            PathDecisionId::fromString('path-dec-4'),
            ProjectionIdentity::fromString('proj-4'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            retrievalEvent: RetrievalEventReference::at('2026-10-01T12:00:00Z'),
        );

        $this->assertSame('2026-10-01T12:00:00Z', $chain->retrievalEvent?->retrievedAt);
        $this->assertNotContains(
            '2026-10-01T12:00:00Z',
            $chain->compositeCorrelationTokens(),
        );
    }

    public function test_external_aggregator_identity_remains_external(): void
    {
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-5'),
            CanonicalQueryId::fromString('csq-5'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-5'),
            PathDecisionId::fromString('path-dec-5'),
            ProjectionIdentity::fromString('proj-5'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            aggregatorRecordIdentifier: AggregatorRecordReference::fromString('openalex-W123'),
        );

        $this->assertSame('openalex-W123', $chain->aggregatorRecordIdentifier?->value);
        $this->assertNotSame($chain->adrId->value, $chain->aggregatorRecordIdentifier?->value);
    }

    public function test_aggregator_id_cannot_equal_adr_id(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        $this->expectExceptionMessage('aggregator_record_identifier must not equal adr_id');

        CorrelationChain::create(
            QuestionIdentityReference::fromString('q-6'),
            CanonicalQueryId::fromString('csq-6'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-6'),
            PathDecisionId::fromString('path-dec-6'),
            ProjectionIdentity::fromString('proj-6'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            aggregatorRecordIdentifier: AggregatorRecordReference::fromString('G3-01'),
        );
    }

    public function test_aggregator_id_cannot_equal_canonical_identity(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        $this->expectExceptionMessage('aggregator_record_identifier must not equal canonical_identity_id');

        CorrelationChain::create(
            QuestionIdentityReference::fromString('q-7'),
            CanonicalQueryId::fromString('csq-7'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-7'),
            PathDecisionId::fromString('path-dec-7'),
            ProjectionIdentity::fromString('proj-7'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            aggregatorRecordIdentifier: AggregatorRecordReference::fromString('cid_apis'),
        );
    }

    public function test_composite_correlation_is_not_canonical_source_identity(): void
    {
        $chain = $this->validChain();
        $this->assertFalse($chain->isCanonicalSourceIdentity());
        $this->assertGreaterThan(1, count($chain->compositeCorrelationTokens()));
        $this->assertFalse($chain->toArray()['composite_is_canonical_source_identity']);
    }

    public function test_b7_does_not_mint_identity_methods(): void
    {
        $ref = new ReflectionClass(CorrelationDomainContract::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            if ($name === 'assertdoesnotmintidentities') {
                continue;
            }
            $this->assertStringNotContainsString('mint', $name);
            $this->assertStringNotContainsString('sameas', $name);
            $this->assertStringNotContainsString('merge', $name);
        }

        $chainRef = new ReflectionClass(CorrelationChain::class);
        foreach ($chainRef->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('mint', $name);
            $this->assertStringNotContainsString('setcsq', $name);
            $this->assertStringNotContainsString('selectpath', $name);
            $this->assertStringNotContainsString('buildprojection', $name);
        }
        CorrelationDomainContract::assertDoesNotMintIdentities();
        $this->addToAssertionCount(1);
    }

    public function test_b7_cannot_mutate_csq_api_surface(): void
    {
        $ref = new ReflectionClass(CorrelationChain::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('rewrite', $name);
            $this->assertStringNotContainsString('modifycsq', $name);
            $this->assertStringNotContainsString('canonicalizequestion', $name);
        }
        $this->assertTrue(true);
    }

    public function test_b7_rejects_c9_r6_scoring_keys(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        CorrelationDomainContract::assertCorrelationPayloadClean([
            'fidelity_class' => 'EXACT',
        ]);
    }

    public function test_payload_rejects_directness_and_confidence(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        CorrelationDomainContract::assertCorrelationPayloadClean([
            'directness' => 'DIRECT',
            'confidence' => 0.9,
        ]);
    }

    public function test_adr_cannot_equal_path_or_projection(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        $this->expectExceptionMessage('adr_id must not equal path_id');

        CorrelationChain::create(
            QuestionIdentityReference::fromString('q-8'),
            CanonicalQueryId::fromString('csq-8'),
            AdrMembershipId::fromString('SAME-TOKEN'),
            CapabilityDecisionIdentity::create(
                'cap-x',
                CapabilitySubject::seatScoped(AdrMembershipId::fromString('SAME-TOKEN')),
                [],
                [],
                CapabilityAndResultClass::ALL_VERIFIED,
                false,
                'UNKNOWN',
                '2026-10-01T00:00:00Z',
            ),
            PathId::fromString('SAME-TOKEN'),
            PathDecisionId::fromString('path-dec-x'),
            ProjectionIdentity::fromString('proj-x'),
        );
    }

    public function test_capability_decision_must_not_equal_path_decision(): void
    {
        $this->expectException(CorrelationInvariantViolation::class);
        $this->expectExceptionMessage('capability_decision_identity must not equal path_decision_identity');

        CorrelationChain::create(
            QuestionIdentityReference::fromString('q-9'),
            CanonicalQueryId::fromString('csq-9'),
            AdrMembershipId::fromString('G3-01'),
            CapabilityDecisionIdentity::create(
                'same-dec',
                CapabilitySubject::of(
                    AdrMembershipId::fromString('G3-01'),
                    CanonicalSourceIdentityId::fromString('cid_apis'),
                ),
                [],
                [],
                CapabilityAndResultClass::ALL_VERIFIED,
                false,
                'UNKNOWN',
                '2026-10-01T00:00:00Z',
            ),
            PathId::fromString('path-9'),
            PathDecisionId::fromString('same-dec'),
            ProjectionIdentity::fromString('proj-9'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
        );
    }

    public function test_source_record_optional_and_distinct(): void
    {
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-10'),
            CanonicalQueryId::fromString('csq-10'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision(CanonicalSourceIdentityId::fromString('cid_apis')),
            PathId::fromString('path-10'),
            PathDecisionId::fromString('path-dec-10'),
            ProjectionIdentity::fromString('proj-10'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('cid_apis'),
            originalSourceIdentifier: SourceRecordReference::fromString('native-rec-99'),
        );
        $this->assertSame('native-rec-99', $chain->originalSourceIdentifier?->value);
    }

    public function test_valid_chain_payload_has_no_scoring_fields(): void
    {
        $payload = $this->validChain()->toArray();
        foreach (CorrelationDomainContract::FORBIDDEN_SCORING_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $payload);
        }
        CorrelationDomainContract::assertCorrelationPayloadClean($payload);
        $this->addToAssertionCount(1);
    }

    private function validChain(): CorrelationChain
    {
        $canonical = CanonicalSourceIdentityId::fromString('cid_apis');

        return CorrelationChain::create(
            QuestionIdentityReference::fromString('q-opaque-1'),
            CanonicalQueryId::fromString('csq-opaque-1'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision($canonical),
            PathId::fromString('path-inst-001'),
            PathDecisionId::fromString('path-dec-001'),
            ProjectionIdentity::fromString('proj-dec-001'),
            canonicalIdentityId: $canonical,
        );
    }

    private function capDecision(?CanonicalSourceIdentityId $canonical): CapabilityDecisionIdentity
    {
        $subject = $canonical === null
            ? CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01'))
            : CapabilitySubject::of(AdrMembershipId::fromString('G3-01'), $canonical);

        return CapabilityDecisionIdentity::create(
            'cap-dec-001',
            $subject,
            [],
            [],
            CapabilityAndResultClass::ALL_VERIFIED,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }
}
