<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\ScientificSourceProfile;
use DateTimeImmutable;

/**
 * Reconciles every membership seat against its documentary evidence and the repository
 * (ADR-023 R6 §8.40, revised by R7 §8.41). Pure: callers pass decoded inputs and the register's sha256, so this
 * class reads no files, persists nothing, and is not the deferred membership-register loader.
 *
 * Documentary evidence can make a dimension at most PARTIALLY_EVIDENCED. Licensing is never
 * inferred from documented licence text: every seat keeps an unresolved declaration until the
 * owner accepts terms. Every decision it emits is NOT_READY or DEFERRED.
 */
final class ScientificSourceOnboardingReconciler
{
    public const DECISION_ACTOR = 'executor (AI agent, Cursor); owner acceptance pending';

    public const STALENESS_POLICY_REF = 'ADR-023 §8.11.3 staleness_policy_ref: documentary evidence of 2026-10-14; re-review before any binding';

    private const MODALITY_FAMILIES = [
        'api' => ScientificSourceAccessFamily::REST_API,
        'oai_pmh' => ScientificSourceAccessFamily::OAI_PMH,
        'rss' => ScientificSourceAccessFamily::RSS_ATOM,
        'atom' => ScientificSourceAccessFamily::RSS_ATOM,
        'bulk_download' => ScientificSourceAccessFamily::BULK_DATASET,
        'static_download' => ScientificSourceAccessFamily::STATIC_DATASET,
    ];

    /**
     * @param  array<string, class-string>  $familyImplementations  access-family value => implementing class
     */
    public function __construct(
        private readonly array $familyImplementations = [],
    ) {}

