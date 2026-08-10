<?php

namespace App\Services\Tenancy;

class TenantContext
{
    private ?int $organizationId = null;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
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
