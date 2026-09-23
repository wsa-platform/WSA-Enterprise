<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Authorization\PlatformRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMeController extends Controller
{
    public function __construct(private PlatformRbacService $rbac) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401, 'Unauthenticated.');

        $isAdmin = $user->isPlatformAdministrator();

        return response()->json([
            'is_platform_administrator' => $isAdmin,
            'user' => $user->only(['id', 'name', 'email']),
            'platform_roles' => $isAdmin
                ? $user->platformRoles()->get(['platform_roles.id', 'platform_roles.slug', 'platform_roles.name'])
                : [],
            'platform_permissions' => $isAdmin ? $this->rbac->permissionsFor($user) : [],
        ]);
    }
}
