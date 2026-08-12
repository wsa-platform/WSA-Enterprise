<?php

namespace App\Contracts\Marketing;

interface EmailProviderInterface
{
    /** @param  array<string, mixed>  $payload */
    public function send(string $to, string $subject, string $body, array $payload = []): MarketingSendResult;
}
