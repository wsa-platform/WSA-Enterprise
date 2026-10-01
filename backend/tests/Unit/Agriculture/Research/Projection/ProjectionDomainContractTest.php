<?php

namespace Tests\Unit\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionDomainContract;
use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;
use App\Services\Agriculture\Research\Projection\ProjectionFacetAccounting;
use App\Services\Agriculture\Research\Projection\ProjectionFacetDisposition;
use App\Services\Agriculture\Research\Projection\ProjectionFacetRecord;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityAggregator;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use App\Services\Agriculture\Research\Projection\ProjectionIdentityCriticalFacets;
use App\Services\Agriculture\Research\Projection\ProjectionInvariantViolation;
use App\Services\Agriculture\Research\Projection\ProjectionRetrievalMethod;
use App\Services\Agriculture\Research\Projection\ProjectionSourceNativeIdentifier;
use App\Services\Agriculture\Research\Projection\ProjectionWarning;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * IU-04 — Projection + C9 Domain Contract unit tests only.
 */
final class ProjectionDomainContractTest extends TestCase
{
    public function test_all_c9_fidelity_classes_present(): void
    {
        $values = array_map(static fn ($c) => $c->value, ProjectionFidelityClass::cases());
        $this->assertSame(
            ['EXACT', 'COMPRESSED', 'APPROXIMATED', 'UNRESOLVED', 'OMITTED', 'UNSUPPORTED'],
            $values,
        );
    }

    public function test_projection_identity_distinct_from_upstream_ids(): void
    {
        $envelope = $this->exactEnvelope();
        $payload = $envelope->toArray();

        $this->assertSame('proj-dec-001', $payload['projection_identity']);
        $this->assertNotSame($payload['projection_identity'], $payload['canonical_query_id']);
        $this->assertNotSame($payload['projection_identity'], $payload['source_identity']['adr_id']);
        $this->assertNotSame($payload['projection_identity'], $payload['capability_decision_identity']);
        $this->assertNotSame($payload['projection_identity'], $payload['path_decision_identity']);
        $this->assertNotSame($payload['projection_identity'], $payload['path_id']);
    }

