<?php

namespace App\Exceptions;

use Exception;

class AiProviderUnavailableException extends Exception
{
    public function __construct(
        public readonly string $requestedProvider,
        public readonly int $status = 422,
        string $message = 'The configured AI provider is not available.',
    ) {
        parent::__construct($message);
    }
}
