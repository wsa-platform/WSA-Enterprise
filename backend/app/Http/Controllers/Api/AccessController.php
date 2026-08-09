<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
            $roles = $user->roles()->wherePivot('organization_id', $organization)->get(['roles.id', 'roles.name']);

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
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->organizations()->attach($organization, ['role' => 'member']);

        return response()->json($user->only(['id', 'name', 'email']), 201);
    }

    public function roles(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Role::where('organization_id', $this->organization($request))->with('permissions')->latest()
        );
    }

    public function storeRole(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'permission_ids' => ['array']]);
        $role = Role::create(['organization_id' => $organization, ...$data]);
        $role->permissions()->sync(Permission::where('organization_id', $organization)->whereIn('id', $data['permission_ids'] ?? [])->pluck('id'));

        return response()->json($role->load('permissions'), 201);
    }

    public function permissions(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Permission::where('organization_id', $this->organization($request))->latest()
        );
    }

    public function storePermission(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);

        return response()->json(Permission::create(['organization_id' => $this->organization($request), ...$data]), 201);
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
        DB::table('role_user')->updateOrInsert(['role_id' => $role->id, 'user_id' => $user->id, 'organization_id' => $organization]);

        return response()->json([
            ...$user->only(['id', 'name', 'email']),
            'roles' => $user->roles()->wherePivot('organization_id', $organization)->get(['roles.id', 'roles.name']),
        ]);
    }
}
