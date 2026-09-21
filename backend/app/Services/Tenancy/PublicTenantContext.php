<?php

namespace App\Services\Tenancy;

/**
 * Server-authorized public/demo tenant context (MODEL B).
 *
 * Distinct from client request input and from authenticated membership TenantContext binding.
 */
final class PublicTenantContext
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $slug,
    ) {}
}
