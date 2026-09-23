<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PlatformRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function __construct(
        private AuditService $audit,
        private PlatformRbacService $rbac,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.users.view');

        $query = User::query()->orderBy('name');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($builder) use ($search, $like): void {
                $builder->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%");
            });
        }

        $paginator = $query->paginate(min(max((int) $request->query('per_page', 15), 1), 100));
        $paginator->getCollection()->transform(fn (User $user) => $this->present($user));

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_platform_administrator' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? Str::random(16)),
            'is_platform_administrator' => (bool) ($data['is_platform_administrator'] ?? false),
        ]);

        if ($user->is_platform_administrator) {
            $this->rbac->grantPlatformAdministrator($user);
        }

        $this->audit->record(
            action: 'admin.user.created',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $user,
            newValues: [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_platform_administrator' => $user->is_platform_administrator,
            ],
            request: $request,
        );

        return response()->json($this->present($user->fresh()), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.users.manage');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'is_platform_administrator' => ['sometimes', 'boolean'],
        ]);

        $old = $user->only(['name', 'email', 'is_platform_administrator']);
        $user->update(collect($data)->only(['name', 'email', 'is_platform_administrator'])->all());

        if (array_key_exists('is_platform_administrator', $data) && $data['is_platform_administrator']) {
            $this->rbac->grantPlatformAdministrator($user);
        }

        $this->audit->record(
            action: 'admin.user.updated',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $user,
            oldValues: $old,
            newValues: $user->fresh()->only(['name', 'email', 'is_platform_administrator']),
            request: $request,
        );

        return response()->json($this->present($user->fresh()));
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.roles.manage');

        $data = $request->validate([
            'role_slug' => ['required', 'string', 'exists:platform_roles,slug'],
        ]);

        $this->rbac->grantPlatformRole($user, $data['role_slug']);

        $this->audit->record(
            action: 'admin.role.assign',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $user,
            newValues: [
                'member_user_id' => $user->id,
                'role_slug' => $data['role_slug'],
            ],
            request: $request,
        );

        return response()->json($this->present($user->fresh()));
    }

    public function roles(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.roles.manage');

        $paginator = PlatformRole::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate(min(max((int) $request->query('per_page', 25), 1), 100));

        return response()->json($paginator);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.roles.manage');

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:64', 'unique:platform_roles,slug'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:platform_permissions,id'],
        ]);

        $role = $this->rbac->createRole(
            $data['slug'],
            $data['name'],
            $data['description'] ?? null,
            $data['permission_ids'] ?? [],
        );

        $this->audit->record(
            action: 'admin.role.created',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $role,
            newValues: [
                'role_id' => $role->id,
                'slug' => $role->slug,
            ],
            request: $request,
        );

        return response()->json($role, 201);
    }

    public function updateRole(Request $request, PlatformRole $platformRole): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.roles.manage');

        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:64', Rule::unique('platform_roles', 'slug')->ignore($platformRole->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:platform_permissions,id'],
        ]);

        $role = $this->rbac->updateRole(
            $platformRole,
            $data,
            $data['permission_ids'] ?? null,
        );

        $this->audit->record(
            action: 'admin.role.updated',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $role,
            newValues: [
                'role_id' => $role->id,
                'slug' => $role->slug,
                'permission_ids' => $data['permission_ids'] ?? null,
            ],
            request: $request,
        );

        return response()->json($role);
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.roles.manage');

        $this->rbac->bootstrapCatalog();

        $permissions = PlatformPermission::query()->orderBy('name')->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $permissions,
            'catalog' => PlatformRbacService::permissionCatalog(),
            'groups' => [
                'platform' => $permissions->map(fn (PlatformPermission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])->values(),
            ],
            'current_page' => 1,
            'last_page' => 1,
            'total' => $permissions->count(),
        ]);
    }

    /** @return array<string, mixed> */
    private function present(User $user): array
    {
        return [
            ...$user->only(['id', 'name', 'email', 'is_platform_administrator', 'created_at']),
            'platform_roles' => $user->platformRoles()->get(['platform_roles.id', 'platform_roles.slug', 'platform_roles.name']),
        ];
    }
}
