<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;

/**
 * FAOSTAT readiness. Does not enable the provider. RFN pending does not block QCL.
 */
final class FaoStatReadinessReporter
{
    public function __construct(
        private FaoStatDeveloperPortalClient $client,
        private FaoStatDeveloperPortalTokenManager $tokens,
        private FaoStatCircuitBreaker $circuit,
    ) {}

    /**
     * @return array{
     *     readiness: string,
     *     provider_state: string,
     *     message: string,
     *     details: array<string, mixed>
     * }
     */
    public function report(): array
    {
        $details = [
            'active_domains' => FaoStatActivationPolicy::activeDomains(),
            'qcl_activation' => FaoStatActivationPolicy::activationState(FaoStatDomainCatalog::QCL),
            'rfn_activation' => FaoStatActivationPolicy::activationState('RFN'),
            'rfn_live_validation' => FaoStatLiveValidationRegistry::rfnLiveValidationStatus(),
            'circuit_open' => $this->circuit->isOpen(),
        ];

        if (! FaoStatRuntimePolicy::isEnabled()) {
            return $this->pack(
                FaoStatReadinessState::DISABLED,
                ProviderHealthState::NOT_CONFIGURED,
                'disabled',
                $details,
            );
        }

        if ($this->circuit->isOpen()) {
            return $this->pack(
                FaoStatReadinessState::CIRCUIT_OPEN,
                ProviderHealthState::UNAVAILABLE,
                'circuit_open',
                $details,
            );
        }

        $username = (string) config('agricultural_intelligence.faostat.username', '');
        $password = (string) config('agricultural_intelligence.faostat.password', '');
        $configured = $username !== '' && $password !== '';

        try {
            $ping = $this->client->ping();
        } catch (FaoStatPortalException $e) {
            return $this->pack(
                FaoStatReadinessState::UPSTREAM_UNAVAILABLE,
                ProviderHealthState::UNAVAILABLE,
                'upstream_unreachable',
                $details + ['category' => $e->category, 'http_status' => $e->httpStatus],
            );
        }

        if ($ping->serverError()) {
            return $this->pack(
                FaoStatReadinessState::UPSTREAM_UNAVAILABLE,
                ProviderHealthState::UNAVAILABLE,
                'api_unavailable',
                $details + ['http_status' => $ping->status()],
            );
        }

        if (! $configured) {
            return $this->pack(
                FaoStatReadinessState::NOT_CONFIGURED,
                ProviderHealthState::NOT_CONFIGURED,
                'authentication_unavailable',
                $details,
            );
        }

        try {
            $this->tokens->obtainToken();
        } catch (FaoStatPortalException $e) {
            $readiness = $e->category === FaoStatErrorCategory::AUTHENTICATION_ERROR
                || $e->category === FaoStatErrorCategory::AUTHORIZATION_ERROR
                ? FaoStatReadinessState::AUTHENTICATION_FAILURE
                : FaoStatReadinessState::UPSTREAM_UNAVAILABLE;

            return $this->pack(
                $readiness,
                ProviderHealthState::UNAVAILABLE,
                'authentication_failed',
                $details + ['category' => $e->category, 'http_status' => $e->httpStatus],
            );
        }

        return $this->pack(
            FaoStatReadinessState::READY,
            ProviderHealthState::HEALTHY,
            'authentication_successful',
            $details + ['configured' => true],
        );
    }

    /**
     * Lightweight Research Agent gate. Does not ping FAOSTAT on every search.
     *
     * @return array{ok: bool, readiness: string, error: ?string, details: array<string, mixed>}
     */
    public function searchPreflight(): array
    {
        $details = [
            'circuit_open' => $this->circuit->isOpen(),
            'activation_state' => FaoStatActivationPolicy::activationState(FaoStatDomainCatalog::QCL),
            'active_domains' => FaoStatActivationPolicy::activeDomains(),
        ];

        if (! FaoStatRuntimePolicy::isEnabled()) {
            return [
                'ok' => false,
                'readiness' => FaoStatReadinessState::DISABLED,
                'error' => FaoStatErrorCategory::DISABLED,
                'details' => $details,
            ];
        }

        if ($this->circuit->isOpen()) {
            return [
                'ok' => false,
                'readiness' => FaoStatReadinessState::CIRCUIT_OPEN,
                'error' => FaoStatErrorCategory::CIRCUIT_OPEN,
                'details' => $details,
            ];
        }

        $username = (string) config('agricultural_intelligence.faostat.username', '');
        $password = (string) config('agricultural_intelligence.faostat.password', '');
        if ($username === '' || $password === '') {
            return [
                'ok' => false,
                'readiness' => FaoStatReadinessState::NOT_AUTHENTICATED,
                'error' => FaoStatErrorCategory::NOT_AUTHENTICATED,
                'details' => $details + ['configured' => false],
            ];
        }

        return [
            'ok' => true,
            'readiness' => FaoStatReadinessState::READY,
            'error' => null,
            'details' => $details + ['configured' => true],
        ];
    }

    public function health(): ProviderHealthStatus
    {
        $report = $this->report();

        return new ProviderHealthStatus(
            FaoStatDeveloperPortalAdapter::SOURCE_KEY,
            $report['provider_state'],
            $report['message'],
            $report['details'] + ['readiness' => $report['readiness']],
        );
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{readiness: string, provider_state: string, message: string, details: array<string, mixed>}
     */
    private function pack(string $readiness, string $providerState, string $message, array $details): array
    {
        return [
            'readiness' => $readiness,
            'provider_state' => $providerState,
            'message' => $message,
            'details' => $details,
        ];
    }
}
