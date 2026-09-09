<?php

namespace App\Services\Agriculture\Intelligence\Adapters;

use App\Contracts\Agriculture\AgriculturalProviderInterface;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;

/**
 * Base provider with failure isolation.
 */
abstract class AbstractAgriculturalProvider implements AgriculturalProviderInterface
{
    abstract protected function buildDescriptor(): ProviderDescriptor;

    abstract protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult;

    public function descriptor(): ProviderDescriptor
    {
        return $this->buildDescriptor();
    }

    public function health(): ProviderHealthStatus
    {
        $d = $this->descriptor();
        if (! $d->enabled) {
            return new ProviderHealthStatus($d->id, ProviderHealthState::NOT_CONFIGURED, 'disabled');
        }

        $authMode = (string) ($d->auth['mode'] ?? 'none');
        if (in_array($authMode, ['required_key', 'required_endpoint'], true) && ! $this->isConfigured()) {
            return new ProviderHealthStatus($d->id, ProviderHealthState::NOT_CONFIGURED, 'NOT_CONFIGURED');
        }

        if (($d->auth['blocked'] ?? false) === true) {
            return new ProviderHealthStatus($d->id, ProviderHealthState::BLOCKED, (string) ($d->auth['block_reason'] ?? 'BLOCKED'));
        }

        return new ProviderHealthStatus($d->id, ProviderHealthState::HEALTHY, 'ok');
    }

    public function retrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        try {
            if (! $this->descriptor()->enabled) {
                return CanonicalAgriculturalResult::notConfigured($this->descriptor()->id, 'disabled');
            }
            if (! $this->isConfigured()) {
                return CanonicalAgriculturalResult::notConfigured($this->descriptor()->id);
            }

            return $this->doRetrieve($input);
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed($this->descriptor()->id, 'isolated_failure:'.$e::class);
        }
    }

    protected function isConfigured(): bool
    {
        return true;
    }
}
