<?php

namespace App\Contracts\Marketing;

class MarketingSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
