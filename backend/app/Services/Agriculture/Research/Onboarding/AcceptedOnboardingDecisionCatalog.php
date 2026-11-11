<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * Accepted Onboarding Contract decisions that authorize ADR seat ↔ sourceKey binding
 * (ADR-023 D-10.11). Only ACTIVE decisions may bind a runtime registration.
 *
 * This is not membership authority and not the runtime ScientificSourceCatalog.
 * Decisions are immutable code artifacts; a change is a new decision id.
 */
final class AcceptedOnboardingDecisionCatalog
{
    /** @var array<string, ScientificSourceOnboardingContract>|null */
    private static ?array $byAdrId = null;

    public static function forAdrId(string $adrId): ?ScientificSourceOnboardingContract
    {
        return self::all()[$adrId] ?? null;
    }

    public static function forSourceKey(string $sourceKey): ?ScientificSourceOnboardingContract
    {
        foreach (self::all() as $contract) {
            if ($contract->stage3Relationship === $sourceKey
                && $contract->activationState === SeatActivationState::ACTIVE) {
                return $contract;
            }
        }

        return null;
    }

    /** @return array<string, ScientificSourceOnboardingContract> */
    public static function all(): array
    {
        return self::$byAdrId ??= array_merge(
            NextGateEligibleSeatCatalog::acceptedActiveContracts(),
            CurrentMembershipNearReadySeatCatalog::acceptedActiveContracts(),
        );
    }

    /** @return list<string> */
    public static function activeSourceKeys(): array
    {
        $keys = [];
        foreach (self::all() as $contract) {
            if ($contract->activationState === SeatActivationState::ACTIVE
                && is_string($contract->stage3Relationship)
                && $contract->stage3Relationship !== '') {
                $keys[] = $contract->stage3Relationship;
            }
        }

        return $keys;
    }

    /** Test seam: replace the catalog. Pass null to restore production decisions. */
    public static function swap(?array $byAdrId): void
    {
        self::$byAdrId = $byAdrId;
    }
}
