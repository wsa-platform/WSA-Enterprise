<?php

namespace App\Exceptions;

use Exception;

class PlanRestrictionException extends Exception
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $featureKey,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($reason ?? "Feature '{$featureKey}' is not available on the current plan.");
    }
}
