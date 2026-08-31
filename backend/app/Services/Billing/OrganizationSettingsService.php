<?php

namespace App\Services\Billing;

use App\Models\OrganizationSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class OrganizationSettingsService
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'operations.timezone',
        'operations.locale',
        'operations.support_email',
        'security.require_mfa',
        'notifications.email_enabled',
        'appearance.theme',
        'appearance.primary_color',
        'appearance.logo_url',
        'appearance.app_name',
    ];

    public function __construct(private AuditService $auditService) {}

    /** @return array<string, mixed> */
    public function allForOrganization(int $organizationId): array
    {
        return OrganizationSetting::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereIn('key', self::ALLOWED_KEYS)
            ->get()
            ->mapWithKeys(fn (OrganizationSetting $setting) => [$setting->key => $setting->value])
            ->all();
    }

    /** @param  array<string, mixed>  $values */
    public function updateForOrganization(
        int $organizationId,
        array $values,
        ?int $userId = null,
        ?Request $request = null,
    ): array {
        $changedKeys = [];

        foreach ($values as $key => $value) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            OrganizationSetting::withoutGlobalScopes()->updateOrCreate(
                ['organization_id' => $organizationId, 'key' => $key],
                ['value' => is_array($value) ? $value : ['value' => $value]],
            );

            $changedKeys[] = $key;
        }

        if ($changedKeys !== []) {
            $this->auditService->record(
                action: 'organization.settings.updated',
                organizationId: $organizationId,
                userId: $userId,
                newValues: ['keys' => $changedKeys],
                request: $request,
            );
        }

        return $this->allForOrganization($organizationId);
    }
}
