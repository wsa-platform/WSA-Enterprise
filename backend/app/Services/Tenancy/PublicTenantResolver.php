<?php

namespace App\Services\Tenancy;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the server-configured public/demo organization for unauthenticated public flows (MODEL B).
 *
 * Never trusts client-supplied organization_id / organization / organization_slug.
 */
final class PublicTenantResolver
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    /**
     * Resolve and bind the public tenant into request-scoped TenantContext.
     */
    public function bindPublicTenant(): PublicTenantContext
    {
        $context = $this->resolve();
        $this->tenant->bindPublicTenant($context);

        return $context;
    }

    /**
     * Resolve configured public organization without binding (for tests / introspection).
     */
    public function resolve(): PublicTenantContext
    {
        $slug = trim((string) config('wsa.public_organization_slug', 'wsa-demo'));

        if ($slug === '') {
            Log::warning('security.public_tenant_config_invalid', [
                'reason' => 'empty_public_organization_slug',
            ]);

            throw new PublicTenantResolutionException('Public organization is unavailable.');
        }

        $organization = Organization::query()->where('slug', $slug)->first();

        if ($organization === null) {
            Log::warning('security.public_tenant_not_found', [
                'configured_slug' => $slug,
            ]);

            throw new PublicTenantResolutionException('Public organization is unavailable.');
        }

        if ($organization->is_active === false) {
            Log::warning('security.public_tenant_inactive', [
                'configured_slug' => $slug,
                'organization_id' => $organization->id,
            ]);

            throw new PublicTenantResolutionException('Public organization is unavailable.');
        }

        return new PublicTenantContext(
            organizationId: (int) $organization->id,
            slug: (string) $organization->slug,
        );
    }

    /**
     * Observability only — never changes tenant selection.
     *
     * @param  array<string, mixed>  $validated
     */
    public function recordIgnoredClientOrganizationInput(array $validated, PublicTenantContext $bound): void
    {
        $clientId = isset($validated['organization_id']) ? (int) $validated['organization_id'] : null;
        $clientSlug = isset($validated['organization']) ? trim((string) $validated['organization']) : null;

        if ($clientId === null && ($clientSlug === null || $clientSlug === '')) {
            return;
        }

        $mismatched = ($clientId !== null && $clientId !== $bound->organizationId)
            || ($clientSlug !== null && $clientSlug !== '' && $clientSlug !== $bound->slug);

        if (! $mismatched) {
            return;
        }

        Log::info('security.public_tenant_client_override_ignored', [
            'bound_organization_id' => $bound->organizationId,
            'bound_slug' => $bound->slug,
            'client_organization_id_present' => $clientId !== null,
            'client_organization_slug_present' => $clientSlug !== null && $clientSlug !== '',
        ]);
    }
}
