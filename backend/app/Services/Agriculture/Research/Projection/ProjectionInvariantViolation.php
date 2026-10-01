<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Thrown when an ADR-023 Projection / C9 domain invariant is violated.
 *
 * IU-04 only — does not encode Cap/Path mutation, Stage-3, Composer, or CSQ.
 */
final class ProjectionInvariantViolation extends \InvalidArgumentException
{
}
