<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformAdminController extends Controller
{
    use AuthorizesOrganizationAccess;
    use AuthorizesPlatformAdmin;
    use PaginatesOrganizationRecords;

    public function __construct(
        private AuditService $auditService,
        private EnterpriseRoleService $enterpriseRoles,
    ) {}

    public function organizations(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        $query = Organization::query()->withCount('members');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = Organization::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($builder) use ($search, $like): void {
                $builder->where('name', $like, "%{$search}%")
                    ->orWhere('slug', $like, "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderBy('name')->paginate(
            min(max((int) $request->query('per_page', 15), 1), 100)
        );

        $paginator->getCollection()->transform(fn (Organization $organization) => $this->transformOrganization($organization));

        return response()->json($paginator);
    }

    public function storeOrganization(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'unique:organizations,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $organization = Organization::create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->enterpriseRoles->seedForOrganization($organization->id);

        $this->auditService->record(
            action: 'organization.created',
            organizationId: $this->organization($request),
            userId: $request->user()->id,
            auditable: $organization,
            newValues: $organization->only(['id', 'name', 'slug', 'is_active']),
            request: $request,
        );

        return response()->json($this->transformOrganization($organization->loadCount('members')), 201);
    }

    public function showOrganization(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        return response()->json($this->transformOrganization($organization->loadCount('members')));
    }

    public function updateOrganization(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:64',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $oldValues = $organization->only(['name', 'slug', 'is_active']);
        $organization->update($data);

        $this->auditService->record(
            action: 'organization.updated',
            organizationId: $this->organization($request),
            userId: $request->user()->id,
            auditable: $organization,
            oldValues: $oldValues,
            newValues: $organization->only(['name', 'slug', 'is_active']),
            request: $request,
        );

        return response()->json($this->transformOrganization($organization->fresh()->loadCount('members')));
    }

    public function organizationMembers(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        $members = $organization->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $user) => [
                ...$user->only(['id', 'name', 'email']),
                'membership_role' => $user->pivot->role ?? 'member',
                'is_active' => (bool) ($user->pivot->is_active ?? true),
            ]);

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
            $page = max((int) $request->query('page', 1), 1);
            $total = $members->count();
            $items = $members->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'current_page' => $page,
                'data' => $items,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'per_page' => $perPage,
                'total' => $total,
            ]);
        }

        return response()->json($members);
    }

    public function addOrganizationMember(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'name' => ['required_without:user_id', 'string', 'max:255'],
            'email' => ['required_without:user_id', 'email'],
            'password' => ['nullable', 'string', 'min:8'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'membership_role' => ['sometimes', Rule::in(['admin', 'member'])],
        ]);

        if (isset($data['user_id'])) {
            $user = User::findOrFail($data['user_id']);
        } else {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password'] ?? Str::random(16)),
                ],
            );
        }

        abort_if(
            $organization->members()->whereKey($user->id)->exists(),
            422,
            'User is already a member of this organization.',
        );

        $organization->members()->attach($user->id, [
            'role' => $data['membership_role'] ?? 'member',
            'is_active' => true,
        ]);

        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization->id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'membership_role' => $data['membership_role'] ?? 'member',
            'is_active' => true,
        ], 201);
    }

    public function updateOrganizationMember(Request $request, Organization $organization, User $user): JsonResponse
    {
        $this->authorizePlatformAdmin($request);
        abort_unless($organization->members()->whereKey($user->id)->exists(), 404);

        $data = $request->validate([
            'membership_role' => ['sometimes', Rule::in(['admin', 'member'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('membership_role', $data)) {
            $data['role'] = $data['membership_role'];
            unset($data['membership_role']);
        }

        $organization->members()->updateExistingPivot($user->id, $data);
        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization->id);

        $membership = $organization->members()->whereKey($user->id)->first()?->pivot;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'membership_role' => $membership->role ?? 'member',
            'is_active' => (bool) ($membership->is_active ?? true),
        ]);
    }

    public function removeOrganizationMember(Request $request, Organization $organization, User $user): JsonResponse
    {
        $this->authorizePlatformAdmin($request);
        abort_unless($organization->members()->whereKey($user->id)->exists(), 404);

        abort_if(
            $this->enterpriseRoles->userIsOwner($user, $organization->id)
            && $this->enterpriseRoles->countOwners($organization->id) <= 1,
            422,
            'Cannot remove the last organization owner.',
        );

        $organization->members()->detach($user->id);
        app(PermissionCacheInvalidator::class)->forgetUser($user, $organization->id);

        return response()->json(['message' => 'Member removed.']);
    }

    /** @return array<string, mixed> */
    private function transformOrganization(Organization $organization): array
    {
        return [
            ...$organization->only(['id', 'name', 'slug', 'is_active', 'created_at', 'updated_at']),
            'members_count' => $organization->members_count ?? $organization->members()->count(),
        ];
    }
}
