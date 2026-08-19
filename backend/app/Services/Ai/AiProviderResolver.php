<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderUnavailableException;
use App\Models\OrganizationSetting;

class AiProviderResolver
{
    public function forOrganization(?int $organizationId): AiProviderInterface
    {
        $requested = $this->requestedName($organizationId);
        $this->assertImplemented($requested);

        return $this->make($requested);
    }

    public function providerNameForOrganization(?int $organizationId): string
    {
        return $this->forOrganization($organizationId)->name();
    }

    /** @return array<string, mixed> */
    public function describe(?int $organizationId): array
    {
        $requested = $this->requestedName($organizationId);
        $provider = $this->forOrganization($organizationId);

        return [
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'requested_provider' => $requested,
            'fallback_provider' => $this->fallbackName(),
            'used_fallback' => false,
        ];
    }

    public function requestedName(?int $organizationId): string
    {
        $override = $this->organizationProviderOverride($organizationId);

        return $this->normalize($override ?? (string) config('ai.provider', 'mock'));
    }

    public function fallbackName(): string
    {
        return $this->normalize((string) config('ai.fallback_provider', 'mock'));
    }

    /** @return list<string> */
    public function implementedProviders(): array
    {
        $configured = config('ai.implemented_providers', ['mock']);

        return is_array($configured) ? array_values(array_map('strval', $configured)) : ['mock'];
    }

    private function assertImplemented(string $provider): void
    {
        if (! in_array($provider, $this->implementedProviders(), true)) {
            throw new AiProviderUnavailableException($provider);
        }
    }

    private function make(string $provider): AiProviderInterface
    {
        return match ($provider) {
            'mock' => app(MockAiProvider::class),
            default => throw new AiProviderUnavailableException($provider),
        };
    }

    private function normalize(string $provider): string
    {
        return strtolower(trim($provider));
    }

    private function organizationProviderOverride(?int $organizationId): ?string
    {
        if ($organizationId === null) {
            return null;
        }

        $setting = OrganizationSetting::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('key', 'ai.provider')
            ->first();

        $value = $setting?->value;

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value) && isset($value['provider']) && is_string($value['provider']) && $value['provider'] !== '') {
            return $value['provider'];
        }

        return null;
    }
}
