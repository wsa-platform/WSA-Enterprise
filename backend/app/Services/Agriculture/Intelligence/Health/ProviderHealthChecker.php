<?php

namespace App\Services\Agriculture\Intelligence\Health;

use App\Contracts\Agriculture\AgriculturalProviderInterface;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;

/**
 * Provider health checks with failure isolation.
 */
final class ProviderHealthChecker
{
    public function __construct(
        private AgriculturalProviderRegistry $registry,
    ) {}

    public function check(string $providerId): ProviderHealthStatus
    {
        $provider = $this->registry->get($providerId);
        if ($provider === null) {
            return new ProviderHealthStatus(
                providerId: $providerId,
                state: ProviderHealthState::UNAVAILABLE,
                message: 'provider_not_registered',
            );
        }

        try {
            return $provider->health();
        } catch (\Throwable $e) {
            return new ProviderHealthStatus(
                providerId: $providerId,
                state: ProviderHealthState::UNAVAILABLE,
                message: 'health_check_failed',
                details: ['error_class' => $e::class],
            );
        }
    }

    /**
     * @return list<ProviderHealthStatus>
     */
    public function checkAll(): array
    {
        $out = [];
        foreach ($this->registry->all() as $provider) {
            $out[] = $this->checkOne($provider);
        }

        return $out;
    }

    private function checkOne(AgriculturalProviderInterface $provider): ProviderHealthStatus
    {
        try {
            return $provider->health();
        } catch (\Throwable $e) {
            return new ProviderHealthStatus(
                providerId: $provider->descriptor()->id,
                state: ProviderHealthState::UNAVAILABLE,
                message: 'health_check_failed',
                details: ['error_class' => $e::class],
            );
        }
    }
}
