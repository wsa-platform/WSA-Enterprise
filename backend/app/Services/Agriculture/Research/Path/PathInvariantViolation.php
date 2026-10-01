<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Thrown when an ADR-023 Path Model domain invariant is violated.
 *
 * IU-03 only — does not encode Cap mutation, Projection, Identity minting, or CSQ.
 */
final class PathInvariantViolation extends \InvalidArgumentException
{
}
