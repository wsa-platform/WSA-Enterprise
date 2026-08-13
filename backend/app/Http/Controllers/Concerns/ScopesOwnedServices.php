<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ScopesOwnedServices
{
    protected function ownership(): ServiceOwnershipAuthorizer
    {
        return app(ServiceOwnershipAuthorizer::class);
    }

    /** @param  Builder<Model>  $query */
    protected function scopedOwnedQuery(Request $request, Builder $query): Builder
    {
        return $this->ownership()->scopeAccessibleServices(
            $query->where('organization_id', $this->organization($request)),
            $request->user(),
            $this->organization($request),
        );
    }

    protected function assertOwnedRecord(Request $request, Model $record, string $permission): void
    {
        $this->ownership()->authorizeManage(
            $request->user(),
            $record,
            $this->organization($request),
            $permission,
        );
    }

    /** @param  array<string, mixed>  $payload */
    protected function assignOwnedPayload(Request $request, array $payload): array
    {
        return $this->ownership()->assignOwnerFromSession($payload, $request->user());
    }
}
