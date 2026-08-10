<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OrganizationScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null) {
                return;
            }

            $tenant = app(TenantContext::class);
            if ($tenant->hasOrganization()) {
                $model->setAttribute('organization_id', $tenant->organizationId());
            }
        });
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}
