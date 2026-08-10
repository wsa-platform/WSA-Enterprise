<?php

namespace App\Exceptions;

use Exception;

class SubscriptionInactiveException extends Exception
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $status,
    ) {
        parent::__construct('Organization subscription is not active.');
    }
}
