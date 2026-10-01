<?php

namespace App\Services\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureHandoff;

/**
 * Request-scoped bag for IU-08 handoffs from Stage-3 Boundary → Composer.
 *
 * Not an authority — transport only so Agent need not be modified.
 */
final class CoexistenceDisclosureContext
{
    private static ?self $instance = null;

    /** @var list<FidelityDisclosureHandoff> */
    private array $handoffs = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * @param  list<FidelityDisclosureHandoff>  $handoffs
     */
    public function replace(array $handoffs): void
    {
        foreach ($handoffs as $handoff) {
            if (! $handoff instanceof FidelityDisclosureHandoff) {
                throw new CoexistenceInvariantViolation(
                    'CoexistenceDisclosureContext requires FidelityDisclosureHandoff instances.'
                );
            }
        }
        $this->handoffs = array_values($handoffs);
    }

    /**
     * @return list<FidelityDisclosureHandoff>
     */
    public function all(): array
    {
        return $this->handoffs;
    }

    public function clear(): void
    {
        $this->handoffs = [];
    }
}
