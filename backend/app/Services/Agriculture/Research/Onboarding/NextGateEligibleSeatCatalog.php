<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonSourceProfile;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Registry\ConfigFlagSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use DateTimeImmutable;

/**
 * Authoritative inventory of the seventeen R7 next-gate candidates (ADR-023 §8.41.2).
 *
 * Source: `local_index_reconciliation.next_gate_candidates` in
 * `2026-10-15-R7-SOURCE-ONBOARDING-RECONCILIATION.v2.json`, confirmed in ADR §8.41.2.
 *
 * Activation is selective: only seats whose identity, licence, endpoint and onboarding
 * prerequisites are satisfied ship an ACTIVE contract and a runtime registration.
 */
final class NextGateEligibleSeatCatalog
{
    /** Authoritative seat order from R7 §8.41.2. */
    public const ADR_IDS = [
        'G1-01', 'G1-13', 'G1-15', 'G1-17', 'G1-19',
        'G2-15', 'G2-23', 'G3-05', 'G4-01', 'G4-02', 'G5-04',
        'G6-03', 'G6-04', 'G6-06', 'G6-08', 'G6-12', 'G6-16',
    ];

    public const AUTHORITY = 'ADR-023 §8.41.2 next_gate_candidates / R7 reconciliation v2';

    public const ENDPOINT_EVIDENCE_GBIF = 'docs/architecture/science-source-expansion/2026-10-16-NEXT-GATE-17-ENDPOINT-VERIFICATION.v1.json#G5-04';

    public const ENDPOINT_EVIDENCE_AGRIS = 'docs/architecture/science-source-expansion/2026-10-16-NEXT-GATE-17-ENDPOINT-VERIFICATION.v1.json#G1-01';

    public const ENDPOINT_EVIDENCE_DR_DUKE = 'https://phytochem.nal.usda.gov/about';

    public const LOCAL_INDEX_CAPABILITY_EVIDENCE = 'docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-ACTIVATION-ELIGIBILITY.v1.json#local_index_path';

    /** @var array<string, NextGateEligibleSeat>|null */
    private static ?array $seats = null;

    /** @return list<string> */
    public static function adrIds(): array
    {
        return self::ADR_IDS;
    }

    /** @return array<string, NextGateEligibleSeat> */
    public static function all(): array
    {
        return self::$seats ??= self::build();
    }

    public static function get(string $adrId): ?NextGateEligibleSeat
    {
        return self::all()[$adrId] ?? null;
    }

    /** @return array<string, ScientificSourceOnboardingContract> */
    public static function acceptedActiveContracts(): array
    {
        $out = [];
        foreach (self::all() as $adrId => $seat) {
            $contract = $seat->onboardingContract;
            if ($contract !== null && $contract->activationState === SeatActivationState::ACTIVE) {
                $out[$adrId] = $contract;
            }
        }

        return $out;
    }

    /**
     * Runtime registrations for seats that cleared activation prerequisites.
     *
     * @return list<ScientificSourceRegistration>
     */
    public static function activatedRegistrations(): array
    {
        $registrations = [];
        foreach (self::all() as $seat) {
            if (! $seat->isRuntimeActive() || $seat->familyProfile === null || $seat->onboardingContract === null) {
                continue;
            }
            $registrations[] = self::registrationFor($seat);
        }

        return $registrations;
    }

    /** Test seam. */
    public static function swap(?array $seats): void
    {
        self::$seats = $seats;
        AcceptedOnboardingDecisionCatalog::swap(null);
    }

