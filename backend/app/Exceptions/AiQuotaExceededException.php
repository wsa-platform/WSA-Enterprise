<?php

namespace App\Exceptions;

use Exception;

class AiQuotaExceededException extends Exception
{
    public function __construct(
        public readonly int $organizationId,
        public readonly int $limit,
        public readonly int $used,
    ) {
        parent::__construct('AI request quota exceeded for this organization.');
    }
}
