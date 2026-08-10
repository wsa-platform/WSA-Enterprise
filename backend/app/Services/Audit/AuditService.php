<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'api_token',
        'secret',
        'app_key',
        'authorization',
    ];

    /** @param  array<string, mixed>|null  $oldValues */
    /** @param  array<string, mixed>|null  $newValues */
    public function record(
        string $action,
        ?int $organizationId = null,
        ?int $userId = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): AuditLog {
        $requestId = $request?->attributes->get('request_id');

        if ($requestId !== null && is_array($newValues)) {
            $newValues['request_id'] = $requestId;
        } elseif ($requestId !== null && $newValues === null) {
            $newValues = ['request_id' => $requestId];
        }

        return AuditLog::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => is_string($requestId) ? $requestId : null,
        ]);
    }

    /** @param  array<string, mixed>|null  $values */
    public function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (self::SENSITIVE_KEYS as $key) {
            unset($values[$key]);
        }

        return $values;
    }
}
