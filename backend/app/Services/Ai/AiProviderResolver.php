<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Models\OrganizationSetting;

class AiProviderResolver
{
    public function forOrganization(?int $organizationId): AiProviderInterface
    {
        $provider = $this->organizationProviderOverride($organizationId)
            ?? config('ai.provider', 'mock');

        return match ($provider) {
            'mock' => app(MockAiProvider::class),
            default => app(MockAiProvider::class),
        };
    }

    public function providerNameForOrganization(?int $organizationId): string
    {
        return $this->forOrganization($organizationId)->name();
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

        return is_string($value) ? $value : (is_array($value) ? ($value['provider'] ?? null) : null);
    }
}
