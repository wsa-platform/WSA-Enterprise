<?php

namespace App\Services\Monitoring;

readonly class RemediationResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public bool $allowed,
        public ?array $payload = null,
    ) {}
}
