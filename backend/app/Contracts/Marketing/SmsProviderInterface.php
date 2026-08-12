<?php

namespace App\Contracts\Marketing;

interface SmsProviderInterface
{
    /** @param  array<string, mixed>  $payload */
    public function send(string $to, string $message, array $payload = []): MarketingSendResult;
}
