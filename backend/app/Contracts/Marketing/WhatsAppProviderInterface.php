<?php

namespace App\Contracts\Marketing;

interface WhatsAppProviderInterface
{
    /** @param  array<string, mixed>  $payload */
    public function send(string $to, string $message, array $payload = []): MarketingSendResult;
}
