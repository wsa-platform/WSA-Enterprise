<?php

namespace App\Contracts\Providers;

interface ConnectableProviderInterface
{
    public function isConfigured(): bool;

    /** @return array{connected: bool, label: string, provider: string} */
    public function connectionStatus(): array;

    /** @return array{success: bool, message?: string, error?: string} */
    public function testConnection(): array;
}
