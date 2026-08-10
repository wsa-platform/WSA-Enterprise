<?php

namespace App\Models\Concerns;

use App\Services\Audit\AuditService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditService::class)->record(
                action: 'created',
                organizationId: $model->getAttribute('organization_id'),
                userId: auth()->id(),
                auditable: $model,
                newValues: $model->getAttributes(),
                request: request(),
            );
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            app(AuditService::class)->record(
                action: 'updated',
                organizationId: $model->getAttribute('organization_id'),
                userId: auth()->id(),
                auditable: $model,
                oldValues: $old,
                newValues: $changes,
                request: request(),
            );
        });

        static::deleted(function (Model $model): void {
            app(AuditService::class)->record(
                action: 'deleted',
                organizationId: $model->getAttribute('organization_id'),
                userId: auth()->id(),
                auditable: $model,
                oldValues: $model->getAttributes(),
                request: request(),
            );
        });
    }
}
