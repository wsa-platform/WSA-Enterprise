<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlatformSettingsController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function __construct(private AuditService $audit) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.settings.view');

        $settings = PlatformSetting::query()->orderBy('key')->get()
            ->mapWithKeys(fn (PlatformSetting $setting) => [$setting->key => $setting->value]);

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.settings.manage');

        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            abort_unless(is_string($key) && $key !== '', 422, 'Invalid platform setting key.');
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? $value : ['value' => $value]],
            );
        }

        $this->audit->record(
            action: 'admin.settings.upd',
            organizationId: null,
            userId: $request->user()->id,
            newValues: ['keys' => array_keys($data['settings'])],
            request: $request,
        );

        return $this->show($request);
    }
}