    public function test_projection_identity_rejects_collapse_with_path_id(): void
    {
        [$cap, $path] = $this->upstream(CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED);

        $this->expectException(ProjectionInvariantViolation::class);
        $this->expectExceptionMessage('projection_identity must not equal path_id');

        ProjectionEnvelope::create(
            ProjectionIdentity::fromString($path->pathId->value),
            CanonicalQueryId::fromString('csq-1'),
            $cap,
            $path,
            'q=wheat',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ]),
        );
    }

    public function test_envelope_includes_required_fields(): void
    {
        $envelope = $this->exactEnvelope();
        $payload = $envelope->toArray();

        foreach ([
            'projection_identity',
            'canonical_query_id',
            'source_identity',
            'capability_decision_identity',
            'path_decision_identity',
            'path_id',
            'path_family',
            'source_query',
            'mapped_facets',
            'expanded_terms',
            'unsupported_facets',
            'omitted_facets',
            'unresolved_facets',
            'source_native_identifiers',
            'retrieval_method',
            'retrieval_timestamp',
            'source_version',
            'fidelity_class',
            'projection_warnings',
        ] as $key) {
            $this->assertArrayHasKey($key, $payload);
        }

        $this->assertSame('UNKNOWN', $payload['retrieval_timestamp']);
        $this->assertSame('UNKNOWN', $payload['source_version']);
        $this->assertSame(PathEligibilityState::ELIGIBLE->value, $payload['path_eligibility_state']);
        $this->assertSame(PathStatus::SELECTED->value, $payload['path_status']);
        $this->assertArrayNotHasKey('directness', $payload);
        $this->assertArrayNotHasKey('claim_relation', $payload);
        $this->assertArrayNotHasKey('sufficiency', $payload);
        $this->assertArrayNotHasKey('confidence', $payload);
    }

    public function test_facet_accounting_buckets_are_separate(): void
    {
        $accounting = ProjectionFacetAccounting::of([
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ProjectionFacetRecord::of('entity', ProjectionFacetDisposition::EXPANDED, 'wheat', exactEquivalenceEstablished: true),
            ProjectionFacetRecord::of('cultivar', ProjectionFacetDisposition::UNSUPPORTED, reason: 'source cannot constrain cultivar'),
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::OMITTED, reason: 'path limitation'),
            ProjectionFacetRecord::of('genotype', ProjectionFacetDisposition::UNRESOLVED, reason: 'no safe mapping'),
        ]);

        $buckets = $accounting->toBucketArrays();
        $this->assertCount(1, $buckets['mapped_facets']);
        $this->assertCount(1, $buckets['expanded_terms']);
        $this->assertCount(1, $buckets['unsupported_facets']);
        $this->assertCount(1, $buckets['omitted_facets']);
        $this->assertCount(1, $buckets['unresolved_facets']);
    }

    public function test_omitted_unsupported_unresolved_require_reason(): void
    {
        $this->expectException(ProjectionInvariantViolation::class);
        ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::OMITTED);
    }

    public function test_identity_critical_loss_cannot_claim_exact(): void
    {
        [$cap, $path] = $this->upstream(CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED);

        $this->expectException(ProjectionInvariantViolation::class);
        $this->expectExceptionMessage('Cannot claim fidelity_class EXACT');

        ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-x'),
            CanonicalQueryId::fromString('csq-x'),
            $cap,
            $path,
            'q=wheat',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::UNSUPPORTED, reason: 'missing'),
            ]),
            ProjectionFidelityClass::EXACT,
        );
    }

    #[DataProvider('identityCriticalLossProvider')]
    public function test_identity_critical_material_losses_are_non_exact(
        string $facet,
        ProjectionFacetDisposition $disposition,
        ProjectionFidelityClass $expected,
    ): void {
        $record = ProjectionFacetRecord::of(
            $facet,
            $disposition,
            reason: $disposition === ProjectionFacetDisposition::MAPPED ? null : 'loss',
            sourceNativeRepresentation: $disposition === ProjectionFacetDisposition::MAPPED ? 'x' : null,
        );
        $this->assertTrue($record->identityCritical);
        $this->assertSame(
            $expected,
            ProjectionFidelityAggregator::aggregateIdentityCritical([$record]),
        );
        $this->assertNotSame(ProjectionFidelityClass::EXACT, $expected);
    }

    /**
     * @return list<array{0: string, 1: ProjectionFacetDisposition, 2: ProjectionFidelityClass}>
     */
    public static function identityCriticalLossProvider(): array
    {
        return [
            ['species', ProjectionFacetDisposition::UNSUPPORTED, ProjectionFidelityClass::UNSUPPORTED],
            ['cultivar', ProjectionFacetDisposition::OMITTED, ProjectionFidelityClass::OMITTED],
            ['dose', ProjectionFacetDisposition::OMITTED, ProjectionFidelityClass::OMITTED],
            ['duration', ProjectionFacetDisposition::UNRESOLVED, ProjectionFidelityClass::UNRESOLVED],
            ['comparator', ProjectionFacetDisposition::APPROXIMATED, ProjectionFidelityClass::APPROXIMATED],
            ['geography', ProjectionFacetDisposition::COMPRESSED, ProjectionFidelityClass::COMPRESSED],
            ['disease', ProjectionFacetDisposition::APPROXIMATED, ProjectionFidelityClass::APPROXIMATED],
            ['pathogen', ProjectionFacetDisposition::UNSUPPORTED, ProjectionFidelityClass::UNSUPPORTED],
        ];
    }

    public function test_worst_loss_aggregation_not_average(): void
    {
        $facets = [
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ProjectionFacetRecord::of('disease', ProjectionFacetDisposition::APPROXIMATED, 'blight-like', reason: 'broader'),
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::UNSUPPORTED, reason: 'unavailable'),
        ];

        $this->assertSame(
            ProjectionFidelityClass::UNSUPPORTED,
            ProjectionFidelityAggregator::aggregateIdentityCritical($facets),
        );
    }

    public function test_species_to_genus_expansion_without_equivalence_is_approximated(): void
    {
        $record = ProjectionFacetRecord::of(
            'species',
            ProjectionFacetDisposition::EXPANDED,
            'Triticum',
            reason: 'broadened to genus',
            exactEquivalenceEstablished: false,
        );
        $this->assertSame(ProjectionFidelityClass::APPROXIMATED, $record->fidelityContribution());
        $this->assertFalse($record->isExactRepresentation());
    }

    public function test_consumes_path_eligibility_without_recalculation(): void
    {
        [$cap, $path] = $this->upstream(
            CapabilityAndResultClass::HAS_PARTIAL,
            PathStatus::CONDITIONALLY_SELECTED,
        );
        $this->assertSame(PathEligibilityState::CONDITIONAL, $path->eligibilityState);

        $envelope = ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-cond'),
            CanonicalQueryId::fromString('csq-cond'),
            $cap,
            $path,
            'q=partial',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
                ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::OMITTED, reason: 'partial cap scope'),
            ]),
        );

        $this->assertSame(PathEligibilityState::CONDITIONAL, $envelope->pathEligibilityState);
        $this->assertSame(PathStatus::CONDITIONALLY_SELECTED, $envelope->pathStatus);
        $this->assertSame(ProjectionFidelityClass::OMITTED, $envelope->fidelityClass);
        $this->assertSame($cap->decisionId, $envelope->capabilityDecisionId);
    }

    public function test_unknown_retrieval_metadata_allowed(): void
    {
        $envelope = $this->exactEnvelope();
        $this->assertTrue($envelope->retrievalMethod->isUnknown());
        $this->assertSame('UNKNOWN', $envelope->retrievalTimestamp);
        $this->assertSame('UNKNOWN', $envelope->sourceVersion);
        ProjectionDomainContract::assertUnknownMetadataNotPromoted(
            $envelope->retrievalTimestamp,
            $envelope->sourceVersion,
        );
        $this->addToAssertionCount(1);
    }

    public function test_payload_rejects_r6_keys(): void
    {
        $this->expectException(ProjectionInvariantViolation::class);
        ProjectionDomainContract::assertProjectionPayloadClean([
            'directness' => 'DIRECT',
        ]);
    }

    public function test_fidelity_independent_of_r6(): void
    {
        foreach (ProjectionFidelityClass::cases() as $class) {
            ProjectionDomainContract::assertFidelityIndependentOfR6($class);
        }
        $this->assertNotContains('DIRECT', array_map(static fn ($c) => $c->value, ProjectionFidelityClass::cases()));
    }

    public function test_identity_critical_vocabulary_contains_required_codes(): void
    {
        foreach (['species', 'dose', 'disease', 'entity', 'process', 'property', 'relation', 'time'] as $code) {
            $this->assertTrue(ProjectionIdentityCriticalFacets::isIdentityCritical($code));
        }
    }

    public function test_package_has_no_runtime_execution_methods(): void
    {
        $ref = new ReflectionClass(ProjectionDomainContract::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('http', $name);
            $this->assertStringNotContainsString('execute', $name);
            $this->assertStringNotContainsString('adapter', $name);
            $this->assertStringNotContainsString('compose', $name);
            $this->assertStringNotContainsString('persist', $name);
        }
        $this->assertTrue(true);
    }

    public function test_source_native_identifier_distinct_from_adr_id(): void
    {
        [$cap, $path] = $this->upstream(CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED);

        $this->expectException(ProjectionInvariantViolation::class);
        ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-native'),
            CanonicalQueryId::fromString('csq-native'),
            $cap,
            $path,
            'q=x',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ]),
            sourceNativeIdentifiers: [
                ProjectionSourceNativeIdentifier::of('record', 'G3-01'),
            ],
        );
    }

    public function test_exact_envelope_emits_no_c9_non_exact_warning_code_only_when_exact(): void
    {
        $exact = $this->exactEnvelope();
        $codes = array_map(static fn (ProjectionWarning $w) => $w->code, $exact->projectionWarnings);
        $this->assertNotContains('C9_NON_EXACT', $codes);
        $this->assertSame(ProjectionFidelityClass::EXACT, $exact->fidelityClass);

        [$cap, $path] = $this->upstream(CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED);
        $lossy = ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-lossy'),
            CanonicalQueryId::fromString('csq-lossy'),
            $cap,
            $path,
            'q=x',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('geography', ProjectionFacetDisposition::OMITTED, reason: 'not sent'),
            ]),
            projectionWarnings: [ProjectionWarning::of('GEO_LIMIT', 'geography omitted')],
        );
        $lossyCodes = array_map(static fn (ProjectionWarning $w) => $w->code, $lossy->projectionWarnings);
        $this->assertContains('C9_NON_EXACT', $lossyCodes);
        $this->assertContains('GEO_LIMIT', $lossyCodes);
    }

    public function test_retrieval_method_unknown_factory(): void
    {
        $this->assertSame('UNKNOWN', ProjectionRetrievalMethod::unknown()->value);
        $this->assertSame('api', ProjectionRetrievalMethod::of('api')->value);
    }

    private function exactEnvelope(): ProjectionEnvelope
    {
        [$cap, $path] = $this->upstream(CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED);

        return ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-dec-001'),
            CanonicalQueryId::fromString('csq-canonical-001'),
            $cap,
            $path,
            'species:Triticum aestivum',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
                ProjectionFacetRecord::of('entity', ProjectionFacetDisposition::MAPPED, 'wheat'),
            ]),
            sourceNativeIdentifiers: [
                ProjectionSourceNativeIdentifier::of('filter_field', 'taxon_name'),
            ],
        );
    }

    /**
     * @return array{0: CapabilityDecisionIdentity, 1: PathDecisionIdentity}
     */
    private function upstream(
        CapabilityAndResultClass $andClass,
        PathStatus $status,
    ): array {
        $cap = CapabilityDecisionIdentity::create(
            'cap-dec-001',
            CapabilitySubject::of(
                AdrMembershipId::fromString('G3-01'),
                CanonicalSourceIdentityId::fromString('cid_apis'),
            ),
            [],
            [],
            $andClass,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );

        $path = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-001'),
            PathId::fromString('path-inst-g3-01-p01'),
            PathFamily::P01,
            $cap,
            $status,
            decidedAt: '2026-10-01T00:00:00Z',
        );

        return [$cap, $path];
    }
}
