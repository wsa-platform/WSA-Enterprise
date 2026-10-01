<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * Thrown when an ADR-023 B7 Correlation domain invariant is violated.
 *
 * IU-06 only — does not encode Cap/Path/Projection/CSQ mutation or persistence.
 */
final class CorrelationInvariantViolation extends \InvalidArgumentException
{
}
