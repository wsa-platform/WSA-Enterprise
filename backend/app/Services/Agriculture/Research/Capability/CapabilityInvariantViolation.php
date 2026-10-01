<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Thrown when an ADR-023 Capability Store domain invariant is violated.
 *
 * IU-02 only — does not encode Path, Projection, Identity minting, or CSQ semantics.
 */
final class CapabilityInvariantViolation extends \InvalidArgumentException
{
}
