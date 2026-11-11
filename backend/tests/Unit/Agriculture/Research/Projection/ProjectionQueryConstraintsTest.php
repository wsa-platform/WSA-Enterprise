<?php

namespace Tests\Unit\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\CapabilityToPathEligibilityMapper;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionDomainContract;
use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;
use App\Services\Agriculture\Research\Projection\ProjectionFacetAccounting;
use App\Services\Agriculture\Research\Projection\ProjectionFacetConstraint;
use App\Services\Agriculture\Research\Projection\ProjectionFacetConstraintState;
use App\Services\Agriculture\Research\Projection\ProjectionFacetDisposition;
use App\Services\Agriculture\Research\Projection\ProjectionFacetRecord;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use App\Services\Agriculture\Research\Projection\ProjectionIdentityCriticalFacets;
use App\Services\Agriculture\Research\Projection\ProjectionInvariantViolation;
use App\Services\Agriculture\Research\Projection\ProjectionQueryConstraints;
use App\Services\Agriculture\Research\Projection\ProjectionQueryConstraintsFactory;
use App\Services\Agriculture\Research\Projection\ProjectionRetrievalMethod;
use App\Services\Agriculture\Research\Projection\ProjectionWarning;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * ADR-023 §8.33 B-1 — ProjectionQueryConstraints domain contract unit tests only.
 *
 * Capability decisions are produced by the real CapabilityRequirementEvaluator unless a test
 * needs a malformed or hand-built decision.
 */
final class ProjectionQueryConstraintsTest extends TestCase
{
    private const NEW_CLASS_FILES = [
        'ProjectionFacetConstraintState.php',
        'ProjectionFacetConstraint.php',
        'ProjectionQueryConstraints.php',
        'ProjectionQueryConstraintsFactory.php',
    ];

    private const MISSING_WARNING = 'Facet [species] query constraint is UNKNOWN (capability_state MISSING: no query_constrainable Capability fact in the capability decision).';

    #[DataProvider('capabilityStateMappingProvider')]
    public function test_capability_state_and_freshness_map_to_constraint(
        ?CapabilityState $state,
        CapabilityEvidenceFreshness $freshness,
        string $expectedCapabilityState,
        ProjectionFacetConstraintState $expectedConstraint,
        ?string $expectedWarning,
    ): void {
        $records = $state === null ? [] : [$this->queryConstrainableRecord('species', $state, $freshness)];

        $payload = $this->derive(['species'], $records)->toArray();

        $this->assertSame(
            [
                'facet_code' => 'species',
                'identity_critical' => true,
                'capability_state' => $expectedCapabilityState,
                'constraint' => $expectedConstraint->value,
            ],
            $payload['facet_constraints'][0],
        );
        $this->assertSame(
            $expectedWarning === null ? [] : [['code' => 'QUERY_CONSTRAINT_UNKNOWN', 'message' => $expectedWarning]],
            $payload['warnings'],
        );
    }

    /**
     * @return array<string, array{0: ?CapabilityState, 1: CapabilityEvidenceFreshness, 2: string, 3: ProjectionFacetConstraintState, 4: ?string}>
     */
    public static function capabilityStateMappingProvider(): array
    {
        $current = CapabilityEvidenceFreshness::CURRENT;
        $unknown = ProjectionFacetConstraintState::UNKNOWN;
        $notConstrainable = ProjectionFacetConstraintState::NOT_CONSTRAINABLE;
        $verifiedNotCurrent = static fn (string $freshness): string => "Facet [species] query constraint is UNKNOWN (capability_state VERIFIED; evidence freshness {$freshness}, not CURRENT).";

        return [
            'VERIFIED + CURRENT' => [CapabilityState::VERIFIED, $current, 'VERIFIED', ProjectionFacetConstraintState::CONSTRAINABLE, null],
            'VERIFIED + STALE' => [CapabilityState::VERIFIED, CapabilityEvidenceFreshness::STALE, 'VERIFIED', $unknown, $verifiedNotCurrent('STALE')],
            'VERIFIED + UNKNOWN' => [CapabilityState::VERIFIED, CapabilityEvidenceFreshness::UNKNOWN, 'VERIFIED', $unknown, $verifiedNotCurrent('UNKNOWN')],
            'UNAVAILABLE + CURRENT' => [CapabilityState::UNAVAILABLE, $current, 'UNAVAILABLE', $notConstrainable, null],
            'UNAVAILABLE + STALE' => [CapabilityState::UNAVAILABLE, CapabilityEvidenceFreshness::STALE, 'UNAVAILABLE', $notConstrainable, null],
            'NOT_APPLICABLE + CURRENT' => [CapabilityState::NOT_APPLICABLE, $current, 'NOT_APPLICABLE', $notConstrainable, null],
            'UNVERIFIED + CURRENT' => [CapabilityState::UNVERIFIED, $current, 'UNVERIFIED', $unknown, 'Facet [species] query constraint is UNKNOWN (capability_state UNVERIFIED).'],
            'PARTIAL + CURRENT' => [CapabilityState::PARTIAL, $current, 'PARTIAL', $unknown, 'Facet [species] query constraint is UNKNOWN (capability_state PARTIAL).'],
            'MISSING' => [null, $current, 'MISSING', $unknown, self::MISSING_WARNING],
        ];
    }

