<?php

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;

/**
 * Read-only authoritative CapVer seat specs for CapVer-authorized runtime seats.
 *
 * Separates Cap decision identities (`cap-r*`) from CapVer store fact IDs (`cap-rec*`).
 * Does not invent identifiers; values match CapVerSeat / historical CapVer recovery authority.
 */
final class CapVerAuthorizedSeatSpecCatalog
{
    /**
     * @return array{
     *     access_method: CapabilityAccessMethod,
     *     capability_decision_identity: string,
     *     capability_record_id: string,
     *     evidence_refs: list<string>,
     *     snapshot_version: string,
     *     observed_at: string
     * }|null
     */
    public static function forAdrId(string $adrId): ?array
    {
        return match ($adrId) {
            'G1-02' => [
                'access_method' => CapabilityAccessMethod::OAI_PMH,
                'capability_decision_identity' => 'cap-r11-20261018-G1-02-search',
                'capability_record_id' => 'cap-rec-r11-20261018-G1-02-oai_pmh',
                'evidence_refs' => [
                    CurrentMembershipNearReadySeatCatalog::ENDPOINT_EVIDENCE_ORGANIC_EPRINTS,
                    'IC-ER 2026-10-13 G1-02',
                    'LocalIndex GEN=2 ACTIVE_ROWS=189 cross-process probe 2026-10-18',
                    'OD-G1-02-ONBOARDING-ACTIVE ACCEPT + OD-CAPVER-ACCEPTED-SEATS AUTHORIZE',
                ],
                'snapshot_version' => 'organic_eprints.oai_pmh.search.v1',
                'observed_at' => '2026-10-18T12:00:00+00:00',
            ],
            'G6-20' => [
                'access_method' => CapabilityAccessMethod::API,
                'capability_decision_identity' => 'cap-r11-20261018-G6-20-search',
                'capability_record_id' => 'cap-rec-r11-20261018-G6-20-api',
                'evidence_refs' => [
                    CurrentMembershipNearReadySeatCatalog::ENDPOINT_EVIDENCE_BHL,
                    'IC-ER 2026-10-13 G6-20',
                    'Live PublicationSearch Status=ok 2026-10-18 (key via config; value never archived)',
                    'OD-G6-20-ONBOARDING-ACTIVE ACCEPT + OD-CAPVER-ACCEPTED-SEATS AUTHORIZE',
                ],
                'snapshot_version' => 'bhl.rest_api.publication_search.v1',
                'observed_at' => '2026-10-18T12:00:00+00:00',
            ],
            'G1-01' => [
                'access_method' => CapabilityAccessMethod::BULK_DOWNLOAD,
                'capability_decision_identity' => 'cap-r12-20261018-G1-01-search',
                'capability_record_id' => 'cap-rec-r12-20261018-G1-01-bulk',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_AGRIS,
                    'IC-ER 2026-10-13 G1-01',
                    'LocalIndex GEN=4 ACTIVE_ROWS=1317 bounded ODS subset 2026-10-18',
                    'OD-G1-01-SCOPE-BOUNDED-SUBSET + OD-G1-01-ONBOARDING-ACTIVE + OD-CAPVER-G1-01-G1-17 AUTHORIZE',
                ],
                'snapshot_version' => 'agris.bulk_dataset.bounded_ods.search.v1',
                'observed_at' => '2026-10-18T12:37:57+00:00',
            ],
            'G1-17' => [
                'access_method' => CapabilityAccessMethod::STATIC_DOWNLOAD,
                'capability_decision_identity' => 'cap-r12-20261018-G1-17-search',
                'capability_record_id' => 'cap-rec-r12-20261018-G1-17-static',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_DR_DUKE,
                    'IC-ER 2026-10-13 G1-17',
                    'LocalIndex GEN=2 ACTIVE_ROWS=1500 limited projection 2026-10-18',
                    'FARMACY≠FARMACY_NEW; ETHNOBOT traditional-use only',
                    'OD-G1-17-SCOPE-LIMITED-PROJECTION + OD-G1-17-ONBOARDING-ACTIVE + OD-CAPVER-G1-01-G1-17 AUTHORIZE',
                ],
                'snapshot_version' => 'dr_duke.static_dataset.limited_projection.search.v1',
                'observed_at' => '2026-10-18T11:23:54+00:00',
            ],
            'G5-04' => [
                'access_method' => CapabilityAccessMethod::API,
                'capability_decision_identity' => 'cap-r9-20261016-G5-04-search',
                'capability_record_id' => 'cap-rec-r9-20261016-G5-04-api',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_GBIF,
                    'IC-ER 2026-10-13 G5-04',
                    'onb-r9-20261016-G5-04 ACTIVE species-search contract',
                    'ADR-023 §8.66 scoped CapVer authorization 2026-10-22',
                    'Live GET /v1/species/search q=Triticum HTTP 200 2026-10-22T06:33:36Z usageKey=315051481',
                ],
                'snapshot_version' => 'gbif.rest_api.species_search.v1',
                'observed_at' => '2026-10-22T06:33:36+00:00',
            ],
            default => null,
        };
    }

    public static function capabilityStoreRecordId(string $adrId): ?string
    {
        $spec = self::forAdrId($adrId);

        return is_array($spec) ? (string) $spec['capability_record_id'] : null;
    }
}
