<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

/**
 * An idempotency_key already belongs to a record whose IC decision contract differs
 * from the request (or, for supersede, to a record that is not the prior's replacement).
 */
final class IntegrationClassificationIdempotencyConflict extends IntegrationClassificationInvariantViolation
{
}
