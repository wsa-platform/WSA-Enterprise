<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Services\Settings\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(private UserSettingsService $userSettingsService) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $user = $request->user();

        return response()->json(
            $this->userSettingsService->allForUser($user->id, $organizationId)
        );
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $user = $request->user();

        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        return response()->json(
            $this->userSettingsService->updateForUser(
                $user->id,
                $organizationId,
                $data['settings'],
                $request,
            )
        );
    }
}
