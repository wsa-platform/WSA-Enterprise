<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * Append-only catalog of recorded owner decisions that authorize onboarding /
 * CapVer work. Distinct from proposals (kind=DECISION_REQUEST_NOT_AN_APPROVAL).
 *
 * Does not itself activate seats; CapVer and runtime ACTIVE still require their
 * own evidence and contract fields.
 */
final class AcceptedOwnerDecisionCatalog
{
    public const DECISION_RECORD_PATH_G1_02_G6_20 = 'docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-OWNER-DECISION-RECORDED-G1-02-G6-20.v1.json';

    public const DECISION_RECORD_PATH_G1_01_G1_17 = 'docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-OWNER-DECISION-RECORDED-G1-01-G1-17-SCOPE.v1.json';

    /** @deprecated Use DECISION_RECORD_PATH_G1_02_G6_20 */
    public const DECISION_RECORD_PATH = self::DECISION_RECORD_PATH_G1_02_G6_20;

    /** @var array<string, array{id: string, seat: string, outcome: string, source_key?: string}>|null */
    private static ?array $onboardingBySeat = null;

    /** @var list<string>|null */
    private static ?array $capVerAuthorizedSeats = null;

    public static function onboardingAccepted(string $adrId): bool
    {
        $row = self::onboardingBySeat()[$adrId] ?? null;

        return is_array($row) && ($row['outcome'] ?? null) === 'ACCEPT';
    }

    public static function capVerAuthorized(string $adrId): bool
    {
        return in_array($adrId, self::capVerAuthorizedSeats(), true);
    }

    /**
     * @return array<string, array{id: string, seat: string, outcome: string, source_key?: string}>
     */
    public static function onboardingBySeat(): array
    {
        return self::$onboardingBySeat ??= [
            'G1-02' => [
                'id' => 'OD-G1-02-ONBOARDING-ACTIVE',
                'seat' => 'G1-02',
                'source_key' => 'organic_eprints',
                'outcome' => 'ACCEPT',
            ],
            'G6-20' => [
                'id' => 'OD-G6-20-ONBOARDING-ACTIVE',
                'seat' => 'G6-20',
                'source_key' => 'bhl',
                'outcome' => 'ACCEPT',
            ],
            'G1-01' => [
                'id' => 'OD-G1-01-ONBOARDING-ACTIVE',
                'seat' => 'G1-01',
                'source_key' => 'agris',
                'outcome' => 'ACCEPT',
            ],
            'G1-17' => [
                'id' => 'OD-G1-17-ONBOARDING-ACTIVE',
                'seat' => 'G1-17',
                'source_key' => 'dr_duke',
                'outcome' => 'ACCEPT',
            ],
            // Existing NextGate contract onb-r9-20261016-G5-04 is already ACTIVE.
            // Listed here only so the CapVer gate can see that recorded contract.
            // ADR-023 §8.66 authorizes CapVer for this seat alone.
            'G5-04' => [
                'id' => 'onb-r9-20261016-G5-04',
                'seat' => 'G5-04',
                'source_key' => 'gbif',
                'outcome' => 'ACCEPT',
            ],
        ];
    }

    /** @return list<string> */
    public static function capVerAuthorizedSeats(): array
    {
        return self::$capVerAuthorizedSeats ??= ['G1-02', 'G6-20', 'G1-01', 'G1-17', 'G5-04'];
    }

    /** Test seam. */
    public static function swap(?array $onboardingBySeat, ?array $capVerAuthorizedSeats = null): void
    {
        self::$onboardingBySeat = $onboardingBySeat;
        self::$capVerAuthorizedSeats = $capVerAuthorizedSeats;
    }
}
