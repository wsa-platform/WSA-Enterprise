<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
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

        $transform = fn (User $user): array => $this->transformUser($user, $organization);

        if (! $request->has('page') && ! $request->has('per_page')) {
            return response()->json($organizationModel->members()->get()->map($transform)->values());
        }

        $paginator = $organizationModel->members()->paginate(
            min(max((int) $request->query('per_page', 15), 1), 100)
        );
        $paginator->getCollection()->transform($transform);

        return response()->json($paginator);
    }

    public function showUser(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);

        return response()->json($this->transformUser($user, $organization));
    }

    public function storeUser(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        app(\App\Services\Billing\EntitlementService::class)->assertUserCapacity($organization, $request->user()->id);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->organizations()->attach($organization, ['role' => 'member', 'is_active' => true]);

        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization);

        app(AuditService::class)->record(
            action: 'user.created',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $user,
            newValues: ['email' => $user->email, 'name' => $user->name],
            request: $request,
        );

        return response()->json($this->transformUser($user, $organization), 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'membership_role' => ['sometimes', Rule::in(['admin', 'member'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $actor = $request->user();
        $enterpriseRoles = app(EnterpriseRoleService::class);

        if (array_key_exists('is_active', $data) && $data['is_active'] === false) {
            abort_if($actor->id === $user->id, 422, 'You cannot deactivate your own account.');
            abort_if(
                $enterpriseRoles->userIsOwner($user, $organization) && $enterpriseRoles->countOwners($organization) <= 1,
                422,
                'Cannot deactivate the last organization owner.',
            );
        }

        if (isset($data['membership_role']) && $data['membership_role'] === 'member') {
            abort_if(
                $enterpriseRoles->userIsOwner($user, $organization) && $enterpriseRoles->countOwners($organization) <= 1,
                422,
                'Cannot change membership role of the last organization owner.',
            );
        }

        $oldMembership = $user->organizations()->whereKey($organization)->first()?->pivot;
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'membership_role' => $oldMembership->role ?? 'member',
            'is_active' => (bool) ($oldMembership->is_active ?? true),
        ];

        if (isset($data['name']) || isset($data['email'])) {
            $user->update(collect($data)->only(['name', 'email'])->all());
        }

        $pivotUpdates = collect($data)->only(['membership_role', 'is_active'])->all();
        if ($pivotUpdates !== []) {
            if (isset($pivotUpdates['membership_role'])) {
                $pivotUpdates['role'] = $pivotUpdates['membership_role'];
                unset($pivotUpdates['membership_role']);
            }
            $user->organizations()->updateExistingPivot($organization, $pivotUpdates);
        }

        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization);

        app(AuditService::class)->record(
            action: 'user.updated',
            organizationId: $organization,
            userId: $actor->id,
            auditable: $user,
            oldValues: $oldValues,
            newValues: collect($this->transformUser($user->fresh(), $organization))
                ->only(['name', 'email', 'membership_role', 'is_active'])
                ->all(),
            request: $request,
        );

        return response()->json($this->transformUser($user->fresh(), $organization));
    }

    public function removeUser(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);

        $actor = $request->user();
        abort_if($actor->id === $user->id, 422, 'You cannot remove yourself from the organization.');

        $enterpriseRoles = app(EnterpriseRoleService::class);
        abort_if(
            $enterpriseRoles->userIsOwner($user, $organization) && $enterpriseRoles->countOwners($organization) <= 1,
            422,
            'Cannot remove the last organization owner.',
        );

        DB::table('role_user')
            ->where('organization_id', $organization)
            ->where('user_id', $user->id)
            ->delete();

        $teamIds = Team::where('organization_id', $organization)->pluck('id');
        if ($teamIds->isNotEmpty()) {
            DB::table('team_user')
                ->where('user_id', $user->id)
                ->whereIn('team_id', $teamIds)
                ->delete();
        }

        $user->organizations()->detach($organization);
        app(PermissionService::class)->forget($user, $organization);

        app(AuditService::class)->record(
            action: 'user.removed',
            organizationId: $organization,
            userId: $actor->id,
            auditable: $user,
            oldValues: ['email' => $user->email, 'name' => $user->name],
            request: $request,
        );

        return response()->json(['message' => 'User removed from organization.']);
    }

    public function roles(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Role::query()->with('permissions')->latest()
        );
    }

    public function showRole(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        abort_unless($role->organization_id === $this->organization($request), 404);

        return response()->json($role->load('permissions'));
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

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($role->organization_id === $organization, 404);

        $enterpriseRoles = app(EnterpriseRoleService::class);
        abort_if($enterpriseRoles->isSystemRole($role), 422, 'System enterprise roles cannot be modified.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['sometimes', 'array'],
        ]);

        $oldValues = [
            'name' => $role->name,
            'description' => $role->description,
            'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
        ];

        $role->update(collect($data)->only(['name', 'description'])->all());

        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync(
                Permission::where('organization_id', $organization)
                    ->whereIn('id', $data['permission_ids'] ?? [])
                    ->pluck('id')
            );
        }

        app(PermissionCacheInvalidator::class)->forgetOrganization($organization);

        app(AuditService::class)->record(
            action: 'role.updated',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $role,
            oldValues: $oldValues,
            newValues: [
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
            ],
            request: $request,
        );

        return response()->json($role->fresh()->load('permissions'));
    }

    public function deleteRole(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($role->organization_id === $organization, 404);

        $enterpriseRoles = app(EnterpriseRoleService::class);
        abort_if($enterpriseRoles->isSystemRole($role), 422, 'System enterprise roles cannot be deleted.');

        abort_if(
            DB::table('role_user')->where('role_id', $role->id)->where('organization_id', $organization)->exists(),
            422,
            'Role is assigned to users and cannot be deleted.',
        );

        app(AuditService::class)->record(
            action: 'role.deleted',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $role,
            oldValues: ['name' => $role->name, 'slug' => $role->slug],
            request: $request,
        );

        $role->permissions()->detach();
        $role->delete();
        app(PermissionCacheInvalidator::class)->forgetOrganization($organization);

        return response()->json(['message' => 'Role deleted.']);
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

    public function updatePermission(Request $request, Permission $permission): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($permission->organization_id === $organization, 404);

        $enterpriseRoles = app(EnterpriseRoleService::class);
        abort_if($enterpriseRoles->isCatalogPermission($permission->name), 422, 'Catalog permissions cannot be modified.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $oldValues = $permission->only(['name', 'description']);
        $permission->update($data);

        app(AuditService::class)->record(
            action: 'permission.updated',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $permission,
            oldValues: $oldValues,
            newValues: $permission->only(['name', 'description']),
            request: $request,
        );

        return response()->json($permission);
    }

    public function deletePermission(Request $request, Permission $permission): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($permission->organization_id === $organization, 404);

        $enterpriseRoles = app(EnterpriseRoleService::class);
        abort_if($enterpriseRoles->isCatalogPermission($permission->name), 422, 'Catalog permissions cannot be deleted.');

        abort_if(
            DB::table('permission_role')->where('permission_id', $permission->id)->exists(),
            422,
            'Permission is assigned to roles and cannot be deleted.',
        );

        app(AuditService::class)->record(
            action: 'permission.deleted',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $permission,
            oldValues: ['name' => $permission->name],
            request: $request,
        );

        $permission->delete();
        app(PermissionCacheInvalidator::class)->forgetOrganization($organization);

        return response()->json(['message' => 'Permission deleted.']);
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

        return response()->json($this->transformUser($user->fresh(), $organization));
    }

    public function unassignRole(Request $request, User $user, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);
        abort_unless($role->organization_id === $organization, 404);

        $enterpriseRoles = app(EnterpriseRoleService::class);
        if ($role->slug === 'owner') {
            abort_if(
                $enterpriseRoles->userIsOwner($user, $organization) && $enterpriseRoles->countOwners($organization) <= 1,
                422,
                'Cannot unassign the last organization owner role.',
            );
        }

        DB::table('role_user')
            ->where('role_id', $role->id)
            ->where('user_id', $user->id)
            ->where('organization_id', $organization)
            ->delete();

        app(PermissionService::class)->forget($user, $organization);

        app(AuditService::class)->record(
            action: 'role.unassigned',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $user,
            oldValues: ['role_id' => $role->id, 'role_name' => $role->name, 'role_slug' => $role->slug],
            request: $request,
        );

        return response()->json($this->transformUser($user->fresh(), $organization));
    }

    /** @return array<string, mixed> */
    private function transformUser(User $user, int $organization): array
    {
        $membership = $user->organizations()->whereKey($organization)->first()?->pivot;
        $roles = $user->roles()->wherePivot('organization_id', $organization)->get(['roles.id', 'roles.name', 'roles.slug']);

        return [
            ...$user->only(['id', 'name', 'email']),
            'membership_role' => $membership->role ?? 'member',
            'is_active' => (bool) ($membership->is_active ?? true),
            'roles' => $roles,
        ];
    }
}
