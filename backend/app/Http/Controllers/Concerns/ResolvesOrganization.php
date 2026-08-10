<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesOrganization
{
    protected function organization(Request $request): int
    {
        if ($request->attributes->has('organization_id')) {
            return (int) $request->attributes->get('organization_id');
        }

        $tenant = app(\App\Services\Tenancy\TenantContext::class);
        if ($tenant->hasOrganization()) {
            return $tenant->organizationId();
        }

        return $request->user()->organizations()->firstOrFail()->id;
    }

    protected function organizationModel(Request $request)
    {
        return $request->user()->organizations()->findOrFail($this->organization($request));
    }
}