    private static function registrationFor(NextGateEligibleSeat $seat): ScientificSourceRegistration
    {
        $profile = $seat->familyProfile;
        assert($profile instanceof ScientificSourceFamilyProfile);
        $family = $seat->primaryFamily;
        $adapterClass = $family === ScientificSourceAccessFamily::REST_API
            ? RestJsonScientificSourceAdapter::class
            : LocalIndexScientificSourceAdapter::class;
        $verifiedAt = '2026-10-16T16:00:00+00:00';
        $expiresAt = '2027-10-16T16:00:00+00:00';
        $evidence = match ($seat->adrId) {
            'G5-04' => self::ENDPOINT_EVIDENCE_GBIF,
            'G1-01' => self::ENDPOINT_EVIDENCE_AGRIS,
            'G1-17' => self::ENDPOINT_EVIDENCE_DR_DUKE,
            default => $seat->endpointEvidenceUrl ?? self::LOCAL_INDEX_CAPABILITY_EVIDENCE,
        };

        return new ScientificSourceRegistration(
            sourceKey: $seat->sourceKey,
            adapterClass: $adapterClass,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::GOVERNED_ONBOARDED,
            accessFamily: $family,
            selectionRole: ScientificSourceSelectionRole::GOVERNED_EXPANSION,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new ConfigFlagSourceEnablement(
                'agricultural_intelligence.'.$seat->sourceKey.'.enabled',
                true,
            ),
            pathFamily: PathFamily::P12,
            adrId: AdrMembershipId::fromString($seat->adrId),
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    $evidence,
                    verifiedAt: $verifiedAt,
                    expiresAt: $expiresAt,
                ),
            ],
            familyProfile: $profile,
        );
    }

    /** @return array<string, NextGateEligibleSeat> */
    private static function build(): array
    {
        $seats = [];
        foreach (self::definitions() as $row) {
            $seats[$row['adr_id']] = self::seatFromDefinition($row);
        }

        return $seats;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function seatFromDefinition(array $row): NextGateEligibleSeat
    {
        $primary = ScientificSourceAccessFamily::from($row['primary_family']);
        $profile = null;
        $contract = null;
        $blockers = $row['blockers'];
        $activation = SeatActivationState::from($row['intended_activation']);

        if ($row['adr_id'] === 'G5-04' && $activation === SeatActivationState::ACTIVE) {
            $profile = self::gbifRestProfile();
            $contract = self::gbifActiveContract();
            $blockers = [];
        } elseif ($row['adr_id'] === 'G1-01' && $activation === SeatActivationState::ACTIVE) {
            $profile = self::scaffoldProfile($row, $primary);
            $contract = self::agrisBoundedActiveContract($row);
            $blockers = [];
        } elseif ($row['adr_id'] === 'G1-17' && $activation === SeatActivationState::ACTIVE) {
            $profile = self::scaffoldProfile($row, $primary);
            $contract = self::drDukeLimitedActiveContract($row);
            $blockers = [];
        } elseif ($row['build_profile'] ?? false) {
            $profile = self::scaffoldProfile($row, $primary);
        }

        if ($activation !== SeatActivationState::ACTIVE && ($row['build_not_ready_contract'] ?? true)) {
            $contract = self::inactiveContract($row, $primary, $activation);
        }

        return new NextGateEligibleSeat(
            adrId: $row['adr_id'],
            displayName: $row['display_name'],
            sourceKey: $row['source_key'],
            primaryFamily: $primary,
            candidateFamilies: $row['candidate_families'],
            dualPeerAdrId: $row['dual_peer'] ?? null,
            intendedActivation: $activation,
            blockers: $blockers,
            familyProfile: $profile,
            onboardingContract: $contract,
            accessNotes: $row['access_notes'],
            licenceNotes: $row['licence_notes'],
            endpointEvidenceUrl: $row['endpoint_evidence_url'] ?? null,
        );
    }

    private static function gbifRestProfile(): RestJsonSourceProfile
    {
        return new RestJsonSourceProfile(
            sourceKey: 'gbif',
            displayName: 'GBIF',
            endpoint: 'https://api.gbif.org/v1/species/search',
            queryParameter: 'q',
            fieldPaths: [
                'identifier' => 'key',
                'title' => 'scientificName',
                'abstract' => 'rank',
                'published' => 'publishedIn',
            ],
            itemsPath: 'results',
            limitParameter: 'limit',
            maxResultsPerRequest: 50,
            fixedParameters: [],
            authorNamePath: null,
            licensing: self::gbifLicensing(),
            requestsPerMinute: 60,
        );
    }

    private static function gbifLicensing(): ScientificSourceLicensingDeclaration
    {
        return new ScientificSourceLicensingDeclaration(
            [
                ScientificSourceUsage::RETRIEVAL->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::CITATION_LINKING->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::METADATA_STORAGE->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::FULL_TEXT_STORAGE->value => ScientificSourceUsageStatus::PROHIBITED,
                ScientificSourceUsage::EXCERPT_DISPLAY->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::REDISTRIBUTION->value => ScientificSourceUsageStatus::CONDITIONAL,
            ],
            licenceIdentifier: 'GBIF Terms of Use; per-dataset CC0|CC-BY|CC-BY-NC',
            termsReference: 'https://www.gbif.org/terms',
            evidenceReference: 'IC evidence G5-04 license_and_reuse + techdocs.gbif.org/en/openapi/',
        );
    }

    private static function gbifActiveContract(): ScientificSourceOnboardingContract
    {
        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r9-20261016-G5-04',
            adrId: AdrMembershipId::fromString('G5-04'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('adr023.canonical.seat.G5-04'),
            identityEvidenceRefs: [
                'https://techdocs.gbif.org/en/openapi/',
                'https://www.gbif.org/citation-guidelines',
                'IC-ER 2026-10-13 G5-04',
            ],
            integrationClassificationRef: 'ic-r9-20261016-G5-04-rest',
            capabilityDecisionIdentity: 'cap-r9-20261016-G5-04-search',
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::VERIFIED,
            ],
            capabilityEvidenceRefs: [self::ENDPOINT_EVIDENCE_GBIF],
            accessMethods: [CapabilityAccessMethod::API, CapabilityAccessMethod::METADATA_ACCESS],
            licensing: self::gbifLicensing(),
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: 'path-r9-G5-04-p12',
            pathDecisionIdentity: 'path-decision-r9-20261016-G5-04',
            pathEligibilityState: PathEligibilityState::ELIGIBLE,
            pathStatus: PathStatus::SELECTED,
            projectionIdentity: 'projection-r9-G5-04-species-search',
            fidelityClass: 'SOURCE_NATIVE',
            facetAccounting: 'unsupported_facets_not_claimed',
            stage3Relationship: 'gbif',
            selectorEligibility: 'GOVERNED_EXPANSION',
            activationState: SeatActivationState::ACTIVE,
            groupWaveLabel: 'next_gate_17',
            decisionActor: 'owner-authorized-r9-execution',
            decisionTimestamp: new DateTimeImmutable('2026-10-16T16:00:00+00:00'),
            rationale: 'G5-04 GBIF cleared identity documentary evidence, documented REST API, licence terms permitting retrieval, and authorized species-search endpoint verification under the 17-seat activation mandate.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['next_gate_17', 'r9_activation'],
            supersedesDecisionId: 'onb-r7-20261015-G5-04',
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function agrisBoundedActiveContract(array $row): ScientificSourceOnboardingContract
    {
        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r12-20261018-G1-01',
            adrId: AdrMembershipId::fromString('G1-01'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('adr023.canonical.seat.G1-01'),
            identityEvidenceRefs: [
                'https://agris.fao.org/ods/AGRIS.ODS.xml',
                'data.apps.fao.org/catalog/dataset/agris',
                'IC-ER 2026-10-13 G1-01',
            ],
            integrationClassificationRef: 'ic-r12-20261018-G1-01-bulk-bounded',
            capabilityDecisionIdentity: 'cap-r12-20261018-G1-01-search',
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::VERIFIED,
            ],
            capabilityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_AGRIS,
                'LocalIndex GEN=4 ACTIVE_ROWS=1317 bounded ODS subset 2026-10-18',
                'OD-G1-01-SCOPE-BOUNDED-SUBSET + OD-G1-01-ONBOARDING-ACTIVE',
            ],
            accessMethods: [CapabilityAccessMethod::BULK_DOWNLOAD, CapabilityAccessMethod::METADATA_ACCESS],
            licensing: self::licensingFromDefinition($row),
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: 'path-r12-G1-01-p12',
            pathDecisionIdentity: 'path-decision-r12-20261018-G1-01',
            pathEligibilityState: PathEligibilityState::ELIGIBLE,
            pathStatus: PathStatus::SELECTED,
            projectionIdentity: 'projection-r12-G1-01-ods-bounded-localindex',
            fidelityClass: 'SOURCE_NATIVE',
            facetAccounting: 'unsupported_facets_not_claimed',
            stage3Relationship: 'agris',
            selectorEligibility: 'GOVERNED_EXPANSION',
            activationState: SeatActivationState::ACTIVE,
            groupWaveLabel: 'next_gate_17_bounded_scope_2026_10_18',
            decisionActor: 'owner-authorized-r12-engineering-scope',
            decisionTimestamp: new DateTimeImmutable('2026-10-18T17:40:00+00:00'),
            rationale: 'G1-01 AGRIS bounded licensed subset (GEN=4/1317; cursor 10/1244) accepted for GOVERNED activation. Full corpus remains a separate milestone. Per-provider CC-BY-4.0 only on ingested artifacts.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['next_gate_17', 'od_g1_01_bounded_accept', 'capver_authorized'],
            supersedesDecisionId: 'onb-r9-20261016-G1-01',
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function drDukeLimitedActiveContract(array $row): ScientificSourceOnboardingContract
    {
        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r12-20261018-G1-17',
            adrId: AdrMembershipId::fromString('G1-17'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('adr023.canonical.seat.G1-17'),
            identityEvidenceRefs: [
                'https://phytochem.nal.usda.gov/about',
                'IC-ER 2026-10-13 G1-17',
                '2026-10-18-DR-DUKE-RECONCILIATION.v1.json',
            ],
            integrationClassificationRef: 'ic-r12-20261018-G1-17-static-limited',
            capabilityDecisionIdentity: 'cap-r12-20261018-G1-17-search',
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::VERIFIED,
            ],
            capabilityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_DR_DUKE,
                'LocalIndex GEN=2 ACTIVE_ROWS=1500 limited projection 2026-10-18',
                'OD-G1-17-SCOPE-LIMITED-PROJECTION + OD-G1-17-ONBOARDING-ACTIVE',
            ],
            accessMethods: [CapabilityAccessMethod::STATIC_DOWNLOAD, CapabilityAccessMethod::METADATA_ACCESS],
            licensing: self::licensingFromDefinition($row),
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: 'path-r12-G1-17-p12',
            pathDecisionIdentity: 'path-decision-r12-20261018-G1-17',
            pathEligibilityState: PathEligibilityState::ELIGIBLE,
            pathStatus: PathStatus::SELECTED,
            projectionIdentity: 'projection-r12-G1-17-limited-search',
            fidelityClass: 'SOURCE_NATIVE',
            facetAccounting: 'unsupported_facets_not_claimed',
            stage3Relationship: 'dr_duke',
            selectorEligibility: 'GOVERNED_EXPANSION',
            activationState: SeatActivationState::ACTIVE,
            groupWaveLabel: 'next_gate_17_limited_projection_2026_10_18',
            decisionActor: 'owner-authorized-r12-engineering-scope',
            decisionTimestamp: new DateTimeImmutable('2026-10-18T17:40:00+00:00'),
            rationale: 'G1-17 Dr. Duke limited legitimate search projection (GEN=2/1500; 16-table reconciliation) accepted. FARMACY≠FARMACY_NEW. ETHNOBOT traditional-use only. Full projection remains a separate milestone.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['next_gate_17', 'od_g1_17_limited_accept', 'capver_authorized'],
            supersedesDecisionId: 'onb-r9-20261016-G1-17',
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function inactiveContract(
        array $row,
        ScientificSourceAccessFamily $family,
        SeatActivationState $activation,
    ): ScientificSourceOnboardingContract {
        $method = match ($family) {
            ScientificSourceAccessFamily::REST_API => CapabilityAccessMethod::API,
            ScientificSourceAccessFamily::OAI_PMH => CapabilityAccessMethod::OAI_PMH,
            ScientificSourceAccessFamily::BULK_DATASET => CapabilityAccessMethod::BULK_DOWNLOAD,
            ScientificSourceAccessFamily::STATIC_DATASET => CapabilityAccessMethod::STATIC_DOWNLOAD,
            default => CapabilityAccessMethod::MACHINE_ACCESS,
        };
        $state = in_array($activation, [SeatActivationState::DEFERRED, SeatActivationState::NOT_READY], true)
            ? $activation
            : SeatActivationState::NOT_READY;

        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r9-20261016-'.$row['adr_id'],
            adrId: AdrMembershipId::fromString($row['adr_id']),
            canonicalIdentityId: null,
            identityEvidenceRefs: [],
            integrationClassificationRef: null,
            capabilityDecisionIdentity: null,
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::UNVERIFIED,
            ],
            capabilityEvidenceRefs: [],
            accessMethods: [$method],
            licensing: self::licensingFromDefinition($row),
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: null,
            pathDecisionIdentity: null,
            pathEligibilityState: null,
            pathStatus: null,
            projectionIdentity: null,
            fidelityClass: null,
            facetAccounting: null,
            stage3Relationship: null,
            selectorEligibility: ScientificSourceOnboardingContract::SELECTOR_NOT_SELECTABLE,
            activationState: $state,
            groupWaveLabel: 'next_gate_17',
            decisionActor: 'owner-authorized-r9-execution',
            decisionTimestamp: new DateTimeImmutable('2026-10-16T16:00:00+00:00'),
            rationale: 'Scaffolded next-gate seat; activation withheld until blockers are cleared: '.implode(', ', $row['blockers']).'.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['next_gate_17'],
            supersedesDecisionId: 'onb-r7-20261015-'.$row['adr_id'],
        );
    }

    /**
     * Documentary IC licence → contract declaration. Does not activate the seat.
     * Unresolved remains unresolved; never treat missing licence as permission.
     *
     * @param  array<string, mixed>  $row
     */
    private static function licensingFromDefinition(array $row): ScientificSourceLicensingDeclaration
    {
        $spec = $row['licensing'] ?? null;
        if (! is_array($spec)) {
            return ScientificSourceLicensingDeclaration::unresolved();
        }
        $usages = [];
        foreach ($spec['usages'] ?? [] as $key => $status) {
            if (! is_string($key) || ! is_string($status)) {
                continue;
            }
            $usageKey = strtolower($key);
            if (ScientificSourceUsage::tryFrom($usageKey) === null) {
                continue;
            }
            $enum = ScientificSourceUsageStatus::tryFrom(strtolower($status))
                ?? ScientificSourceUsageStatus::tryFrom($status);
            if ($enum !== null) {
                $usages[$usageKey] = $enum;
            }
        }

        return new ScientificSourceLicensingDeclaration(
            $usages,
            licenceIdentifier: isset($spec['licence_identifier']) ? (string) $spec['licence_identifier'] : null,
            termsReference: isset($spec['terms_reference']) ? (string) $spec['terms_reference'] : null,
            evidenceReference: isset($spec['evidence_reference']) ? (string) $spec['evidence_reference'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function scaffoldProfile(array $row, ScientificSourceAccessFamily $family): ?ScientificSourceFamilyProfile
    {
        $key = $row['source_key'];
        $name = $row['display_name'];

        $licensing = self::licensingFromDefinition($row);

        return match ($family) {
            ScientificSourceAccessFamily::BULK_DATASET, ScientificSourceAccessFamily::STATIC_DATASET => new BulkDatasetSourceProfile(
                sourceKey: $key,
                displayName: $name,
                family: $family,
                format: BulkDatasetSourceProfile::FORMAT_JSONL,
                fieldPaths: [
                    'identifier' => 'id',
                    'title' => 'title',
                    'abstract' => 'abstract',
                    'doi' => 'doi',
                    'url' => 'url',
                    'published' => 'published',
                    'year' => 'year',
                    'authors' => 'authors',
                ],
                licensing: $licensing,
                // Full AGRIS ODS / Dr. Duke searchable projections exceed the 100k default.
                maxRecords: 500_000,
            ),
            ScientificSourceAccessFamily::OAI_PMH => isset($row['oai_base_url'])
                ? new OaiPmhSourceProfile(
                    sourceKey: $key,
                    displayName: $name,
                    baseUrl: $row['oai_base_url'],
                    licensing: $licensing,
                )
                : null,
            default => null,
        };
    }

    /**
     * Seat definitions derived from R7 families + IC evidence review (2026-10-13).
     *
     * @return list<array<string, mixed>>
     */
    private static function definitions(): array
    {
        $localIndexBlockers = [
            'identity_not_verified',
            'licence_unresolved',
            'endpoint_not_tested',
            'local_index_store_not_deployed',
            'local_index_not_built',
            'onboarding_decision_not_accepted',
            'capability_unverified',
        ];
        $dualBlockers = array_values(array_unique(array_merge(
            ['dual_peer_runtime_deferred'],
            $localIndexBlockers,
        )));

        return [
            [
                'adr_id' => 'G1-01',
                'display_name' => 'FAO AGRIS',
                'source_key' => 'agris',
                'primary_family' => 'bulk_dataset',
                'candidate_families' => ['bulk_dataset'],
                'intended_activation' => 'ACTIVE',
                'blockers' => [],
                'access_notes' => 'BOUNDED ACTIVATION: LocalIndex generation 76 incremental, 96612 active rows (isolated copy verified 2026-10-22 after v6 ingest; was 87984 at generation 29; 3 withdrawn). Delta +8628 from 46 CC-BY-4.0 providers capped at 200 records each. Checkpoint completed 143/1244. Full corpus not claimed. DCAT root https://agris.fao.org/ods/AGRIS.ODS.xml.',
                'licence_notes' => 'Per-provider CC-BY-4.0 on ingested ODS artifacts only; no cross-provider license generalization. Legacy zip pages citing CC BY 3.0 IGO are stale relative to ingested DCAT artifacts.',
                'endpoint_evidence_url' => 'https://agris.fao.org/ods/AGRIS.ODS.xml',
                'licensing' => [
                    'licence_identifier' => 'CC-BY-4.0 (AGRIS.ODS.xml / provider subsets; legacy zip pages may still cite CC BY 3.0 IGO)',
                    'terms_reference' => 'https://creativecommons.org/licenses/by/4.0/',
                    'evidence_reference' => 'AGRIS.ODS.BG3.xml dc:license CC-BY-4.0 + data.apps.fao.org/catalog/dataset/agris (2026-10-18)',
                    'usages' => [
                        'retrieval' => 'PERMITTED',
                        'citation_linking' => 'PERMITTED',
                        'metadata_storage' => 'PERMITTED',
                        'full_text_storage' => 'CONDITIONAL',
                        'excerpt_display' => 'PERMITTED',
                        'redistribution' => 'CONDITIONAL',
                    ],
                ],
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G1-13',
                'display_name' => 'HortDB V1.0',
                'source_key' => 'hortdb',
                'primary_family' => 'bulk_dataset',
                'candidate_families' => ['bulk_dataset'],
                'dual_peer' => 'G6-03',
                'intended_activation' => 'NOT_READY',
                'blockers' => [
                    'identity_not_verified',
                    'endpoint_not_tested',
                    'local_index_store_not_deployed',
                    'local_index_not_built',
                    'onboarding_decision_not_accepted',
                    'capability_unverified',
                    'licence_commercial_contact_required',
                ],
                'access_notes' => 'Genomics bulk resource; no formal API documented in IC review.',
                'licence_notes' => 'Research use unrestricted; commercial use requires maintainer contact (HortDB FAQ).',
                'endpoint_evidence_url' => 'https://bioinformatics.hainanu.edu.cn/hortdb/faq_allList.action',
                'licensing' => [
                    'licence_identifier' => 'HortDB FAQ: research unrestricted; commercial contact required',
                    'terms_reference' => 'https://bioinformatics.hainanu.edu.cn/hortdb/faq_allList.action',
                    'evidence_reference' => 'IC-ER 2026-10-13 G1-13 license_and_reuse',
                    'usages' => [
                        'retrieval' => 'CONDITIONAL',
                        'citation_linking' => 'PERMITTED',
                        'metadata_storage' => 'CONDITIONAL',
                        'full_text_storage' => 'CONDITIONAL',
                        'excerpt_display' => 'CONDITIONAL',
                        'redistribution' => 'CONDITIONAL',
                    ],
                ],
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G1-15',
                'display_name' => 'sCentInDB',
                'source_key' => 'scentindb',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G6-04',
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'Web UI only; no API or bulk terms on fetched pages.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G1-17',
                'display_name' => "Dr. Duke's Databases",
                'source_key' => 'dr_duke',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G6-06',
                'intended_activation' => 'ACTIVE',
                'blockers' => [],
                'access_notes' => 'LIMITED PROJECTION ACTIVATION: LocalIndex generation 19, 211948 active rows (isolated copy verified 2026-10-22; 500 withdrawn). Active identifier prefixes: FNFTAX 2376, CHEMICALS 29572, ETHNOBOT 60000, FARMACY 60000, FARMACY_NEW 60000. FARMACY≠FARMACY_NEW. ETHNOBOT=traditional-use only. Full multi-table projection not claimed.',
                'licence_notes' => 'CC0 public domain dedication (phytochem.nal.usda.gov/about).',
                'endpoint_evidence_url' => 'https://phytochem.nal.usda.gov/about',
                'licensing' => [
                    'licence_identifier' => 'CC0 1.0',
                    'terms_reference' => 'https://phytochem.nal.usda.gov/about',
                    'evidence_reference' => 'IC-ER 2026-10-13 G1-17 license_and_reuse',
                    'usages' => [
                        'retrieval' => 'PERMITTED',
                        'citation_linking' => 'PERMITTED',
                        'metadata_storage' => 'PERMITTED',
                        'full_text_storage' => 'PERMITTED',
                        'excerpt_display' => 'PERMITTED',
                        'redistribution' => 'PERMITTED',
                    ],
                ],
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G1-19',
                'display_name' => 'Fruit and Nut Cultivars Database',
                'source_key' => 'fncd',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G6-08',
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'No API; About page may be stale.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G2-15',
                'display_name' => 'Andalasian Livestock',
                'source_key' => 'andalasian_livestock',
                'primary_family' => 'oai_pmh',
                'candidate_families' => ['oai_pmh'],
                'intended_activation' => 'NOT_READY',
                'blockers' => [
                    'identity_not_verified',
                    'licence_unresolved',
                    'endpoint_not_tested',
                    'local_index_store_not_deployed',
                    'local_index_not_built',
                    'onboarding_decision_not_accepted',
                    'capability_unverified',
                ],
                'access_notes' => 'OAI-PMH link labelled on journal site; base URL candidates require Identify probe.',
                'licence_notes' => 'Open-access journal claim; machine-readable reuse licence not fetched.',
                'oai_base_url' => 'https://alive.lppm.unand.ac.id/index.php/alive/oai',
                'endpoint_evidence_url' => 'https://alive.lppm.unand.ac.id/index.php/alive/prp',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G2-23',
                'display_name' => 'LivestockDev',
                'source_key' => 'livestockdev',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'No API or licence documented; download pages not fetched in IC review.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G3-05',
                'display_name' => 'OSMIA',
                'source_key' => 'osmia',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => [
                    'identity_not_verified',
                    'endpoint_not_tested',
                    'local_index_store_not_deployed',
                    'local_index_not_built',
                    'onboarding_decision_not_accepted',
                    'capability_unverified',
                    'no_machine_search_api',
                ],
                'access_notes' => 'Journal site; CC BY 4.0 declared. No documented machine search API; LocalIndex only after authorized ingest.',
                'licence_notes' => 'CC BY 4.0 (OSMIA about page).',
                'endpoint_evidence_url' => 'https://www.osmia-journal-hymenoptera.com/about.html',
                'licensing' => [
                    'licence_identifier' => 'CC BY 4.0',
                    'terms_reference' => 'https://www.osmia-journal-hymenoptera.com/about.html',
                    'evidence_reference' => 'IC-ER 2026-10-13 G3-05 license_and_reuse',
                    'usages' => [
                        'retrieval' => 'PERMITTED',
                        'citation_linking' => 'PERMITTED',
                        'metadata_storage' => 'PERMITTED',
                        'full_text_storage' => 'CONDITIONAL',
                        'excerpt_display' => 'PERMITTED',
                        'redistribution' => 'CONDITIONAL',
                    ],
                ],
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G4-01',
                'display_name' => 'SilkBase',
                'source_key' => 'silkbase',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'No API or licence on fetched pages.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G4-02',
                'display_name' => 'KAIKObase',
                'source_key' => 'kaikobase',
                'primary_family' => 'bulk_dataset',
                'candidate_families' => ['bulk_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'Bulk gene-model downloads documented; no API.',
                'licence_notes' => 'Licence unresolved.',
                'endpoint_evidence_url' => 'https://kaikobase.dna.naro.go.jp/KAIKObase_download.html',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G5-04',
                'display_name' => 'GBIF',
                'source_key' => 'gbif',
                'primary_family' => 'rest_api',
                'candidate_families' => ['rest_api', 'bulk_dataset', 'static_dataset'],
                'intended_activation' => 'ACTIVE',
                'blockers' => [],
                'access_notes' => 'Documented REST JSON API at api.gbif.org; species search used for CSQ query text.',
                'licence_notes' => 'GBIF Terms of Use; per-dataset CC0/CC-BY/CC-BY-NC. Retrieval of API search results permitted; redistribution conditional.',
                'endpoint_evidence_url' => 'https://api.gbif.org/v1/species/search',
                'build_profile' => false,
                'build_not_ready_contract' => false,
            ],
            [
                'adr_id' => 'G6-03',
                'display_name' => 'HortDB V1.0',
                'source_key' => 'hortdb_g6',
                'primary_family' => 'bulk_dataset',
                'candidate_families' => ['bulk_dataset'],
                'dual_peer' => 'G1-13',
                'intended_activation' => 'DEFERRED',
                'blockers' => $dualBlockers,
                'access_notes' => 'Dual peer of G1-13; runtime deferred to avoid duplicate evidence.',
                'licence_notes' => 'Same unresolved licence as G1-13.',
                'build_profile' => false,
            ],
            [
                'adr_id' => 'G6-04',
                'display_name' => 'sCentInDB',
                'source_key' => 'scentindb_g6',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G1-15',
                'intended_activation' => 'DEFERRED',
                'blockers' => $dualBlockers,
                'access_notes' => 'Dual peer of G1-15; runtime deferred.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => false,
            ],
            [
                'adr_id' => 'G6-06',
                'display_name' => "Dr. Duke's Phytochemical and Ethnobotanical Databases",
                'source_key' => 'dr_duke_g6',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G1-17',
                'intended_activation' => 'DEFERRED',
                'blockers' => $dualBlockers,
                'access_notes' => 'Dual peer of G1-17; runtime deferred.',
                'licence_notes' => 'CC0 on G1-17 primary; dual not activated.',
                'build_profile' => false,
            ],
            [
                'adr_id' => 'G6-08',
                'display_name' => 'Fruit and Nut Cultivars Database (FNCD)',
                'source_key' => 'fncd_g6',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'dual_peer' => 'G1-19',
                'intended_activation' => 'DEFERRED',
                'blockers' => $dualBlockers,
                'access_notes' => 'Dual peer of G1-19; runtime deferred.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => false,
            ],
            [
                'adr_id' => 'G6-12',
                'display_name' => 'OfIR – Osmanthus fragrans Information Resource',
                'source_key' => 'ofir',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'Web-tool queries only; no API/bulk documentation.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
            [
                'adr_id' => 'G6-16',
                'display_name' => 'Citrus Genome Database (CGD)',
                'source_key' => 'citrus_genome_db',
                'primary_family' => 'static_dataset',
                'candidate_families' => ['static_dataset'],
                'intended_activation' => 'NOT_READY',
                'blockers' => $localIndexBlockers,
                'access_notes' => 'Downloads and web search documented; no API.',
                'licence_notes' => 'Licence unresolved.',
                'build_profile' => true,
            ],
        ];
    }
}