    /**
     * Source-family implementations present in this repository. RSS/Atom has none.
     *
     * @return array<string, class-string>
     */
    public static function repositoryFamilyImplementations(): array
    {
        return [
            ScientificSourceAccessFamily::REST_API->value => RestJsonScientificSourceAdapter::class,
            ScientificSourceAccessFamily::OAI_PMH->value => OaiPmhHarvester::class,
            ScientificSourceAccessFamily::BULK_DATASET->value => BulkDatasetReader::class,
            ScientificSourceAccessFamily::STATIC_DATASET->value => BulkDatasetReader::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $register  decoded membership register
     * @param  array<string, mixed>  $evidence  decoded IC evidence review
     * @param  array<string, ScientificSourceProfile>  $profiles  query profiles keyed by sourceKey
     */
    public function reconcile(
        array $register,
        string $registerSha256,
        array $evidence,
        ScientificSourceCatalog $catalog,
        array $profiles,
        DateTimeImmutable $at,
    ): ScientificSourceOnboardingReconciliation {
        $findings = [];
        $seats = $this->seats($register, $findings);
        $records = $this->evidenceRecords($evidence, $findings);
        $this->checkInputs($register, $registerSha256, $evidence, $seats, $records, $findings);
        $this->checkFamilyImplementations($findings);
        $correspondences = $this->checkRuntimeAndProfiles($catalog, $profiles, $seats, $findings);

        $entries = [];
        foreach ($seats as $adrId => $seat) {
            $record = $records[$adrId] ?? null;
            if ($record === null) {
                $findings[] = new OnboardingIntegrityFinding('missing_evidence_record', OnboardingIntegrityFinding::ERROR, $adrId, 'The seat has no evidence record; it cannot be reconciled.');

                continue;
            }
            $this->checkSeatRecord($adrId, $seat, $record, $findings);
            $entries[$adrId] = $this->entry($seat, $record, $correspondences[$adrId] ?? [], $at);
        }

        return new ScientificSourceOnboardingReconciliation(
            entries: $entries,
            findings: $findings,
            inputs: [
                'membership_register_sha256' => $registerSha256,
                'membership_revision' => self::str($register, 'membership_revision') ?? 'UNKNOWN',
                'evidence_schema_id' => self::str($evidence, 'schema_id') ?? 'UNKNOWN',
                'evidence_review_window' => self::str($evidence, 'review_window') ?? 'UNKNOWN',
                'runtime_catalog_keys' => implode(',', $catalog->sourceKeys()),
            ],
            reconciledAt: $at->format(DATE_ATOM),
        );
    }

    /**
     * @param  array<string, mixed>  $seat
     * @param  array<string, mixed>  $record
     * @param  list<string>  $correspondences
     */
    private function entry(array $seat, array $record, array $correspondences, DateTimeImmutable $at): SeatReconciliationEntry
    {
        $adrId = (string) $seat['adr_id'];
        $identity = self::arr($record, 'identity');
        $access = self::arr($record, 'access');
        $auth = self::arr($access, 'auth_and_rate_limits');
        $identityState = self::str($identity, 'evidence_state') ?? 'NOT_ESTABLISHED';
        $accessState = self::str($access, 'evidence_state') ?? 'NOT_DOCUMENTED';
        if (! in_array($accessState, ScientificSourceOnboardingContract::ACCESS_STATES, true)) {
            $accessState = 'NOT_DOCUMENTED';
        }
        $authRequired = self::str($auth, 'auth_required') ?? 'UNKNOWN';
        $fetchFailures = array_values(array_filter(self::arr($record, 'fetch_failures'), 'is_string'));
        $candidateUrl = self::str($identity, 'candidate_url');

        $methods = [];
        $methodRefs = [];
        foreach (self::arr($access, 'documented_modalities') as $modality) {
            $method = is_array($modality) ? CapabilityAccessMethod::tryFrom((string) ($modality['modality'] ?? '')) : null;
            if ($method === null) {
                continue;
            }
            $methods[$method->value] = $method;
            $url = self::str($modality, 'evidence_url');
            if ($url !== null) {
                $methodRefs[$method->value][] = $url;
            }
        }
        $families = [];
        foreach ($methods as $value => $method) {
            if (isset(self::MODALITY_FAMILIES[$value])) {
                $families[self::MODALITY_FAMILIES[$value]->value] = self::MODALITY_FAMILIES[$value];
            }
        }
        $families = array_values($families);
        $implemented = array_values(array_filter($families, fn (ScientificSourceAccessFamily $f): bool => isset($this->familyImplementations[$f->value])));
        $remoteSearch = in_array(ScientificSourceAccessFamily::REST_API, $families, true);
        $localIndex = array_values(array_filter($families, static fn (ScientificSourceAccessFamily $f): bool => in_array($f, SeatFamilyState::LOCAL_INDEX_FAMILIES, true)));
        $localIndexRequired = SeatFamilyState::of($families) === SeatFamilyState::LOCAL_INDEX;
        $has = static fn (string $m): bool => isset($methods[$m]);
        $refs = static fn (string ...$ms): array => array_values(array_unique(array_merge(...array_map(static fn (string $m): array => $methodRefs[$m] ?? [], $ms))));

        $licensing = ScientificSourceLicensingDeclaration::unresolved();
        $licence = self::arr($access, 'license_and_reuse');
        $licenceUrl = self::str($licence, 'evidence_url');
        $licenceText = self::str($licence, 'text');
        $identifiers = self::arr($access, 'stable_record_identifiers');
        $identifierValue = self::str($identifiers, 'value');
        $identifierUrl = self::str($identifiers, 'evidence_url');
        $identifiersEvidenced = $identifierValue !== null && strtoupper($identifierValue) !== 'UNKNOWN' && $identifierUrl !== null;
        $platform = self::arr($access, 'platform');
        $anchorRefs = [];
        foreach (self::arr($identity, 'anchors') as $anchor) {
            $url = is_array($anchor) ? self::str($anchor, 'evidence_url') : null;
            if ($url !== null) {
                $anchorRefs[] = $url;
            }
        }
        $identityRefs = array_values(array_unique(array_merge(
            ($identity['candidate_url_fetched'] ?? false) === true && $candidateUrl !== null ? [$candidateUrl] : [],
            $anchorRefs,
        )));
        $endpointStatus = self::str(self::arr($record, 'endpoint_test'), 'status') ?? 'NOT RUN';
        $integrationStatus = self::str(self::arr($record, 'integration_test'), 'status') ?? 'NOT RUN';
        $classification = self::str(self::arr($record, 'proposed_ic'), 'classification_status') ?? 'UNCLASSIFIED';
        $notReviewed = static fn (string $field): bool => strtoupper((string) (self::str($access, $field) ?? 'NOT REVIEWED')) === 'NOT REVIEWED';

        $usage = static function (ScientificSourceUsage ...$usages) use ($licensing): SeatOnboardingDimension {
            $states = array_map(static fn (ScientificSourceUsage $u): string => $u->value.'='.$licensing->statusOf($u)->value, $usages);
            $permitted = array_filter($usages, static fn (ScientificSourceUsage $u): bool => $licensing->permits($u)) === $usages;

            $permitted = $permitted && $licensing->evidenceReference !== null;

            return new SeatOnboardingDimension(
                $permitted ? OnboardingEvidenceStatus::VERIFIED : OnboardingEvidenceStatus::UNVERIFIED,
                implode('; ', $states),
                $permitted ? [$licensing->evidenceReference] : [],
                $permitted ? null : 'Not permitted: no accepted licensing declaration (unknown is not permission).',
            );
        };
        $unverified = static fn (?string $value, ?string $note = null): SeatOnboardingDimension => new SeatOnboardingDimension(OnboardingEvidenceStatus::UNVERIFIED, $value, [], $note);
        $recordIdentity = $identifiersEvidenced
            ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, $identifierValue, [$identifierUrl])
            : $unverified(null, 'No stable record identifier documented.');

        $dimensions = [
            'identity' => new SeatOnboardingDimension(
                match ($identityState) {
                    'CANDIDATE_EVIDENCED' => OnboardingEvidenceStatus::PARTIALLY_EVIDENCED,
                    'AMBIGUOUS' => OnboardingEvidenceStatus::BLOCKED,
                    default => OnboardingEvidenceStatus::UNVERIFIED,
                },
                self::str($identity, 'candidate_resource'),
                $identityRefs,
                "Evidence state {$identityState}; identity binding NONE; canonical_identity_id null (owner verification pending).",
            ),
            'membership' => new SeatOnboardingDimension(
                (self::str($seat, 'membership_status') ?? '') === 'CURRENT' ? OnboardingEvidenceStatus::VERIFIED : OnboardingEvidenceStatus::BLOCKED,
                (string) ($seat['membership_status'] ?? 'UNKNOWN'),
                ['docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json'],
                'Membership authority is the register (D-09); membership is not activation.',
            ),
            'family' => new SeatOnboardingDimension(
                $families === [] ? OnboardingEvidenceStatus::UNVERIFIED : OnboardingEvidenceStatus::PARTIALLY_EVIDENCED,
                $families === [] ? null : implode(',', array_map(static fn (ScientificSourceAccessFamily $f): string => $f->value, $families)),
                $refs('api', 'oai_pmh', 'rss', 'atom', 'bulk_download', 'static_download'),
                $families === [] ? 'No machine-readable access family is documented.' : 'Candidate family from documented modalities; no endpoint was tested.',
            ),
            'adapter_compatibility' => match (true) {
                $families === [] => $unverified(null, 'No family to match an adapter against.'),
                $implemented === [] => new SeatOnboardingDimension(OnboardingEvidenceStatus::UNAVAILABLE, null, [], 'The documented family has no implementation in this repository.'),
                default => $unverified(
                    implode(',', array_map(fn (ScientificSourceAccessFamily $f): string => class_basename($this->familyImplementations[$f->value]), $implemented)),
                    'A family implementation exists; compatibility needs the source schema and an endpoint test.',
                ),
            },
            'search' => $remoteSearch
                ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, 'remote_api_documented', $refs('api'), 'Cap search state UNVERIFIED.')
                : $unverified($localIndex !== [] ? 'local_index_required' : ($has('web_ui_search') ? 'web_ui_only' : null), 'Cap search state UNVERIFIED.'),
            'pagination' => $unverified($notReviewed('pagination') ? 'NOT REVIEWED' : self::str($access, 'pagination')),
            'filtering' => $unverified('NOT REVIEWED', 'Filtering was not part of the documentary review.'),
            'full_text' => $has('full_text_access')
                ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, 'full_text_access_documented', $refs('full_text_access'), 'Full-text storage remains unresolved by licence.')
                : $unverified(null),
            'bulk_harvest' => $localIndex !== []
                ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, implode(',', array_map(static fn (ScientificSourceAccessFamily $f): string => $f->value, $localIndex)), $refs('oai_pmh', 'bulk_download', 'static_download'))
                : $unverified(null),
            'query_mapping' => $unverified($notReviewed('query_construction') ? 'NOT REVIEWED' : self::str($access, 'query_construction'), 'No query profile exists for this seat.'),
            'authentication' => in_array($authRequired, ['YES', 'NO'], true)
                ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, $authRequired, array_values(array_filter([self::str($auth, 'evidence_url')])))
                : $unverified('UNKNOWN'),
            'credential_settings' => match ($authRequired) {
                'NO' => new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, 'none_required', array_values(array_filter([self::str($auth, 'evidence_url')]))),
                'YES' => new SeatOnboardingDimension(OnboardingEvidenceStatus::BLOCKED, null, [], 'Credentials are required and no credential setting name is defined.'),
                default => $unverified(null),
            },
            'access_method' => new SeatOnboardingDimension(
                $accessState === 'NOT_DOCUMENTED' ? OnboardingEvidenceStatus::UNVERIFIED : OnboardingEvidenceStatus::PARTIALLY_EVIDENCED,
                $accessState,
                $refs(...array_keys($methods)),
                $platform !== [] ? 'Platform: '.(self::str($platform, 'value') ?? 'UNKNOWN') : null,
            ),
            'licence' => $licenceText !== null && $licenceUrl !== null
                ? new SeatOnboardingDimension(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, $licenceText, [$licenceUrl], 'Documented, not accepted: the licensing declaration stays UNRESOLVED.')
                : $unverified(null, 'No licence or reuse terms documented.'),
            'retention' => $usage(ScientificSourceUsage::METADATA_STORAGE),
            'indexing' => $usage(ScientificSourceUsage::METADATA_STORAGE, ScientificSourceUsage::FULL_TEXT_STORAGE),
            'excerpt_display' => $usage(ScientificSourceUsage::EXCERPT_DISPLAY),
            'user_facing_use' => $usage(ScientificSourceUsage::RETRIEVAL, ScientificSourceUsage::CITATION_LINKING),
            'provenance' => $unverified(
                $notReviewed('provenance_fields') ? 'NOT REVIEWED' : self::str($access, 'provenance_fields'),
                'Platform provenance (source, identifier basis, retrieval time, transformations) is recorded by ScientificSourceFamilyRecord.',
            ),
            'record_identity' => $recordIdentity,
            'dedupe' => new SeatOnboardingDimension(
                $recordIdentity->status,
                'doi > provider identifier > url (ScientificSourceFamilyRecord); store identity sourceKey|original identifier',
                $recordIdentity->evidenceRefs,
                $identifiersEvidenced ? null : 'Without a stable identifier only URL-level dedupe is possible.',
            ),
            'ingestion' => match (true) {
                $localIndexRequired => new SeatOnboardingDimension(OnboardingEvidenceStatus::BLOCKED, 'local_index_ingestion', $refs('oai_pmh', 'bulk_download', 'static_download'), 'Durable record store implemented and tested on disposable PostgreSQL only; its migration is not deployed (§8.41).'),
                $remoteSearch => new SeatOnboardingDimension(
                    OnboardingEvidenceStatus::PARTIALLY_EVIDENCED,
                    $localIndex === [] ? 'not_required_remote_search' : 'not_required_remote_search; local_index_optional',
                    $refs('api'),
                    $localIndex === [] ? null : 'A harvest/bulk family is also documented; its local index would need the undeployed durable store (§8.41).',
                ),
                default => $unverified(null),
            },
            'freshness' => $unverified(match (true) {
                in_array(ScientificSourceAccessFamily::OAI_PMH, $families, true) => 'incremental_by_datestamp',
                $localIndex !== [] => 'snapshot_replacement',
                $remoteSearch => 'per_query',
                default => null,
            }, 'Update cadence not reviewed.'),
            'deletion_withdrawal' => $unverified(match (true) {
                in_array(ScientificSourceAccessFamily::OAI_PMH, $families, true) => 'oai_deleted_header_tombstone',
                $localIndex !== [] => 'absent_from_complete_snapshot_withdrawn',
                $remoteSearch => 'not_observable',
                default => null,
            }, 'The source deletion policy is not documented.'),
            'implementation' => new SeatOnboardingDimension(OnboardingEvidenceStatus::UNAVAILABLE, 'no_runtime_registration', [], 'No registration may carry an adr_id until an Onboarding decision is accepted (D-10.11).'),
            'test_coverage' => $unverified("endpoint_test={$endpointStatus}; integration_test={$integrationStatus}", 'Offline contract tests cover the family implementations, not this source.'),
        ];

        $blockers = [OnboardingBlocker::IDENTITY_NOT_VERIFIED];
        if ($identityState === 'AMBIGUOUS') {
            $blockers[] = OnboardingBlocker::IDENTITY_AMBIGUOUS;
        } elseif ($identityState !== 'CANDIDATE_EVIDENCED') {
            $blockers[] = OnboardingBlocker::IDENTITY_NOT_ESTABLISHED;
        }
        if ($classification === 'UNCLASSIFIED') {
            $blockers[] = OnboardingBlocker::INTEGRATION_UNCLASSIFIED;
        }
        if ($families === []) {
            $blockers[] = OnboardingBlocker::NO_MACHINE_ACCESS_DOCUMENTED;
        } elseif ($implemented === []) {
            $blockers[] = OnboardingBlocker::NO_FAMILY_IMPLEMENTATION;
        }
        if ($endpointStatus !== 'PASSED') {
            $blockers[] = OnboardingBlocker::ENDPOINT_NOT_TESTED;
        }
        if (! $licensing->permits(ScientificSourceUsage::RETRIEVAL)) {
            $blockers[] = OnboardingBlocker::LICENCE_UNRESOLVED;
        }
        if ($authRequired === 'YES') {
            $blockers[] = OnboardingBlocker::CREDENTIALS_REQUIRED;
        }
        if ($localIndexRequired) {
            $blockers[] = OnboardingBlocker::LOCAL_INDEX_STORE_NOT_DEPLOYED;
        }
        if ($fetchFailures !== []) {
            $blockers[] = OnboardingBlocker::EVIDENCE_FETCH_FAILURES;
        }
        $blockers[] = OnboardingBlocker::CAPABILITY_UNVERIFIED;
        $blockers[] = OnboardingBlocker::ONBOARDING_DECISION_NOT_ACCEPTED;

        $candidateUnreachable = $candidateUrl !== null && array_filter($fetchFailures, static fn (string $f): bool => str_starts_with($f, $candidateUrl.':')) !== [];
        $status = match (true) {
            $identityState === 'AMBIGUOUS' => OnboardingEvidenceStatus::BLOCKED,
            $candidateUnreachable => OnboardingEvidenceStatus::UNAVAILABLE,
            $identityState !== 'CANDIDATE_EVIDENCED' || $accessState === 'NOT_DOCUMENTED' => OnboardingEvidenceStatus::UNVERIFIED,
            default => OnboardingEvidenceStatus::PARTIALLY_EVIDENCED,
        };
        $activation = $identityState === 'AMBIGUOUS' ? SeatActivationState::DEFERRED : SeatActivationState::NOT_READY;
        $capabilityStates = [];
        foreach (ScientificSourceOperation::cases() as $operation) {
            $capabilityStates[$operation->value] = CapabilityState::UNVERIFIED;
        }

        $contract = new ScientificSourceOnboardingContract(
            onboardingDecisionId: 'onb-r7-'.$at->format('Ymd').'-'.$adrId,
            adrId: AdrMembershipId::fromString($adrId),
            canonicalIdentityId: null,
            identityEvidenceRefs: $identityRefs,
            integrationClassificationRef: null,
            capabilityDecisionIdentity: null,
            capabilityDimensionStates: $capabilityStates,
            capabilityEvidenceRefs: [],
            accessMethods: array_values($methods),
            licensing: $licensing,
            accessState: $accessState,
            pathId: null,
            pathDecisionIdentity: null,
            pathEligibilityState: null,
            pathStatus: null,
            projectionIdentity: null,
            fidelityClass: null,
            facetAccounting: null,
            stage3Relationship: null,
            selectorEligibility: ScientificSourceOnboardingContract::SELECTOR_NOT_SELECTABLE,
            activationState: $activation,
            groupWaveLabel: null,
            decisionActor: self::DECISION_ACTOR,
            decisionTimestamp: $at,
            rationale: 'Reconciled from documentary evidence only; blockers: '.implode(', ', array_map(static fn (OnboardingBlocker $b): string => $b->value, $blockers)).'.',
            stalenessPolicyRef: self::STALENESS_POLICY_REF,
            correlationHooks: ['evidence:'.(self::str(self::arr($record, 'evidence_basis'), 'evidence_adr_id') ?? $adrId)],
        );

        return new SeatReconciliationEntry(
            adrId: $adrId,
            groupId: (string) $seat['group_id'],
            ordinal: (int) $seat['ordinal'],
            displayName: (string) $seat['display_name'],
            dualPeerAdrId: self::str($seat, 'dual_peer_adr_id'),
            evidenceBasis: self::str(self::arr($record, 'evidence_basis'), 'kind') ?? 'UNKNOWN',
            evidenceAdrId: self::str(self::arr($record, 'evidence_basis'), 'evidence_adr_id') ?? $adrId,
            dimensions: $dimensions,
            candidateFamilies: $families,
            blockers: $blockers,
            status: $status,
            contract: $contract,
            profileNameCorrespondences: $correspondences,
            nextAction: self::str($record, 'next_action'),
        );
    }

    /**
     * @param  array<string, mixed>  $register
     * @param  list<OnboardingIntegrityFinding>  $findings
     * @return array<string, array<string, mixed>>
     */
    private function seats(array $register, array &$findings): array
    {
        $seats = [];
        foreach (self::arr($register, 'seats') as $seat) {
            $adrId = is_array($seat) ? self::str($seat, 'adr_id') : null;
            if ($adrId === null || self::str($seat, 'group_id') === null || self::str($seat, 'display_name') === null || ! is_int($seat['ordinal'] ?? null)) {
                $findings[] = new OnboardingIntegrityFinding('malformed_register_seat', OnboardingIntegrityFinding::ERROR, 'register', 'A register seat lacks adr_id, group_id, ordinal or display_name.');

                continue;
            }
            if (isset($seats[$adrId])) {
                $findings[] = new OnboardingIntegrityFinding('duplicate_register_seat', OnboardingIntegrityFinding::ERROR, $adrId, 'The adr_id appears more than once in the register.');

                continue;
            }
            $seats[$adrId] = $seat;
        }

        return $seats;
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @param  list<OnboardingIntegrityFinding>  $findings
     * @return array<string, array<string, mixed>>
     */
    private function evidenceRecords(array $evidence, array &$findings): array
    {
        $records = [];
        foreach (self::arr($evidence, 'records') as $record) {
            $adrId = is_array($record) ? self::str($record, 'adr_id') : null;
            if ($adrId === null) {
                $findings[] = new OnboardingIntegrityFinding('malformed_evidence_record', OnboardingIntegrityFinding::ERROR, 'evidence', 'An evidence record has no adr_id.');

                continue;
            }
            if (isset($records[$adrId])) {
                $findings[] = new OnboardingIntegrityFinding('duplicate_evidence_record', OnboardingIntegrityFinding::ERROR, $adrId, 'The seat has more than one evidence record.');

                continue;
            }
            $records[$adrId] = $record;
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $register
     * @param  array<string, mixed>  $evidence
     * @param  array<string, array<string, mixed>>  $seats
     * @param  array<string, array<string, mixed>>  $records
     * @param  list<OnboardingIntegrityFinding>  $findings
     */
    private function checkInputs(array $register, string $registerSha256, array $evidence, array $seats, array $records, array &$findings): void
    {
        $declared = self::arr($evidence, 'membership_register');
        if (strtolower((string) (self::str($declared, 'sha256') ?? '')) !== strtolower($registerSha256)) {
            $findings[] = new OnboardingIntegrityFinding('register_hash_mismatch', OnboardingIntegrityFinding::ERROR, 'evidence', 'The evidence review was produced against a different membership register.');
        }
        if (self::str($declared, 'membership_revision') !== self::str($register, 'membership_revision')) {
            $findings[] = new OnboardingIntegrityFinding('membership_revision_mismatch', OnboardingIntegrityFinding::ERROR, 'evidence', 'The evidence review names a different membership revision.');
        }
        if (($register['total_current_seats'] ?? null) !== count($seats)) {
            $findings[] = new OnboardingIntegrityFinding('register_seat_count_mismatch', OnboardingIntegrityFinding::ERROR, 'register', 'total_current_seats does not equal the number of seats.');
        }
        $groups = [];
        foreach ($seats as $seat) {
            $groups[(string) $seat['group_id']] = ($groups[(string) $seat['group_id']] ?? 0) + 1;
        }
        $declaredGroups = self::arr($register, 'group_counts');
        ksort($groups);
        ksort($declaredGroups);
        if ($groups !== $declaredGroups) {
            $findings[] = new OnboardingIntegrityFinding('register_group_count_mismatch', OnboardingIntegrityFinding::ERROR, 'register', 'group_counts do not match the seats.');
        }
        foreach (array_diff_key($records, $seats) as $adrId => $record) {
            $findings[] = new OnboardingIntegrityFinding('orphan_evidence_record', OnboardingIntegrityFinding::ERROR, (string) $adrId, 'An evidence record names a seat absent from the register.');
        }
        foreach (self::arr($register, 'dual_pairs') as $pair) {
            $a = is_array($pair) ? self::str($pair, 'seat_a') : null;
            $b = is_array($pair) ? self::str($pair, 'seat_b') : null;
            if ($a === null || $b === null || ! isset($seats[$a], $seats[$b])
                || self::str($seats[$a], 'dual_peer_adr_id') !== $b || self::str($seats[$b], 'dual_peer_adr_id') !== $a) {
                $findings[] = new OnboardingIntegrityFinding('dual_pair_inconsistent', OnboardingIntegrityFinding::ERROR, ($a ?? '?').'/'.($b ?? '?'), 'A dual pair is not mirrored by both seats.');
            }
        }
        $summary = self::arr($evidence, 'summary');
        if (($summary['records'] ?? null) !== count($records)) {
            $findings[] = new OnboardingIntegrityFinding('evidence_summary_mismatch', OnboardingIntegrityFinding::ERROR, 'evidence', 'The evidence summary record count differs from its records.');
        }
        foreach (['identity_verified', 'endpoint_tested', 'integration_verified'] as $claim) {
            if (($summary[$claim] ?? 0) !== 0) {
                $findings[] = new OnboardingIntegrityFinding('unsupported_verification_claim', OnboardingIntegrityFinding::WARNING, 'evidence', "The evidence summary claims {$claim}; documentary review cannot verify it.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $seat
     * @param  array<string, mixed>  $record
     * @param  list<OnboardingIntegrityFinding>  $findings
     */
    private function checkSeatRecord(string $adrId, array $seat, array $record, array &$findings): void
    {
        foreach (['display_name', 'group_id', 'dual_peer_adr_id'] as $field) {
            if (self::str($seat, $field) !== self::str($record, $field)) {
                $findings[] = new OnboardingIntegrityFinding('evidence_seat_mismatch', OnboardingIntegrityFinding::ERROR, $adrId, "Evidence {$field} differs from the register.");
            }
        }
        if (($seat['ordinal'] ?? null) !== ($record['ordinal'] ?? null)) {
            $findings[] = new OnboardingIntegrityFinding('evidence_seat_mismatch', OnboardingIntegrityFinding::ERROR, $adrId, 'Evidence ordinal differs from the register.');
        }
        if ((self::str($record, 'same_as') ?? 'NONE') !== 'NONE') {
            $findings[] = new OnboardingIntegrityFinding('same_as_asserted', OnboardingIntegrityFinding::ERROR, $adrId, 'Evidence asserts SAME_AS; dual membership is not SAME_AS and no SAME_AS was decided.');
        }
        $basis = self::arr($record, 'evidence_basis');
        $kind = self::str($basis, 'kind');
        $evidenceAdr = self::str($basis, 'evidence_adr_id');
        if ($kind === 'DIRECT_REVIEW' && $evidenceAdr !== $adrId) {
            $findings[] = new OnboardingIntegrityFinding('evidence_basis_inconsistent', OnboardingIntegrityFinding::ERROR, $adrId, 'A direct review must cite its own seat.');
        } elseif ($kind === 'DUAL_PEER_EVIDENCE_REFERENCE' && $evidenceAdr !== self::str($seat, 'dual_peer_adr_id')) {
            $findings[] = new OnboardingIntegrityFinding('evidence_basis_inconsistent', OnboardingIntegrityFinding::ERROR, $adrId, 'A dual-peer evidence reference must cite the registered dual peer.');
        } elseif (! in_array($kind, ['DIRECT_REVIEW', 'DUAL_PEER_EVIDENCE_REFERENCE'], true)) {
            $findings[] = new OnboardingIntegrityFinding('evidence_basis_inconsistent', OnboardingIntegrityFinding::ERROR, $adrId, 'Unknown evidence basis.');
        }
        if ((self::arr($record, 'identity')['identity_verified'] ?? false) !== false
            || (self::str(self::arr($record, 'endpoint_test'), 'status') ?? 'NOT RUN') !== 'NOT RUN'
            || (self::str(self::arr($record, 'integration_test'), 'status') ?? 'NOT RUN') !== 'NOT RUN') {
            $findings[] = new OnboardingIntegrityFinding('unsupported_verification_claim', OnboardingIntegrityFinding::WARNING, $adrId, 'The record claims a verification that no owning authority recorded; it is not accepted.');
        }
        $runtime = self::arr($record, 'runtime');
        if ((self::str($runtime, 'runtime_source_registry') ?? 'ABSENT') !== 'ABSENT' || (self::str($runtime, 'binding') ?? 'NONE') !== 'NONE') {
            $findings[] = new OnboardingIntegrityFinding('evidence_runtime_claim', OnboardingIntegrityFinding::ERROR, $adrId, 'Evidence claims a runtime registration or binding that the catalog does not hold.');
        }
    }

    /** @param  list<OnboardingIntegrityFinding>  $findings */
    private function checkFamilyImplementations(array &$findings): void
    {
        foreach ($this->familyImplementations as $family => $class) {
            if (ScientificSourceAccessFamily::tryFrom($family) === null || ! class_exists($class)) {
                $findings[] = new OnboardingIntegrityFinding('dead_family_implementation', OnboardingIntegrityFinding::ERROR, $family, "Family implementation '{$class}' does not exist.");
            }
        }
        foreach (ScientificSourceAccessFamily::cases() as $family) {
            if (! isset($this->familyImplementations[$family->value])) {
                $findings[] = new OnboardingIntegrityFinding('family_without_implementation', OnboardingIntegrityFinding::INFO, $family->value, 'No source-family implementation exists for this access family.');
            }
        }
    }

    /**
     * @param  array<string, ScientificSourceProfile>  $profiles
     * @param  array<string, array<string, mixed>>  $seats
     * @param  list<OnboardingIntegrityFinding>  $findings
     * @return array<string, list<string>> adr_id => profile keys whose name matches the seat
     */
    private function checkRuntimeAndProfiles(ScientificSourceCatalog $catalog, array $profiles, array $seats, array &$findings): array
    {
        foreach ($catalog->all() as $key => $registration) {
            $declared = [];
            foreach ($registration->capabilities as $capability) {
                $declared[] = $capability->operation->value.'='.$capability->status->value;
                if ($capability->status === ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS) {
                    $findings[] = new OnboardingIntegrityFinding('legacy_operational_basis', OnboardingIntegrityFinding::INFO, $key, "'{$capability->operation->value}' rests on the legacy operational basis, not on verification.");
                }
            }
            if ($registration->adrId !== null) {
                $contract = AcceptedOnboardingDecisionCatalog::forAdrId($registration->adrId->value);
                if ($contract === null
                    || $contract->activationState !== SeatActivationState::ACTIVE
                    || $contract->stage3Relationship !== $key) {
                    $findings[] = new OnboardingIntegrityFinding('registration_bound_without_onboarding', OnboardingIntegrityFinding::ERROR, $key, 'A registration carries an adr_id without an accepted onboarding decision.');
                } else {
                    $findings[] = new OnboardingIntegrityFinding('registration_governed_bound', OnboardingIntegrityFinding::INFO, $key, "Governed ADR seat '{$registration->adrId->value}' bound via accepted onboarding '{$contract->onboardingDecisionId}'; capabilities: ".($declared === [] ? 'none' : implode(', ', $declared)).'.');
                }
            } else {
                $findings[] = new OnboardingIntegrityFinding('registration_external_only', OnboardingIntegrityFinding::INFO, $key, 'Stage-3 registration with no seat binding (EXTERNAL identity only); declared capabilities: '.($declared === [] ? 'none' : implode(', ', $declared)).'.');
            }
            $profile = $profiles[$key] ?? null;
            if ($profile === null) {
                $findings[] = new OnboardingIntegrityFinding('registration_without_query_profile', OnboardingIntegrityFinding::WARNING, $key, 'A registered source has no query profile, so CSQ cannot compile queries for it.');
            } elseif (! $profile->active) {
                $findings[] = new OnboardingIntegrityFinding('registered_profile_inactive', OnboardingIntegrityFinding::INFO, $key, 'The source is registered but its query profile is inactive.');
            }
            if ($registration->familyProfile !== null && $registration->familyProfile->licensing()->isFullyUnresolved()) {
                $findings[] = new OnboardingIntegrityFinding('registered_source_licence_unresolved', OnboardingIntegrityFinding::WARNING, $key, 'The family profile declares no resolved licensing.');
            }
        }

        $correspondences = [];
        foreach ($profiles as $key => $profile) {
            $registered = $catalog->has($key);
            if ($profile->active && ! $registered) {
                $findings[] = new OnboardingIntegrityFinding('active_profile_unregistered', OnboardingIntegrityFinding::ERROR, $key, 'An active query profile has no runtime registration.');
            }
            if (! $registered) {
                $findings[] = new OnboardingIntegrityFinding('unbound_query_profile_stub', OnboardingIntegrityFinding::INFO, $key, 'Inactive query profile with no registration and no seat binding.');
                if ($profile->identifierFields !== [] || $profile->supportedConstraintFields !== []) {
                    $findings[] = new OnboardingIntegrityFinding('stub_declares_unverified_query_capabilities', OnboardingIntegrityFinding::WARNING, $key, 'The stub declares identifier fields ('.implode(',', $profile->identifierFields).') and constraints ('.implode(',', $profile->supportedConstraintFields).') that no evidence or endpoint test supports.');
                }
            }
            foreach ($seats as $adrId => $seat) {
                if (self::nameCorresponds($key, (string) $seat['display_name'])) {
                    $correspondences[$adrId][] = $key;
                    $findings[] = new OnboardingIntegrityFinding('profile_seat_name_correspondence', OnboardingIntegrityFinding::INFO, $key.'/'.$adrId, "Profile '{$key}' matches the name of seat {$adrId} ('{$seat['display_name']}'); a name match is not SAME_AS or a binding.");
                }
            }
        }

        return $correspondences;
    }

    private static function nameCorresponds(string $sourceKey, string $displayName): bool
    {
        $tokens = preg_split('/[^a-z0-9]+/', strtolower($displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keyTokens = explode('_', $sourceKey);

        return in_array(str_replace('_', '', $sourceKey), $tokens, true)
            || array_diff($keyTokens, $tokens) === [];
    }

    /**
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    private static function arr(array $array, string $key): array
    {
        return is_array($array[$key] ?? null) ? $array[$key] : [];
    }

    /** @param  array<mixed>  $array */
    private static function str(array $array, string $key): ?string
    {
        $value = $array[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
