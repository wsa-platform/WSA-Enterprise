<?php

namespace App\Services\Tenancy;

use RuntimeException;

/**
 * Thrown when the configured public organization cannot be resolved (fail closed).
 */
final class PublicTenantResolutionException extends RuntimeException
{
}
