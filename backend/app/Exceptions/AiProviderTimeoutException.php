<?php

namespace App\Exceptions;

use RuntimeException;

class AiProviderTimeoutException extends RuntimeException
{
    public function __construct(public readonly int $timeoutSeconds)
    {
        parent::__construct('The AI provider timed out.');
    }
}
