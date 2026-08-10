<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\LibraryItem;
use App\Models\Role;
use App\Models\Team;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Services\Ai\AiQuotaService;
use App\Services\Authorization\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function organizations(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizations = $request->user()
            ->organizations()
            ->select(['organizations.id', 'organizations.name', 'organizations.slug'])
            ->orderBy('organizations.name')
            ->get()
            ->map(fn ($organization) => [
                ...$organization->only(['id', 'name', 'slug']),
                'role' => $organization->pivot->role ?? null,
            ]);

        return response()->json($organizations);
    }

    public function workflowSummary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);

        return response()->json([
            'organization_id' => $organizationId,
            'farms' => Farm::where('organization_id', $organizationId)->count(),
            'diagnosis_requests' => DiagnosisRequest::where('organization_id', $organizationId)->count(),
            'training_courses' => TrainingCourse::where('organization_id', $organizationId)->where('status', 'published')->count(),
            'library_items' => LibraryItem::where('organization_id', $organizationId)->where('publication_status', 'published')->count(),
            'active_enrollments' => TrainingEnrollment::where('organization_id', $organizationId)->where('status', 'active')->count(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $user = $request->user();
        $membership = $user->organizations()->whereKey($organizationId)->firstOrFail();
        $permissions = app(PermissionService::class)->permissionsFor($user, $organizationId);
        $roles = $user->roles()
            ->wherePivot('organization_id', $organizationId)
            ->get(['roles.id', 'roles.name', 'roles.slug']);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'organization_id' => $organizationId,
            'membership_role' => $membership->pivot->role ?? null,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function accessSummary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $organization = $request->user()->organizations()->findOrFail($organizationId);
        $today = now()->toDateString();

        $aiBase = AiRequest::query()->where('organization_id', $organizationId);
        $canManageAccess = app(PermissionService::class)->userCan($request->user(), $organizationId, 'access.manage');
        $canUseAi = app(PermissionService::class)->userCan($request->user(), $organizationId, 'ai.use');

        $summary = [
            'organization_id' => $organizationId,
            'users_count' => $organization->members()->count(),
            'teams_count' => $canManageAccess ? Team::where('organization_id', $organizationId)->count() : null,
            'roles_count' => $canManageAccess ? Role::where('organization_id', $organizationId)->count() : null,
            'audit_events_24h' => $canManageAccess
                ? AuditLog::where('organization_id', $organizationId)->where('created_at', '>=', now()->subDay())->count()
                : null,
            'ai_requests' => $canUseAi ? [
                'today' => (clone $aiBase)->whereDate('created_at', $today)->count(),
                'pending' => (clone $aiBase)->where('status', 'pending')->count(),
                'processing' => (clone $aiBase)->where('status', 'processing')->count(),
                'completed' => (clone $aiBase)->where('status', 'completed')->count(),
                'failed' => (clone $aiBase)->where('status', 'failed')->count(),
                'cancelled' => (clone $aiBase)->where('status', 'cancelled')->count(),
            ] : null,
            'quota' => $canUseAi
                ? app(AiQuotaService::class)->summaryForOrganization($organizationId)
                : null,
            'system' => [
                'api' => 'ok',
                'queue' => config('queue.default') === 'redis' ? 'redis' : config('queue.default'),
            ],
        ];

        if ($canManageAccess) {
            $summary['recent_audit'] = AuditLog::query()
                ->where('organization_id', $organizationId)
                ->with(['user:id,name,email'])
                ->latest()
                ->limit(8)
                ->get(['id', 'action', 'user_id', 'auditable_type', 'auditable_id', 'created_at']);
        }

        if ($canUseAi) {
            $summary['recent_ai'] = (clone $aiBase)
                ->select(['id', 'request_type', 'status', 'user_id', 'created_at', 'updated_at'])
                ->latest()
                ->limit(8)
                ->get();
        }

        return response()->json($summary);
    }
}
