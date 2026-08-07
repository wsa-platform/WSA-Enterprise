<?php

namespace App\Http\Controllers\Api;

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
    private function organization(Request $request): int
    {
        return $request->user()->organizations()->firstOrFail()->id;
    }

    public function users(Request $request): JsonResponse
    {
        $organization = $this->organization($request);
        return response()->json($request->user()->organizations()->findOrFail($organization)->members()->with('roles')->get());
    }

    public function storeUser(Request $request): JsonResponse
    {
        $organization = $this->organization($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->organizations()->attach($organization, ['role' => 'member']);
        return response()->json($user, 201);
    }

    public function roles(Request $request): JsonResponse
    {
        return response()->json(Role::where('organization_id', $this->organization($request))->with('permissions')->get());
    }

    public function storeRole(Request $request): JsonResponse
    {
        $organization = $this->organization($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'permission_ids' => ['array']]);
        $role = Role::create(['organization_id' => $organization, ...$data]);
        $role->permissions()->sync(Permission::where('organization_id', $organization)->whereIn('id', $data['permission_ids'] ?? [])->pluck('id'));
        return response()->json($role->load('permissions'), 201);
    }

    public function permissions(Request $request): JsonResponse
    {
        return response()->json(Permission::where('organization_id', $this->organization($request))->get());
    }

    public function storePermission(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);
        return response()->json(Permission::create(['organization_id' => $this->organization($request), ...$data]), 201);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $organization = $this->organization($request);
        abort_unless($user->organizations()->whereKey($organization)->exists(), 404);
        $data = $request->validate(['role_id' => ['required', Rule::exists('roles', 'id')]]);
        $role = Role::where('organization_id', $organization)->findOrFail($data['role_id']);
        DB::table('role_user')->updateOrInsert(['role_id' => $role->id, 'user_id' => $user->id, 'organization_id' => $organization]);
        return response()->json($user->load('roles'));
    }
}
