<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            Team::query()->withCount('members')->latest()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organization = $this->organization($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', Rule::unique('teams', 'slug')->where('organization_id', $organization)],
            'description' => ['nullable', 'string'],
        ]);

        $team = Team::create([
            'organization_id' => $organization,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        app(AuditService::class)->record(
            action: 'team.created',
            organizationId: $organization,
            userId: $request->user()->id,
            auditable: $team,
            newValues: ['name' => $team->name, 'slug' => $team->slug],
            request: $request,
        );

        return response()->json($team, 201);
    }

    public function addMember(Request $request, Team $team): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        abort_unless($team->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['sometimes', 'string', 'max:32'],
        ]);

        $member = User::findOrFail($data['user_id']);
        abort_unless(
            $member->organizations()->whereKey($team->organization_id)->exists(),
            422,
            'User must belong to the organization.'
        );

        $team->members()->syncWithoutDetaching([
            $member->id => ['role' => $data['role'] ?? 'member'],
        ]);

        app(AuditService::class)->record(
            action: 'team.member_added',
            organizationId: $team->organization_id,
            userId: $request->user()->id,
            auditable: $team,
            newValues: ['user_id' => $member->id, 'role' => $data['role'] ?? 'member'],
            request: $request,
        );

        return response()->json($team->load('members'), 200);
    }

    public function removeMember(Request $request, Team $team, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        abort_unless($team->organization_id === $this->organization($request), 404);

        $team->members()->detach($user->id);

        app(AuditService::class)->record(
            action: 'team.member_removed',
            organizationId: $team->organization_id,
            userId: $request->user()->id,
            auditable: $team,
            oldValues: ['user_id' => $user->id],
            request: $request,
        );

        return response()->json(null, 204);
    }
}
