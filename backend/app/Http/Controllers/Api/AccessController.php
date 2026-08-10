<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionCacheInvalidator;
use App\Services\Authorization\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccessController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function users(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        $organizationModel = $request->user()->organizations()->findOrFail($organization);

        $transform = function (User $user) use ($organization): array {
            $roles = $user->roles()->wherePivot('organization_id', $organization)->get(['roles.id', 'roles.name', 'roles.slug']);

            return [
                ...$user->only(['id', 'name', 'email']),
                'roles' => $roles,
            ];
        };

        if (! $request->has('page') && ! $request->has('per_page')) {
            return response()->json($organizationModel->members()->get()->map($transform)->values());
        }

        $paginator = $organizationModel->members()->paginate(
            min(max((int) $request->query('per_page', 15), 1), 100)
        );
        $paginator->getCollection()->transform($transform);

        return response()->json($paginator);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        app(\App\Services\Billing\EntitlementService::class)->assertUserCapacity($organization, $request->user()->id);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->organizations()->attach($organization, ['role' => 'member']);

        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization);

        app(AuditService::class)->record(
            action: 'user.created',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $user,
            newValues: ['email' => $user->email, 'name' => $user->name],
            request: $request,
        );

        return response()->json($user->only(['id', 'name', 'email']), 201);
    }

    public function roles(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Role::query()->with('permissions')->latest()
        );
    }

    public function storeRole(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:64', Rule::unique('roles', 'slug')->where('organization_id', $organization)],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['array'],
        ]);

        abort_if(
            isset($data['slug']) && in_array($data['slug'], config('enterprise_roles.privileged_slugs', []), true)
            && ! app(EnterpriseRoleService::class)->canAssignRole($request->user(), $organization, new Role(['slug' => $data['slug']])),
            403,
            'You cannot create privileged enterprise roles.'
        );

        $role = Role::create([
            'organization_id' => $organization,
            'slug' => $data['slug'] ?? null,
            ...collect($data)->only(['name', 'description'])->all(),
        ]);
        $role->permissions()->sync(Permission::where('organization_id', $organization)->whereIn('id', $data['permission_ids'] ?? [])->pluck('id'));

        app(PermissionCacheInvalidator::class)->forgetOrganization($organization);

        app(AuditService::class)->record(
            action: 'role.created',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $role,
            newValues: ['name' => $role->name, 'slug' => $role->slug],
            request: $request,
        );

        return response()->json($role->load('permissions'), 201);
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Permission::query()->latest()
        );
    }

    public function storePermission(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);

        $permission = Permission::create(['organization_id' => $organization, ...$data]);

        app(PermissionCacheInvalidator::class)->forgetOrganization($organization);

        app(AuditService::class)->record(
            action: 'permission.created',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $permission,
            newValues: ['name' => $permission->name],
            request: $request,
        );

        return response()->json($permission, 201);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);
        $data = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('organization_id', $organization)],
        ]);
        $role = Role::where('organization_id', $organization)->findOrFail($data['role_id']);

        abort_unless(
            app(EnterpriseRoleService::class)->canAssignRole($request->user(), $organization, $role),
            403,
            'You cannot assign this role.'
        );

        DB::table('role_user')->updateOrInsert(['role_id' => $role->id, 'user_id' => $user->id, 'organization_id' => $organization]);

        app(PermissionService::class)->forget($user, $organization);

        app(AuditService::class)->record(
            action: 'role.assigned',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $user,
            newValues: ['role_id' => $role->id, 'role_name' => $role->name, 'role_slug' => $role->slug],
            request: $request,
        );

        return response()->json([
            ...$user->only(['id', 'name', 'email']),
            'roles' => $user->roles()->wherePivot('organization_id', $organization)->get(['roles.id', 'roles.name', 'roles.slug']),
        ]);
    }
}
