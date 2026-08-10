<?php

namespace App\Services\Billing;

use App\Models\OrganizationSetting;

class OrganizationSettingsService
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'operations.timezone',
        'operations.locale',
        'operations.support_email',
        'security.require_mfa',
        'notifications.email_enabled',
    ];

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
    public function updateForOrganization(int $organizationId, array $values): array
    {
        foreach ($values as $key => $value) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            OrganizationSetting::withoutGlobalScopes()->updateOrCreate(
                ['organization_id' => $organizationId, 'key' => $key],
                ['value' => is_array($value) ? $value : ['value' => $value]],
            );
        }

        return $this->allForOrganization($organizationId);
    }
}