    public function test_mapping_is_total_over_frozen_states_and_freshness(): void
    {
        $expected = [
            'VERIFIED' => ProjectionFacetConstraintState::UNKNOWN,
            'PARTIAL' => ProjectionFacetConstraintState::UNKNOWN,
            'UNVERIFIED' => ProjectionFacetConstraintState::UNKNOWN,
            'UNAVAILABLE' => ProjectionFacetConstraintState::NOT_CONSTRAINABLE,
            'NOT_APPLICABLE' => ProjectionFacetConstraintState::NOT_CONSTRAINABLE,
        ];

        $this->assertSame(array_keys($expected), array_map(static fn ($c) => $c->value, CapabilityState::cases()));
        $this->assertSame(['CURRENT', 'STALE', 'UNKNOWN'], array_map(static fn ($c) => $c->value, CapabilityEvidenceFreshness::cases()));
        foreach (CapabilityState::cases() as $state) {
            foreach ([...CapabilityEvidenceFreshness::cases(), null] as $freshness) {
                $want = $state === CapabilityState::VERIFIED && $freshness === CapabilityEvidenceFreshness::CURRENT
                    ? ProjectionFacetConstraintState::CONSTRAINABLE
                    : $expected[$state->value];
                $this->assertSame($want, ProjectionFacetConstraintState::fromCapability($state, $freshness));
            }
        }
        foreach ([...CapabilityEvidenceFreshness::cases(), null] as $freshness) {
            $this->assertSame(ProjectionFacetConstraintState::UNKNOWN, ProjectionFacetConstraintState::fromCapability(null, $freshness));
        }
        $this->assertSame(
            ['CONSTRAINABLE', 'NOT_CONSTRAINABLE', 'UNKNOWN'],
            array_map(static fn ($c) => $c->value, ProjectionFacetConstraintState::cases()),
        );
    }

    public function test_verified_without_established_current_freshness_is_unknown(): void
    {
        $defaultFreshness = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-species-default'),
            $this->subject(),
            CapabilityDimension::scientificFacetQueryConstrainable('species'),
            CapabilityState::VERIFIED,
        );
        $entry = $this->derive(['species'], [$defaultFreshness])->toArray()['facet_constraints'][0];
        $this->assertSame(['VERIFIED', 'UNKNOWN'], [$entry['capability_state'], $entry['constraint']]);

        $notEstablished = 'Facet [species] query constraint is UNKNOWN (capability_state VERIFIED; evidence freshness not established, not CURRENT).';
        $cell = $this->outcome('species', 'VERIFIED');
        unset($cell['evidence_freshness_class']);
        foreach ([$cell, $this->outcome('species', 'VERIFIED', 'FRESH'), $this->outcome('species', 'VERIFIED', 'current')] as $outcome) {
            $payload = $this->deriveFrom($this->handBuiltDecision([$outcome]), ['species'])->toArray();
            $this->assertSame('VERIFIED', $payload['facet_constraints'][0]['capability_state']);
            $this->assertSame('UNKNOWN', $payload['facet_constraints'][0]['constraint']);
            $this->assertSame([['code' => 'QUERY_CONSTRAINT_UNKNOWN', 'message' => $notEstablished]], $payload['warnings']);
        }

