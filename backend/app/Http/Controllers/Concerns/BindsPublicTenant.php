<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Organization;
use App\Services\Tenancy\PublicTenantResolver;
use Illuminate\Http\Request;

trait BindsPublicTenant
{
    /**
     * Server-authoritative public tenant (MODEL B).
     * Client organization / organization_id are compatibility input only.
     *
     * @param  array<string, mixed>  $clientInput
     */
    protected function bindPublicOrganization(Request $request, array $clientInput = []): Organization
    {
        $resolver = app(PublicTenantResolver::class);
        $bound = $resolver->bindPublicTenant();
        $resolver->recordIgnoredClientOrganizationInput(
            $clientInput !== [] ? $clientInput : $request->all(),
            $bound,
        );

        return Organization::query()->findOrFail($bound->organizationId);
    }
}
