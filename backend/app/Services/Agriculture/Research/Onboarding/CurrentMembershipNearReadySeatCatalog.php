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
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonCredential;
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
 * CURRENT-membership (110.v1) seats beyond the R7 next-gate 17.
 *
 * G1-02 / G6-20: owner ACCEPT onboarding ACTIVE (2026-10-18) + CapVer AUTHORIZE;
 * runtime registrations ship when isRuntimeActive() (ACTIVE contract + empty blockers).
 */
final class CurrentMembershipNearReadySeatCatalog
{
    public const ADR_IDS = ['G1-02', 'G6-20'];

    public const AUTHORITY = 'ADR-023 CURRENT membership 110.v1 + 2026-10-18 owner ACCEPT + CapVer AUTHORIZE';

    public const ENDPOINT_EVIDENCE_ORGANIC_EPRINTS = 'https://www.orgprints.org/about_policy.html#oai';

    public const ENDPOINT_EVIDENCE_BHL = 'https://www.biodiversitylibrary.org/docs/api3.html';

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

    /**
     * ACTIVE onboarding contracts for AcceptedOnboardingDecisionCatalog merge.
     *
     * @return array<string, ScientificSourceOnboardingContract>
     */
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
                    $seat->endpointEvidenceUrl ?? self::AUTHORITY,
                    verifiedAt: '2026-10-18T16:00:00+00:00',
                    expiresAt: '2027-10-18T16:00:00+00:00',
                ),
            ],
            familyProfile: $profile,
        );
    }

    /** @return array<string, NextGateEligibleSeat> */
    private static function build(): array
    {
        return [
            'G1-02' => self::organicEprintsSeat(),
            'G6-20' => self::bhlSeat(),
        ];
    }

    private static function organicEprintsSeat(): NextGateEligibleSeat
    {
        $licensing = self::organicEprintsLicensing();
        $profile = new OaiPmhSourceProfile(
            sourceKey: 'organic_eprints',
            displayName: 'Organic Eprints',
            baseUrl: 'https://orgprints.org/cgi/oai2',
            licensing: $licensing,
            requestsPerMinute: 30,
            datestampGranularity: OaiPmhSourceProfile::GRANULARITY_SECOND,
            recordUrlTemplate: 'https://orgprints.org/{local_id}',
        );

        return new NextGateEligibleSeat(
            adrId: 'G1-02',
            displayName: 'Organic Eprints',
            sourceKey: 'organic_eprints',
            primaryFamily: ScientificSourceAccessFamily::OAI_PMH,
            candidateFamilies: ['oai_pmh'],
            dualPeerAdrId: null,
            intendedActivation: SeatActivationState::ACTIVE,
            blockers: [],
            familyProfile: $profile,
            onboardingContract: self::organicEprintsActiveContract($licensing),
            accessNotes: 'Official OAI-PMH base https://orgprints.org/cgi/oai2 (policy: metadata reusable with summary URL). Harvest→LocalIndex via scientific:harvest-oai; dedicated scientific_records connection.',
            licenceNotes: 'Metadata freely re-usable with stable summary URL linkage (orgprints.org/about_policy.html). Documents: fair use download; public re-hosting prohibited.',
            endpointEvidenceUrl: self::ENDPOINT_EVIDENCE_ORGANIC_EPRINTS,
        );
    }

    private static function bhlSeat(): NextGateEligibleSeat
    {
        $licensing = self::bhlLicensing();
        $profile = new RestJsonSourceProfile(
            sourceKey: 'bhl',
            displayName: 'Biodiversity Heritage Library',
            endpoint: 'https://www.biodiversitylibrary.org/api3',
            queryParameter: 'searchterm',
            fieldPaths: [
                'identifier' => ['PartID', 'TitleID', 'ItemID'],
                'title' => 'Title',
                'url' => ['PartUrl', 'TitleUrl', 'ItemUrl'],
                'published' => 'Date',
                'authors' => 'Authors',
                'journal' => 'ContainerTitle',
            ],
            itemsPath: 'Result',
            limitParameter: 'pageSize',
            maxResultsPerRequest: 100,
            fixedParameters: [
                'op' => 'PublicationSearch',
                'format' => 'json',
            ],
            authorNamePath: 'Name',
            credential: new RestJsonCredential(
                'agricultural_intelligence.bhl.api_key',
                RestJsonCredential::PLACEMENT_QUERY,
                'apikey',
            ),
            licensing: $licensing,
            requestsPerMinute: 30,
            responseStatusPath: 'Status',
            responseStatusOkValue: 'ok',
            responseErrorMessagePath: 'ErrorMessage',
        );

        return new NextGateEligibleSeat(
            adrId: 'G6-20',
            displayName: 'Biodiversity Heritage Library (BHL)',
            sourceKey: 'bhl',
            primaryFamily: ScientificSourceAccessFamily::REST_API,
            candidateFamilies: ['rest_api', 'oai_pmh', 'bulk_dataset'],
            dualPeerAdrId: null,
            intendedActivation: SeatActivationState::ACTIVE,
            blockers: [],
            familyProfile: $profile,
            onboardingContract: self::bhlActiveContract($licensing),
            accessNotes: 'REST API v3 https://www.biodiversitylibrary.org/api3?op=PublicationSearch; apikey via BHL_API_KEY. PublicationSearch items commonly expose PartID/PartUrl (Item/Title fallbacks supported).',
            licenceNotes: 'BHL metadata CC0 1.0; content PD or rights-holder CC. Retrieval of search metadata permitted.',
            endpointEvidenceUrl: self::ENDPOINT_EVIDENCE_BHL,
        );
    }

    private static function organicEprintsActiveContract(
        ScientificSourceLicensingDeclaration $licensing,
    ): ScientificSourceOnboardingContract {
        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r11-20261018-G1-02',
            adrId: AdrMembershipId::fromString('G1-02'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('adr023.canonical.seat.G1-02'),
            identityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_ORGANIC_EPRINTS,
                'https://orgprints.org/cgi/oai2',
                'IC-ER 2026-10-13 G1-02',
            ],
            integrationClassificationRef: 'ic-r11-20261018-G1-02-oai_pmh',
            capabilityDecisionIdentity: 'cap-r11-20261018-G1-02-search',
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::VERIFIED,
            ],
            capabilityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_ORGANIC_EPRINTS,
                'LocalIndex GEN=2 ACTIVE_ROWS=189 cross-process probe 2026-10-18',
            ],
            accessMethods: [CapabilityAccessMethod::OAI_PMH, CapabilityAccessMethod::METADATA_ACCESS],
            licensing: $licensing,
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: 'path-r11-G1-02-p12',
            pathDecisionIdentity: 'path-decision-r11-20261018-G1-02',
            pathEligibilityState: PathEligibilityState::ELIGIBLE,
            pathStatus: PathStatus::SELECTED,
            projectionIdentity: 'projection-r11-G1-02-oai-localindex',
            fidelityClass: 'SOURCE_NATIVE',
            facetAccounting: 'unsupported_facets_not_claimed',
            stage3Relationship: 'organic_eprints',
            selectorEligibility: 'GOVERNED_EXPANSION',
            activationState: SeatActivationState::ACTIVE,
            groupWaveLabel: 'current_membership_110_owner_accept_2026_10_18',
            decisionActor: 'owner-authorized-r11-execution',
            decisionTimestamp: new DateTimeImmutable('2026-10-18T16:00:00+00:00'),
            rationale: 'G1-02 Organic Eprints: owner ACCEPT onboarding ACTIVE; CapVer AUTHORIZE with OAI-PMH LocalIndex evidence (GEN=2/189). PostgreSQL production LocalIndex parity remains OD-PGSQL (infra), not an onboarding reject.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['current_membership_110', 'od_g1_02_accept', 'capver_authorized'],
            supersedesDecisionId: 'onb-r10-20261017-G1-02',
        );
    }

    private static function bhlActiveContract(
        ScientificSourceLicensingDeclaration $licensing,
    ): ScientificSourceOnboardingContract {
        return new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r11-20261018-G6-20',
            adrId: AdrMembershipId::fromString('G6-20'),
            canonicalIdentityId: CanonicalSourceIdentityId::fromString('adr023.canonical.seat.G6-20'),
            identityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_BHL,
                'https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/',
                'IC-ER 2026-10-13 G6-20',
            ],
            integrationClassificationRef: 'ic-r11-20261018-G6-20-rest',
            capabilityDecisionIdentity: 'cap-r11-20261018-G6-20-search',
            capabilityDimensionStates: [
                ScientificSourceOperation::SEARCH->value => CapabilityState::VERIFIED,
            ],
            capabilityEvidenceRefs: [
                self::ENDPOINT_EVIDENCE_BHL,
                'Live PublicationSearch Status=ok 2026-10-18 (credential via config path; value never logged)',
            ],
            accessMethods: [CapabilityAccessMethod::API, CapabilityAccessMethod::METADATA_ACCESS],
            licensing: $licensing,
            accessState: 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            pathId: 'path-r11-G6-20-p12',
            pathDecisionIdentity: 'path-decision-r11-20261018-G6-20',
            pathEligibilityState: PathEligibilityState::ELIGIBLE,
            pathStatus: PathStatus::SELECTED,
            projectionIdentity: 'projection-r11-G6-20-publication-search',
            fidelityClass: 'SOURCE_NATIVE',
            facetAccounting: 'unsupported_facets_not_claimed',
            stage3Relationship: 'bhl',
            selectorEligibility: 'GOVERNED_EXPANSION',
            activationState: SeatActivationState::ACTIVE,
            groupWaveLabel: 'current_membership_110_owner_accept_2026_10_18',
            decisionActor: 'owner-authorized-r11-execution',
            decisionTimestamp: new DateTimeImmutable('2026-10-18T16:00:00+00:00'),
            rationale: 'G6-20 BHL: owner ACCEPT onboarding ACTIVE; CapVer AUTHORIZE with live PublicationSearch evidence. Production registration uses GOVERNED_ONBOARDED; ephemeral LEGACY probes are not activation.',
            stalenessPolicyRef: 'adr023.onboarding.staleness.v1',
            correlationHooks: ['current_membership_110', 'od_g6_20_accept', 'capver_authorized'],
            supersedesDecisionId: 'onb-r10-20261017-G6-20',
        );
    }

    private static function organicEprintsLicensing(): ScientificSourceLicensingDeclaration
    {
        return new ScientificSourceLicensingDeclaration(
            [
                ScientificSourceUsage::RETRIEVAL->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::CITATION_LINKING->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::METADATA_STORAGE->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::FULL_TEXT_STORAGE->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::EXCERPT_DISPLAY->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::REDISTRIBUTION->value => ScientificSourceUsageStatus::PROHIBITED,
            ],
            licenceIdentifier: 'Organic Eprints policy: metadata reusable with summary URL linkage; documents fair use / no public re-host',
            termsReference: 'https://www.orgprints.org/about_policy.html',
            evidenceReference: 'IC-ER 2026-10-13 G1-02 + about_policy.html OAI quote (access date 2026-10-17)',
        );
    }

    private static function bhlLicensing(): ScientificSourceLicensingDeclaration
    {
        return new ScientificSourceLicensingDeclaration(
            [
                ScientificSourceUsage::RETRIEVAL->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::CITATION_LINKING->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::METADATA_STORAGE->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::FULL_TEXT_STORAGE->value => ScientificSourceUsageStatus::CONDITIONAL,
                ScientificSourceUsage::EXCERPT_DISPLAY->value => ScientificSourceUsageStatus::PERMITTED,
                ScientificSourceUsage::REDISTRIBUTION->value => ScientificSourceUsageStatus::CONDITIONAL,
            ],
            licenceIdentifier: 'BHL metadata CC0 1.0; content PD or rights-holder permission',
            termsReference: 'https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/',
            evidenceReference: 'IC-ER 2026-10-13 G6-20 license_and_reuse',
        );
    }
}
