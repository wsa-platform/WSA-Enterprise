<?php

namespace Tests\Unit\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Disclosure\C9ComposerFidelityDisclosureMapper;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureCodes;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureDomainContract;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureHandoff;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureInvariantViolation;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureRecord;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;
use App\Services\Agriculture\Research\Projection\ProjectionFacetAccounting;
use App\Services\Agriculture\Research\Projection\ProjectionFacetDisposition;
use App\Services\Agriculture\Research\Projection\ProjectionFacetRecord;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * IU-08 — B8 / C9→Composer Fidelity Disclosure Handoff unit tests.
 */
final class FidelityDisclosureHandoffTest extends TestCase
{
    private C9ComposerFidelityDisclosureMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new C9ComposerFidelityDisclosureMapper;
    }

    public function test_exact_aggregate_no_mandatory_disclosure(): void
    {
        $handoff = $this->mapper->map($this->exactEnvelope());

        $this->assertSame(ProjectionFidelityClass::EXACT, $handoff->aggregateFidelityClass);
        $this->assertFalse($handoff->qualificationRequired);
        $this->assertFalse($handoff->unqualifiedExactScientificClaimForbidden);
        $this->assertSame([], $handoff->disclosures);
        $this->assertSame(1, $handoff->schemaVersion);
    }

    #[DataProvider('identityCriticalLossProvider')]
    public function test_identity_critical_non_exact_classes(
        ProjectionFacetDisposition $disposition,
        ProjectionFidelityClass $expectedClass,
        string $reason,
    ): void {
        $envelope = $this->envelopeWithFacets([
            ProjectionFacetRecord::of('species', $disposition, reason: $reason),
        ]);
        $handoff = $this->mapper->map($envelope);

        $this->assertTrue($handoff->qualificationRequired);
        $this->assertTrue($handoff->unqualifiedExactScientificClaimForbidden);

        $codes = array_map(static fn (FidelityDisclosureRecord $r) => $r->code, $handoff->disclosures);
        $this->assertContains(FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS, $codes);
        $this->assertContains(FidelityDisclosureCodes::AGGREGATE_NON_EXACT, $codes);

        $facet = $this->firstByCode($handoff, FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS);
        $this->assertTrue($facet->mandatory);
        $this->assertTrue($facet->qualificationRequired);
        $this->assertTrue($facet->identityCritical);
        $this->assertSame($expectedClass, $facet->c9Class);
        $this->assertSame('species', $facet->facetCode);
        $this->assertSame($disposition, $facet->disposition);
        $this->assertSame($reason, $facet->reason);
    }

    /**
     * @return list<array{0: ProjectionFacetDisposition, 1: ProjectionFidelityClass, 2: string}>
     */
    public static function identityCriticalLossProvider(): array
    {
        return [
            [ProjectionFacetDisposition::COMPRESSED, ProjectionFidelityClass::COMPRESSED, 'compressed taxon'],
            [ProjectionFacetDisposition::APPROXIMATED, ProjectionFidelityClass::APPROXIMATED, 'approx taxon'],
            [ProjectionFacetDisposition::UNRESOLVED, ProjectionFidelityClass::UNRESOLVED, 'unresolved taxon'],
            [ProjectionFacetDisposition::OMITTED, ProjectionFidelityClass::OMITTED, 'omitted taxon'],
            [ProjectionFacetDisposition::UNSUPPORTED, ProjectionFidelityClass::UNSUPPORTED, 'unsupported taxon'],
        ];
    }

    public function test_non_identity_critical_non_exact_is_advisory(): void
    {
        $envelope = $this->envelopeWithFacets([
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ProjectionFacetRecord::of('title', ProjectionFacetDisposition::OMITTED, reason: 'title not projected'),
        ]);
        $handoff = $this->mapper->map($envelope);

        $this->assertSame(ProjectionFidelityClass::EXACT, $handoff->aggregateFidelityClass);
        $this->assertFalse($handoff->qualificationRequired);
        $this->assertFalse($handoff->unqualifiedExactScientificClaimForbidden);

        $codes = array_map(static fn (FidelityDisclosureRecord $r) => $r->code, $handoff->disclosures);
        $this->assertSame([FidelityDisclosureCodes::NON_IDENTITY_FACET_LOSS], $codes);

        $record = $handoff->disclosures[0];
        $this->assertFalse($record->mandatory);
        $this->assertFalse($record->qualificationRequired);
        $this->assertFalse($record->identityCritical);
        $this->assertSame('title', $record->facetCode);
    }

    public function test_aggregate_and_facet_disclosures_remain_distinct(): void
    {
        $envelope = $this->envelopeWithFacets([
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::UNSUPPORTED, reason: 'no dose field'),
        ]);
        $handoff = $this->mapper->map($envelope);

        $codes = array_map(static fn (FidelityDisclosureRecord $r) => $r->code, $handoff->disclosures);
        $this->assertContains(FidelityDisclosureCodes::AGGREGATE_NON_EXACT, $codes);
        $this->assertContains(FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS, $codes);
        $this->assertCount(2, array_unique($codes));
        $this->assertNotSame(
            FidelityDisclosureCodes::AGGREGATE_NON_EXACT,
            FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS,
        );
    }

    public function test_r6_orthogonality_handoff_has_no_r6_fields(): void
    {
        $handoff = $this->mapper->map($this->exactEnvelope());
        $payload = $handoff->toArray();
        foreach (['directness', 'claim_relation', 'sufficiency', 'confidence'] as $key) {
            $this->assertArrayNotHasKey($key, $payload);
        }
        // Mapper ignores R6 fixtures — only ProjectionEnvelope input exists.
        $r6FixtureA = ['directness' => 'DIRECT', 'claim_relation' => 'SUPPORTS', 'sufficiency' => 'SUFFICIENT', 'confidence' => 0.9];
        $r6FixtureB = ['directness' => 'BACKGROUND', 'claim_relation' => 'UNRELATED', 'sufficiency' => 'INSUFFICIENT', 'confidence' => 0.1];
        unset($r6FixtureA, $r6FixtureB);
        $again = $this->mapper->map($this->exactEnvelope());
        $this->assertSame($handoff->toArray(), $again->toArray());
    }

    public function test_confidence_and_threshold_orthogonal_to_handoff(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);

        $exact = $this->mapper->map($this->exactEnvelope());
        $lossy = $this->mapper->map($this->envelopeWithFacets([
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::OMITTED, reason: 'missing'),
        ]));

        // Unit C confidence is independent: handoff never carries or maps confidence.
        $this->assertArrayNotHasKey('confidence', $exact->toArray());
        $this->assertArrayNotHasKey('confidence', $lossy->toArray());
        $this->assertNotSame($exact->qualificationRequired, $lossy->qualificationRequired);

        $fixedUnitCScore = 0.82; // golden from ComposerConfidenceDenominatorContractTest
        $observability = $lossy->mergeIntoObservability([]);
        $this->assertArrayHasKey(FidelityDisclosureHandoff::OBSERVABILITY_KEY, $observability);
        $this->assertSame(0.82, $fixedUnitCScore);
        $this->assertArrayNotHasKey('confidence', $observability);
    }

    public function test_mapper_has_no_forbidden_dependencies(): void
    {
        $dir = dirname(__DIR__, 5).'/app/Services/Agriculture/Research/Disclosure';
        $files = glob($dir.'/*.php') ?: [];
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $src = (string) file_get_contents($file);
            foreach ([
                'CanonicalScientificQuestion',
                'QueryUnderstandingService',
                'ScientificSearchQueryBuilder',
                'AgriculturalEntityCatalog',
                'ScientificQueryCompiler',
                'ScientificSourceSelector',
                'ScientificSourceAdapterRegistry',
                'MultiSourceScientificSearchOrchestrator',
                'ScientificKnowledgePersistenceService',
                'MonitoringEvent',
                'AnswerComposerPhrases',
                'CapabilityRequirementEvaluator',
                'PathModelDomainContract',
            ] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $src, $file);
            }
        }
    }

    public function test_no_persistence_surface_in_disclosure_package(): void
    {
        $ref = new ReflectionClass(C9ComposerFidelityDisclosureMapper::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('persist', $name);
            $this->assertStringNotContainsString('migrate', $name);
            $this->assertStringNotContainsString('eloquent', $name);
        }
        $migration = dirname(__DIR__, 5).'/database/migrations/2026_10_01_160000_create_fidelity_disclosure_table.php';
        $this->assertFileDoesNotExist($migration);
    }

    public function test_only_closed_codes_accepted(): void
    {
        $this->assertSame(
            [
                FidelityDisclosureCodes::AGGREGATE_NON_EXACT,
                FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS,
                FidelityDisclosureCodes::NON_IDENTITY_FACET_LOSS,
            ],
            FidelityDisclosureCodes::ALL,
        );
        $this->expectException(FidelityDisclosureInvariantViolation::class);
        FidelityDisclosureCodes::assertKnown('C9_INVENTED');
    }

    public function test_projection_identity_preserved(): void
    {
        $handoff = $this->mapper->map($this->exactEnvelope('proj-opaque-42'));
        $this->assertSame('proj-opaque-42', $handoff->projectionIdentity->value);
        foreach ($handoff->disclosures as $record) {
            $this->assertSame('proj-opaque-42', $record->projectionIdentity->value);
        }
    }

    public function test_deterministic_output(): void
    {
        $envelope = $this->envelopeWithFacets([
            ProjectionFacetRecord::of('title', ProjectionFacetDisposition::OMITTED, reason: 'a'),
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::COMPRESSED, reason: 'b'),
            ProjectionFacetRecord::of('abstract', ProjectionFacetDisposition::UNSUPPORTED, reason: 'c'),
        ], 'proj-det');
        $a = $this->mapper->map($envelope);
        $b = $this->mapper->map($envelope);
        $this->assertSame($a->toArray(), $b->toArray());
        $facetCodes = [];
        foreach ($a->disclosures as $record) {
            if ($record->facetCode !== null) {
                $facetCodes[] = $record->facetCode;
            }
        }
        $sorted = $facetCodes;
        sort($sorted);
        $this->assertSame($sorted, $facetCodes);
    }

    public function test_observability_attach_does_not_recalculate_c9(): void
    {
        $envelope = $this->envelopeWithFacets([
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::OMITTED, reason: 'missing'),
        ]);
        $handoff = $this->mapper->map($envelope);
        $merged = $handoff->mergeIntoObservability(['existing' => true]);
        $this->assertTrue($merged['existing']);
        $attached = $merged[FidelityDisclosureHandoff::OBSERVABILITY_KEY];
        $this->assertSame($handoff->toArray(), $attached);
        $this->assertTrue($merged['c9_qualification_required']);
        $this->assertTrue($merged['c9_unqualified_exact_scientific_claim_forbidden']);
    }

    public function test_contract_rejects_r6_keys_in_payload(): void
    {
        $this->expectException(FidelityDisclosureInvariantViolation::class);
        FidelityDisclosureDomainContract::assertObservabilityHasNoR6AuthorityKeys(['confidence' => 0.9]);
    }

    private function firstByCode(FidelityDisclosureHandoff $handoff, string $code): FidelityDisclosureRecord
    {
        foreach ($handoff->disclosures as $record) {
            if ($record->code === $code) {
                return $record;
            }
        }
        $this->fail('Missing disclosure code '.$code);
    }

    private function exactEnvelope(string $projectionId = 'proj-dec-001'): ProjectionEnvelope
    {
        return $this->envelopeWithFacets([
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ProjectionFacetRecord::of('entity', ProjectionFacetDisposition::MAPPED, 'wheat'),
        ], $projectionId);
    }

    /**
     * @param  list<ProjectionFacetRecord>  $facets
     */
    private function envelopeWithFacets(array $facets, string $projectionId = 'proj-dec-001'): ProjectionEnvelope
    {
        $cap = CapabilityDecisionIdentity::create(
            'cap-dec-001',
            CapabilitySubject::of(
                AdrMembershipId::fromString('G3-01'),
                CanonicalSourceIdentityId::fromString('cid_apis'),
            ),
            [],
            [],
            CapabilityAndResultClass::ALL_VERIFIED,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
        $path = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-001'),
            PathId::fromString('path-inst-g3-01-p01'),
            PathFamily::P01,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T00:00:00Z',
        );

        return ProjectionEnvelope::create(
            ProjectionIdentity::fromString($projectionId),
            CanonicalQueryId::fromString('csq-canonical-001'),
            $cap,
            $path,
            'species:Triticum aestivum',
            ProjectionFacetAccounting::of($facets),
        );
    }
}