        $this->assertSame(
            'CONSTRAINABLE',
            $this->deriveFrom($this->handBuiltDecision([$this->outcome('species', 'VERIFIED', 'CURRENT')]), ['species'])
                ->toArray()['facet_constraints'][0]['constraint'],
        );
    }

    public function test_reads_only_cells_resolved_by_the_capability_decision(): void
    {
        $verifiedCurrent = $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT);

        $notRequired = $this->evaluate([$verifiedCurrent], [CapabilityDimension::accessMethod(CapabilityAccessMethod::API)]);
        $this->assertSame([], array_filter($notRequired->perCellOutcomes, static fn (array $o) => $o['code'] === 'species'));
        $this->assertMissing($this->deriveFrom($notRequired, ['species']));

        $contentAbout = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-species-about'),
            $this->subject(),
            CapabilityDimension::scientificFacetContentAbout('species'),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
        );
        $this->assertMissing($this->deriveFrom($this->evaluate([$contentAbout]), ['species']));

        $foreign = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-foreign'),
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G3-02')),
            CapabilityDimension::scientificFacetQueryConstrainable('species'),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
        );
        $this->assertMissing($this->deriveFrom($this->evaluate([$foreign]), ['species']));

        $seatOverride = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-species-seat'),
            $this->subject(),
            CapabilityDimension::scientificFacetQueryConstrainable('species'),
            CapabilityState::UNAVAILABLE,
            CapabilityEvidenceFreshness::CURRENT,
            seatOverrideApplied: true,
        );
        $decision = $this->evaluate([$verifiedCurrent, $seatOverride]);
        $this->assertSame('UNAVAILABLE', $decision->perCellOutcomes[0]['state']);
        $this->assertSame(
            ['UNAVAILABLE', 'NOT_CONSTRAINABLE'],
            array_values(array_intersect_key(
                $this->deriveFrom($decision, ['species'])->toArray()['facet_constraints'][0],
                ['capability_state' => true, 'constraint' => true],
            )),
        );
    }

    public function test_capability_codes_are_not_case_folded(): void
    {
        $upper = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-species-upper'),
            $this->subject(),
            CapabilityDimension::scientificFacetQueryConstrainable('Species'),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
        );
        $decision = $this->evaluate([$upper], [CapabilityDimension::scientificFacetQueryConstrainable('Species')]);
        $this->assertSame(['Species', 'VERIFIED'], [$decision->perCellOutcomes[0]['code'], $decision->perCellOutcomes[0]['state']]);

        $this->assertMissing($this->deriveFrom($decision, ['species']));
        $this->assertMissing($this->deriveFrom($decision, ['Species']));
    }

    public function test_identity_critical_is_derived_not_caller_supplied(): void
    {
        $critical = ProjectionFacetConstraint::of(' Species ', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT);
        $this->assertSame('species', $critical->facetCode);
        $this->assertTrue($critical->identityCritical);

        $this->assertNotContains('soil_texture', ProjectionIdentityCriticalFacets::CODES);
        $nonCritical = ProjectionFacetConstraint::of('soil_texture', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT);
        $this->assertFalse($nonCritical->identityCritical);

        $this->assertTrue((new ReflectionClass(ProjectionFacetConstraint::class))->getConstructor()->isPrivate());
        $this->assertTrue((new ReflectionClass(ProjectionQueryConstraints::class))->getConstructor()->isPrivate());

        $entries = $this->derive(['soil_texture', 'species'], [])->toArray()['facet_constraints'];
        $this->assertSame(
            ['soil_texture' => false, 'species' => true],
            array_column($entries, 'identity_critical', 'facet_code'),
        );
    }

    public function test_rejects_empty_duplicate_and_malformed_values(): void
    {
        $this->assertViolation('Facet code must be a non-empty string.', fn () => $this->derive(['species', '  '], []));
        $this->assertViolation('Facet code must be a non-empty string.', fn () => ProjectionFacetConstraint::of('', null, null));
        $this->assertViolation('Facet codes must be strings.', fn () => $this->derive([42], []));
        $this->assertViolation(
            'Duplicate facet_code [species] in facet codes.',
            fn () => $this->derive(['species', ' Species '], []),
        );

        [$cap, $path] = $this->upstream();
        $this->assertViolation(
            'Duplicate facet_code [dose] in facet_constraints.',
            fn () => ProjectionQueryConstraints::create(
                CanonicalQueryId::fromString('csq-1'),
                $cap,
                $path,
                [
                    ProjectionFacetConstraint::of('dose', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT),
                    ProjectionFacetConstraint::of('DOSE', null, null),
                ],
            ),
        );
        $this->assertViolation(
            'canonical_query_id must be a non-empty string.',
            fn () => CanonicalQueryId::fromString('  '),
        );
        $this->assertViolation(
            'facet_constraints must be ProjectionFacetConstraint instances.',
            fn () => ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-1'), $cap, $path, [['facet_code' => 'species']]),
        );
        $this->assertViolation(
            'warnings must be ProjectionWarning instances.',
            fn () => $this->derive(['species'], [], warnings: ['plain string']),
        );
        $this->assertViolation(
            'ProjectionWarning code and message must be non-empty.',
            fn () => ProjectionWarning::of(' ', 'message'),
        );

        $noKey = $this->outcome('species', 'VERIFIED', 'CURRENT');
        unset($noKey['dimension_key']);
        $noMissingFlag = $this->outcome('species', 'VERIFIED', 'CURRENT');
        unset($noMissingFlag['missing_record']);
        $malformed = [
            'Capability decision outcomes must carry a string dimension_key.' => [$noKey],
            'Capability decision outcome for facet_code [species] has no valid Capability state.' => [$this->outcome('species', 'verified', 'CURRENT')],
            'Capability decision outcome for facet_code [species] has no boolean missing_record.' => [$noMissingFlag],
            'Conflicting capability decision outcomes for facet_code [species].' => [
                $this->outcome('species', 'VERIFIED', 'CURRENT'),
                $this->outcome('species', 'UNAVAILABLE', 'CURRENT'),
            ],
        ];
        foreach ($malformed as $message => $outcomes) {
            $this->assertViolation($message, fn () => $this->deriveFrom($this->handBuiltDecision($outcomes), ['species']));
        }

        $species = CapabilityDimension::scientificFacetQueryConstrainable('species');
        $repeated = $this->evaluate(
            [$this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT)],
            [$species, $species],
        );
        $this->assertCount(2, $repeated->perCellOutcomes);
        $this->assertSame('CONSTRAINABLE', $this->deriveFrom($repeated, ['species'])->toArray()['facet_constraints'][0]['constraint']);
    }

    public function test_carries_aligned_decision_identifiers(): void
    {
        $payload = $this->derive(['species'], [])->toArray();

        $this->assertSame('csq-canonical-001', $payload['canonical_query_id']);
        $this->assertSame('G3-01', $payload['adr_id']);
        $this->assertSame('cid_apis', $payload['canonical_identity_id']);
        $this->assertSame('cap-dec-001', $payload['capability_decision_id']);
        $this->assertSame('path-dec-001', $payload['path_decision_id']);
        $this->assertSame('path-inst-g3-01-p01', $payload['path_id']);

        [$cap, $path] = $this->upstream(canonical: null);
        $seatScoped = (new ProjectionQueryConstraintsFactory())->derive(
            CanonicalQueryId::fromString('csq-seat'),
            $cap,
            $path,
            ['species'],
        );
        $this->assertNull($seatScoped->toArray()['canonical_identity_id']);
        $this->assertNull($seatScoped->canonicalIdentityId);
    }

    public function test_freshness_does_not_bypass_decision_alignment(): void
    {
        $records = [$this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT)];
        [$cap, $path] = $this->upstream($records);
        $this->assertSame('CONSTRAINABLE', $this->deriveFrom($cap, ['species'])->toArray()['facet_constraints'][0]['constraint']);

        $otherSeatSubject = CapabilitySubject::of(AdrMembershipId::fromString('G3-02'), CanonicalSourceIdentityId::fromString('cid_apis'));
        $otherSeatRecord = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-species-g3-02'),
            $otherSeatSubject,
            CapabilityDimension::scientificFacetQueryConstrainable('species'),
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
        );
        $cases = [
            'Projection Cap decision id must match Path Decision capability_decision_identity.' => $this->evaluate($records, decisionId: 'cap-dec-002'),
            'Projection Cap subject adr_id must match Path Decision adr_id.' => $this->evaluate([$otherSeatRecord], subject: $otherSeatSubject),
            'Projection Cap subject canonical must match Path Decision canonical.' => $this->evaluate(
                $records,
                subject: CapabilitySubject::seatScoped(AdrMembershipId::fromString('G3-01')),
            ),
        ];
        foreach ($cases as $message => $mismatched) {
            $this->assertViolation($message, fn () => (new ProjectionQueryConstraintsFactory())->derive(
                CanonicalQueryId::fromString('csq-1'),
                $mismatched,
                $path,
                ['species'],
            ));
            $this->assertViolation($message, fn () => ProjectionQueryConstraints::create(
                CanonicalQueryId::fromString('csq-1'),
                $mismatched,
                $path,
                [ProjectionFacetConstraint::of('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT)],
            ));
        }
        $this->assertSame('cap-dec-001', $cap->decisionId);
    }

    public function test_direct_create_accepts_only_constraints_the_capability_decision_resolves(): void
    {
        $verifiedCurrent = ProjectionFacetConstraint::of('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT);
        [$missingCap, $missingPath] = $this->upstream();
        [$staleCap, $stalePath] = $this->upstream([
            $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::STALE),
        ]);
        [$currentCap, $currentPath] = $this->upstream([
            $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT),
        ]);

        $sameSerialization = ProjectionFacetConstraint::of('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::UNKNOWN);
        $this->assertSame(
            ProjectionFacetConstraint::fromCapabilityDecision($staleCap, ['species'])[0]->toArray(),
            $sameSerialization->toArray(),
        );

        $cases = [
            'VERIFIED + CURRENT without a Cap cell' => [$missingCap, $missingPath, $verifiedCurrent],
            'CURRENT asserted over STALE' => [$staleCap, $stalePath, $verifiedCurrent],
            'freshness differs, serialization equal' => [$staleCap, $stalePath, $sameSerialization],
            'MISSING asserted over VERIFIED + CURRENT' => [$currentCap, $currentPath, ProjectionFacetConstraint::of('species', null, null)],
        ];
        foreach ($cases as $label => [$cap, $path, $constraint]) {
            try {
                ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-canonical-001'), $cap, $path, [$constraint]);
                $this->fail("Expected rejection: {$label}");
            } catch (ProjectionInvariantViolation $e) {
                $this->assertSame('Facet constraint [species] does not match the capability decision.', $e->getMessage(), $label);
            }
        }

        $noKey = $this->outcome('species', 'VERIFIED', 'CURRENT');
        unset($noKey['dimension_key']);
        $malformedCap = $this->handBuiltDecision([$noKey]);
        $malformedPath = $this->path($malformedCap);
        $this->assertViolation(
            'Capability decision outcomes must carry a string dimension_key.',
            fn () => $this->deriveFrom($malformedCap, [], path: $malformedPath),
        );
        $this->assertViolation(
            'Capability decision outcomes must carry a string dimension_key.',
            fn () => ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-canonical-001'), $malformedCap, $malformedPath, []),
        );

        $this->assertSame(
            $this->deriveFrom($currentCap, ['species'], path: $currentPath)->toArray(),
            ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-canonical-001'), $currentCap, $currentPath, [$verifiedCurrent])->toArray(),
        );
    }

    public function test_serialization_is_sorted_and_deterministic(): void
    {
        $species = $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT);
        $dose = $this->queryConstrainableRecord('dose', CapabilityState::UNAVAILABLE, CapabilityEvidenceFreshness::CURRENT);
        $codes = ['species', 'dose', 'cultivar', '9', '10'];

        $a = $this->derive($codes, [$species, $dose])->toArray();
        $b = $this->deriveFrom(
            $this->evaluate([$dose, $species], array_map(
                static fn (string $c) => CapabilityDimension::scientificFacetQueryConstrainable($c),
                ['10', 'dose', 'cultivar', 'species', '9'],
            )),
            ['10', 'Cultivar', 'species', 'DOSE', '9'],
        )->toArray();

        $this->assertSame($a, $b);
        $this->assertSame(json_encode($a), json_encode($b));
        $this->assertSame(['10', '9', 'cultivar', 'dose', 'species'], array_column($a['facet_constraints'], 'facet_code'));
        $this->assertSame(
            ['10' => 'UNKNOWN', '9' => 'UNKNOWN', 'cultivar' => 'UNKNOWN', 'dose' => 'NOT_CONSTRAINABLE', 'species' => 'CONSTRAINABLE'],
            array_column($a['facet_constraints'], 'constraint', 'facet_code'),
        );

        [$cap, $path] = $this->upstream([$species, $dose], $codes);
        $direct = ProjectionQueryConstraints::create(
            CanonicalQueryId::fromString('csq-canonical-001'),
            $cap,
            $path,
            [
                ProjectionFacetConstraint::of('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT),
                ProjectionFacetConstraint::of('9', null, null),
                ProjectionFacetConstraint::of('cultivar', null, null),
                ProjectionFacetConstraint::of('dose', CapabilityState::UNAVAILABLE, CapabilityEvidenceFreshness::CURRENT),
                ProjectionFacetConstraint::of('10', null, null),
            ],
        );
        $this->assertSame($a, $direct->toArray());
    }

    public function test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid(): void
    {
        $payload = $this->derive(
            ['7', '07'],
            [$this->queryConstrainableRecord('7', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT)],
        )->toArray();
        $this->assertSame(
            [
                ['facet_code' => '07', 'identity_critical' => false, 'capability_state' => 'MISSING', 'constraint' => 'UNKNOWN'],
                ['facet_code' => '7', 'identity_critical' => false, 'capability_state' => 'VERIFIED', 'constraint' => 'CONSTRAINABLE'],
            ],
            $payload['facet_constraints'],
        );
        $this->assertSame(
            [[
                'code' => 'QUERY_CONSTRAINT_UNKNOWN',
                'message' => 'Facet [07] query constraint is UNKNOWN (capability_state MISSING: no query_constrainable Capability fact in the capability decision).',
            ]],
            $payload['warnings'],
        );

        [$cap, $path] = $this->upstream();
        $this->assertViolation('Duplicate facet_code [9] in facet codes.', fn () => $this->deriveFrom($cap, ['9', ' 9 '], path: $path));
        $this->assertViolation(
            'Duplicate facet_code [9] in facet_constraints.',
            fn () => ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-1'), $cap, $path, [
                ProjectionFacetConstraint::of('9', null, null),
                ProjectionFacetConstraint::of(' 9 ', null, null),
            ]),
        );
        $this->assertViolation(
            'Facet constraint [9] does not match the capability decision.',
            fn () => ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-1'), $cap, $path, [
                ProjectionFacetConstraint::of('9', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT),
            ]),
        );

        $empty = $this->deriveFrom($cap, [], path: $path)->toArray();
        $this->assertSame([], $empty['facet_constraints']);
        $this->assertSame([], $empty['warnings']);
        $this->assertSame(
            $empty,
            ProjectionQueryConstraints::create(CanonicalQueryId::fromString('csq-canonical-001'), $cap, $path, [])->toArray(),
        );
    }

    public function test_unknown_constraints_are_disclosed_once_in_facet_order(): void
    {
        $generatedForCultivar = ProjectionWarning::of(
            'QUERY_CONSTRAINT_UNKNOWN',
            'Facet [cultivar] query constraint is UNKNOWN (capability_state MISSING: no query_constrainable Capability fact in the capability decision).',
        );
        $payload = $this->derive(
            ['species', 'dose', 'cultivar', 'geography'],
            [
                $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::STALE),
                $this->queryConstrainableRecord('dose', CapabilityState::PARTIAL, CapabilityEvidenceFreshness::CURRENT),
                $this->queryConstrainableRecord('geography', CapabilityState::UNAVAILABLE, CapabilityEvidenceFreshness::CURRENT),
            ],
            warnings: [
                ProjectionWarning::of('CALLER_NOTE', 'supplied by caller'),
                ProjectionWarning::of('CALLER_NOTE', 'supplied by caller'),
                $generatedForCultivar,
            ],
        )->toArray();

        $this->assertSame(
            [
                ['code' => 'CALLER_NOTE', 'message' => 'supplied by caller'],
                $generatedForCultivar->toArray(),
                ['code' => 'QUERY_CONSTRAINT_UNKNOWN', 'message' => 'Facet [dose] query constraint is UNKNOWN (capability_state PARTIAL).'],
                ['code' => 'QUERY_CONSTRAINT_UNKNOWN', 'message' => 'Facet [species] query constraint is UNKNOWN (capability_state VERIFIED; evidence freshness STALE, not CURRENT).'],
            ],
            $payload['warnings'],
        );
        $this->assertSame('UNKNOWN', $payload['retrieval_method']);
        $this->assertSame(
            'api',
            $this->derive(['species'], [], ProjectionRetrievalMethod::of('api'))->toArray()['retrieval_method'],
        );
    }

    public function test_payload_keys_are_snake_case_and_clean(): void
    {
        $payload = $this->derive(
            ['species', 'dose'],
            [$this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT)],
        )->toArray();

        $this->assertSame(
            [
                'schema_version',
                'canonical_query_id',
                'adr_id',
                'canonical_identity_id',
                'capability_decision_id',
                'path_decision_id',
                'path_id',
                'facet_constraints',
                'retrieval_method',
                'warnings',
            ],
            array_keys($payload),
        );
        $this->assertSame('projection_query_constraints.v1', $payload['schema_version']);
        $this->assertSame(ProjectionQueryConstraints::SCHEMA_VERSION, $payload['schema_version']);

        $allKeys = $this->collectKeys($payload);
        foreach ($allKeys as $key) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $key);
        }
        foreach (['source_query', 'expanded_terms', 'fidelity_class', 'mapped_facets', 'source_native_identifiers', 'evidence_freshness_class'] as $absent) {
            $this->assertNotContains($absent, $allKeys);
        }

        ProjectionDomainContract::assertProjectionPayloadClean($payload);
        foreach ($payload['facet_constraints'] as $entry) {
            $this->assertSame(['facet_code', 'identity_critical', 'capability_state', 'constraint'], array_keys($entry));
            ProjectionDomainContract::assertProjectionPayloadClean($entry);
        }
        $this->assertViolation(
            'Projection payload must not carry forbidden key [source_key].',
            fn () => ProjectionDomainContract::assertProjectionPayloadClean([...$payload, 'source_key' => 'x']),
        );
    }

    public function test_does_not_write_capability_or_use_any_repository(): void
    {
        $records = [
            $this->queryConstrainableRecord('species', CapabilityState::VERIFIED, CapabilityEvidenceFreshness::CURRENT),
            $this->queryConstrainableRecord('dose', CapabilityState::PARTIAL, CapabilityEvidenceFreshness::STALE),
        ];
        [$cap, $path] = $this->upstream($records, ['species', 'dose']);
        $before = [
            array_map(static fn (CapabilityRecord $r) => $r->toArray(), $records),
            $cap->toArray(),
            $path->toArray(),
        ];

        $this->deriveFrom($cap, ['species', 'dose', 'cultivar'], path: $path);

        $this->assertSame($before, [
            array_map(static fn (CapabilityRecord $r) => $r->toArray(), $records),
            $cap->toArray(),
            $path->toArray(),
        ]);

        $factory = new ReflectionClass(ProjectionQueryConstraintsFactory::class);
        $this->assertNull($factory->getConstructor());
        $this->assertSame([], $factory->getProperties());

        foreach (self::NEW_CLASS_FILES as $file) {
            $source = (string) file_get_contents($this->projectionDir().DIRECTORY_SEPARATOR.$file);
            preg_match_all('/^use\s+([^;]+);/m', $source, $imports);
            foreach ($imports[1] as $import) {
                $this->assertStringNotContainsString('Persistence', $import, $file);
                $this->assertStringNotContainsString('Repository', $import, $file);
                $this->assertStringNotContainsString('Illuminate', $import, $file);
                $this->assertStringNotContainsString('CapabilityRequirementEvaluator', $import, $file);
            }
            foreach (['DB::', '->save(', '->persist(', '->upsert(', 'Http::', 'curl_', 'strtolower(', 'mb_strtolower('] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $source, $file);
            }
        }
    }

    public function test_existing_contracts_are_unchanged(): void
    {
        $this->assertSame(
            ['MAPPED', 'EXPANDED', 'COMPRESSED', 'APPROXIMATED', 'UNSUPPORTED', 'OMITTED', 'UNRESOLVED'],
            array_map(static fn ($c) => $c->value, ProjectionFacetDisposition::cases()),
        );
        $this->assertSame(
            ['EXACT', 'COMPRESSED', 'APPROXIMATED', 'UNRESOLVED', 'OMITTED', 'UNSUPPORTED'],
            array_map(static fn ($c) => $c->value, ProjectionFidelityClass::cases()),
        );
        $this->assertSame(
            ['directness', 'claim_relation', 'claim_relationship', 'sufficiency', 'overall_confidence', 'confidence', 'evidence_score'],
            ProjectionDomainContract::FORBIDDEN_R6_KEYS,
        );
        $this->assertSame(
            ['http', 'guzzle', 'curl', 'adapter', 'normalizer', 'sourcekey', 'source_key', 'migration', 'eloquent'],
            ProjectionDomainContract::FORBIDDEN_RUNTIME_KEYS,
        );
        $this->assertCount(33, ProjectionIdentityCriticalFacets::CODES);

        $create = new ReflectionMethod(ProjectionEnvelope::class, 'create');
        $this->assertSame(
            [
                'projectionIdentity', 'canonicalQueryId', 'capabilityDecision', 'pathDecision', 'sourceQuery',
                'facetAccounting', 'fidelityClass', 'projectionWarnings', 'sourceNativeIdentifiers',
                'retrievalMethod', 'retrievalTimestamp', 'sourceVersion', 'projectionVersion',
            ],
            array_map(static fn (ReflectionParameter $p) => $p->getName(), $create->getParameters()),
        );

        [$cap, $path] = $this->upstream();
        $envelope = ProjectionEnvelope::create(
            ProjectionIdentity::fromString('proj-dec-001'),
            CanonicalQueryId::fromString('csq-canonical-001'),
            $cap,
            $path,
            'species:Triticum aestivum',
            ProjectionFacetAccounting::of([
                ProjectionFacetRecord::of('species', ProjectionFacetDisposition::MAPPED, 'Triticum aestivum'),
            ]),
        );
        $this->assertSame(
            [
                'projection_identity', 'canonical_query_id', 'source_identity', 'capability_decision_identity',
                'path_decision_identity', 'path_id', 'path_family', 'path_eligibility_state', 'path_status',
                'source_query', 'mapped_facets', 'expanded_terms', 'unsupported_facets', 'omitted_facets',
                'unresolved_facets', 'compressed_facets', 'approximated_facets', 'source_native_identifiers',
                'retrieval_method', 'retrieval_timestamp', 'source_version', 'projection_version',
                'fidelity_class', 'projection_warnings',
            ],
            array_keys($envelope->toArray()),
        );
    }

    public function test_contract_is_not_wired_into_runtime(): void
    {
        $appDir = dirname(__DIR__, 5).DIRECTORY_SEPARATOR.'app';
        $this->assertDirectoryExists($appDir);

        $references = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || in_array($file->getFilename(), self::NEW_CLASS_FILES, true)) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (str_contains($source, 'ProjectionQueryConstraints') || str_contains($source, 'ProjectionFacetConstraint')) {
                $references[] = $file->getPathname();
            }
        }

        $this->assertSame([], $references);
    }

    /**
     * Derives against a decision evaluated with a query_constrainable requirement for every string facet code.
     *
     * @param  list<mixed>  $facetCodes
     * @param  list<CapabilityRecord>  $records
     * @param  list<mixed>  $warnings
     */
    private function derive(
        array $facetCodes,
        array $records,
        ?ProjectionRetrievalMethod $retrievalMethod = null,
        array $warnings = [],
    ): ProjectionQueryConstraints {
        $required = array_values(array_filter(
            $facetCodes,
            static fn ($code) => is_string($code) && trim($code) !== '',
        ));
        [$cap, $path] = $this->upstream($records, $required === [] ? ['species'] : $required);

        return $this->deriveFrom($cap, $facetCodes, $retrievalMethod, $warnings, $path);
    }

    /**
     * @param  list<mixed>  $facetCodes
     * @param  list<mixed>  $warnings
     */
    private function deriveFrom(
        CapabilityDecisionIdentity $cap,
        array $facetCodes,
        ?ProjectionRetrievalMethod $retrievalMethod = null,
        array $warnings = [],
        ?PathDecisionIdentity $path = null,
    ): ProjectionQueryConstraints {
        return (new ProjectionQueryConstraintsFactory())->derive(
            CanonicalQueryId::fromString('csq-canonical-001'),
            $cap,
            $path ?? $this->path($cap),
            $facetCodes,
            $retrievalMethod,
            $warnings,
        );
    }

    /**
     * @param  list<CapabilityRecord>  $records
     * @param  list<string>  $requiredFacetCodes
     * @return array{0: CapabilityDecisionIdentity, 1: PathDecisionIdentity}
     */
    private function upstream(array $records = [], array $requiredFacetCodes = ['species'], ?string $canonical = 'cid_apis'): array
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('G3-01'),
            $canonical === null ? null : CanonicalSourceIdentityId::fromString($canonical),
        );
        $cap = $this->evaluate(
            $records,
            array_map(static fn (string $c) => CapabilityDimension::scientificFacetQueryConstrainable($c), $requiredFacetCodes),
            subject: $subject,
        );

        return [$cap, $this->path($cap)];
    }

    /**
     * @param  list<CapabilityRecord>  $records
     * @param  list<CapabilityDimension>|null  $required  defaults to query_constrainable species
     */
    private function evaluate(
        array $records,
        ?array $required = null,
        string $decisionId = 'cap-dec-001',
        ?CapabilitySubject $subject = null,
    ): CapabilityDecisionIdentity {
        return (new CapabilityRequirementEvaluator())->evaluate(
            $subject ?? $this->subject(),
            CapabilityRequirementSet::of($required ?? [CapabilityDimension::scientificFacetQueryConstrainable('species')]),
            $records,
            $decisionId,
            '2026-10-01T00:00:00Z',
        );
    }

    private function path(CapabilityDecisionIdentity $cap): PathDecisionIdentity
    {
        $mapper = new CapabilityToPathEligibilityMapper();

        return PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-001'),
            PathId::fromString('path-inst-g3-01-p01'),
            PathFamily::P01,
            $cap,
            $mapper->suggestedStatus($mapper->map($cap->andResultClass)),
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $outcomes
     */
    private function handBuiltDecision(array $outcomes): CapabilityDecisionIdentity
    {
        return CapabilityDecisionIdentity::create(
            'cap-dec-001',
            $this->subject(),
            $outcomes,
            [],
            CapabilityAndResultClass::ALL_VERIFIED,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function outcome(string $facetCode, string $state, ?string $freshness = null): array
    {
        $dimension = CapabilityDimension::scientificFacetQueryConstrainable($facetCode);

        return [
            'dimension_key' => $dimension->key(),
            'family' => $dimension->family->value,
            'code' => $dimension->code,
            'state' => $state,
            'limitation_text' => null,
            'missing_record' => false,
            'evidence_freshness_class' => $freshness,
        ];
    }

    private function subject(): CapabilitySubject
    {
        return CapabilitySubject::of(
            AdrMembershipId::fromString('G3-01'),
            CanonicalSourceIdentityId::fromString('cid_apis'),
        );
    }

    private function queryConstrainableRecord(
        string $facetCode,
        CapabilityState $state,
        CapabilityEvidenceFreshness $freshness,
    ): CapabilityRecord {
        return CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rec-'.$facetCode),
            $this->subject(),
            CapabilityDimension::scientificFacetQueryConstrainable($facetCode),
            $state,
            $freshness,
            limitationText: $state === CapabilityState::PARTIAL ? 'partial query_constrainable scope' : null,
        );
    }

    private function assertMissing(ProjectionQueryConstraints $constraints): void
    {
        $payload = $constraints->toArray();
        $this->assertSame(
            ['facet_code' => 'species', 'identity_critical' => true, 'capability_state' => 'MISSING', 'constraint' => 'UNKNOWN'],
            $payload['facet_constraints'][0],
        );
        $this->assertSame([['code' => 'QUERY_CONSTRAINT_UNKNOWN', 'message' => self::MISSING_WARNING]], $payload['warnings']);
    }

    private function assertViolation(string $message, callable $operation): void
    {
        try {
            $operation();
        } catch (ProjectionInvariantViolation $e) {
            $this->assertSame($message, $e->getMessage());

            return;
        }
        $this->fail("Expected ProjectionInvariantViolation: {$message}");
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<string>
     */
    private function collectKeys(array $payload): array
    {
        $keys = [];
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $keys[] = $key;
            }
            if (is_array($value)) {
                $keys = [...$keys, ...$this->collectKeys($value)];
            }
        }

        return $keys;
    }

    private function projectionDir(): string
    {
        return dirname(__DIR__, 5).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Services'
            .DIRECTORY_SEPARATOR.'Agriculture'.DIRECTORY_SEPARATOR.'Research'.DIRECTORY_SEPARATOR.'Projection';
    }
}
