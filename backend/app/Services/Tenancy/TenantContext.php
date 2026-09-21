<?php

namespace App\Services\Tenancy;

class TenantContext
{
    private ?int $organizationId = null;

    /** When true, organizationId was bound via PublicTenantResolver (MODEL B public flows). */
    private bool $publicBound = false;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
        $this->publicBound = false;
    }

    /**
     * Bind server-authorized public/demo tenant for unauthenticated public write flows.
     */
    public function bindPublicTenant(PublicTenantContext $context): void
    {
        $this->organizationId = $context->organizationId;
        $this->publicBound = true;
    }

    public function isPublicBound(): bool
    {
        return $this->publicBound && $this->organizationId !== null;
    }

    public function organizationId(): ?int
    {
        return $this->organizationId;
    }

    public function hasOrganization(): bool
    {
        return $this->organizationId !== null;
    }

    public function requireOrganizationId(): int
    {
        abort_unless($this->hasOrganization(), 403, 'Organization context is required.');

        return $this->organizationId;
    }
}
