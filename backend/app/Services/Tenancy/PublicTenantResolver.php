<?php

namespace App\Services\Tenancy;

use App\Models\Organization;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the server-configured public/demo organization for unauthenticated public flows (MODEL B).
 *
 * Never trusts client-supplied organization_id / organization / organization_slug.
 */
final class PublicTenantResolver
{
    public const ACTION_CLIENT_OVERRIDE_IGNORED = 'security.public_tenant_client_override_ignored';

    public const ACTION_PUBLIC_TENANT_UNAVAILABLE = 'security.public_tenant_unavailable';

    public function __construct(
        private TenantContext $tenant,
        private AuditService $audit,
    ) {}

    /**
     * Resolve and bind the public tenant into request-scoped TenantContext.
     *
     * Per Phase 8A-2 H2: successful bind does not emit a durable audit event.
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
            $this->auditPublicTenantUnavailable('empty_public_organization_slug');

            throw new PublicTenantResolutionException('Public organization is unavailable.');
        }

        $organization = Organization::query()->where('slug', $slug)->first();

        if ($organization === null) {
            Log::warning('security.public_tenant_not_found', [
                'configured_slug' => $slug,
            ]);
            $this->auditPublicTenantUnavailable('not_found');

            throw new PublicTenantResolutionException('Public organization is unavailable.');
        }

        if ($organization->is_active === false) {
            Log::warning('security.public_tenant_inactive', [
                'configured_slug' => $slug,
                'organization_id' => $organization->id,
            ]);
            $this->auditPublicTenantUnavailable('inactive', (int) $organization->id);

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

        $clientOrganizationIdPresent = $clientId !== null;
        $clientOrganizationSlugPresent = $clientSlug !== null && $clientSlug !== '';

        Log::info('security.public_tenant_client_override_ignored', [
            'bound_organization_id' => $bound->organizationId,
            'bound_slug' => $bound->slug,
            'client_organization_id_present' => $clientOrganizationIdPresent,
            'client_organization_slug_present' => $clientOrganizationSlugPresent,
        ]);

        $this->audit->record(
            action: self::ACTION_CLIENT_OVERRIDE_IGNORED,
            organizationId: $bound->organizationId,
            userId: null,
            newValues: [
                'category' => 'client_organization_override_ignored',
                'client_organization_id_present' => $clientOrganizationIdPresent,
                'client_organization_slug_present' => $clientOrganizationSlugPresent,
            ],
            request: $this->currentRequest(),
        );
    }

    private function auditPublicTenantUnavailable(string $reason, ?int $organizationId = null): void
    {
        $this->audit->record(
            action: self::ACTION_PUBLIC_TENANT_UNAVAILABLE,
            organizationId: $organizationId,
            userId: null,
            newValues: [
                'category' => 'public_organization_unavailable',
                'reason' => $reason,
            ],
            request: $this->currentRequest(),
        );
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }
}
