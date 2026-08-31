<?php

namespace App\Services\Settings;

use App\Models\UserSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class UserSettingsService
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'appearance.theme',
        'appearance.primary_color',
        'appearance.secondary_color',
        'appearance.sidebar_color',
        'appearance.logo_url',
        'notifications.email',
        'notifications.push',
        'notifications.in_app',
        'notifications.marketing',
        'security.session_timeout',
    ];

    public function __construct(private AuditService $auditService) {}

    /** @return array<string, mixed> */
    public function allForUser(int $userId, int $organizationId): array
    {
        return UserSetting::query()
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->whereIn('key', self::ALLOWED_KEYS)
            ->get()
            ->mapWithKeys(fn (UserSetting $setting) => [$setting->key => $setting->value])
            ->all();
    }

    /** @param  array<string, mixed>  $values */
    public function updateForUser(
        int $userId,
        int $organizationId,
        array $values,
        ?Request $request = null,
    ): array {
        $changedKeys = [];

        foreach ($values as $key => $value) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            UserSetting::updateOrCreate(
                ['user_id' => $userId, 'organization_id' => $organizationId, 'key' => $key],
                ['value' => is_array($value) ? $value : ['value' => $value]],
            );

            $changedKeys[] = $key;
        }

        if ($changedKeys !== []) {
            $this->auditService->record(
                action: 'user.settings.updated',
                organizationId: $organizationId,
                userId: $userId,
                newValues: ['keys' => $changedKeys],
                request: $request,
            );
        }

        return $this->allForUser($userId, $organizationId);
    }
}
