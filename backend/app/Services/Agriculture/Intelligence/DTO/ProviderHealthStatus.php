<?php

namespace App\Services\Agriculture\Intelligence\DTO;

final class ProviderHealthStatus
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $providerId,
        public readonly string $state,
        public readonly ?string $message = null,
        public readonly array $details = [],
        public readonly ?string $checkedAt = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider_id' => $this->providerId,
            'state' => $this->state,
            'message' => $this->message,
            'details' => $this->details,
            'checked_at' => $this->checkedAt ?? now()->toIso8601String(),
        ];
    }
}
