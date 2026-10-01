<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * Thrown when an ADR-023 Source Identity domain invariant is violated.
 *
 * IU-01 only — does not encode Capability, Path, Projection, or CSQ semantics.
 */
final class SourceIdentityInvariantViolation extends \InvalidArgumentException
{
}
