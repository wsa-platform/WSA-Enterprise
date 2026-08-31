<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\JobSeekerProfile;
use App\Models\MarketplaceListing;
use App\Models\ContactAccessOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organization = $this->organizationModel($request);
        $tasks = Task::query()->whereHas('project', fn ($query) => $query->where('organization_id', $organization->id));

        return response()->json([
            'organization' => $organization->only(['id', 'name', 'slug']),
            'metrics' => [
                'active_projects' => $organization->projects()->where('status', 'active')->count(),
                'open_tasks' => (clone $tasks)->whereNotIn('status', ['done', 'cancelled'])->count(),
                'completed_tasks' => (clone $tasks)->where('status', 'done')->count(),
                'overdue_tasks' => (clone $tasks)->whereNotIn('status', ['done', 'cancelled'])->whereDate('due_at', '<', now()->toDateString())->count(),
                'job_seekers_active' => JobSeekerProfile::where('is_active', true)->count(),
                'job_seekers_pending_review' => JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_UNDER_REVIEW)->count(),
                'marketplace_published' => MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->count(),
                'marketplace_pending_review' => MarketplaceListing::where('status', MarketplaceListing::STATUS_PENDING_REVIEW)->count(),
                'marketplace_contact_orders_paid' => ContactAccessOrder::where('payment_status', ContactAccessOrder::PAYMENT_PAID)->count(),
            ],
            'projects' => $organization->projects()
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
                ->orderBy('name')
                ->get(),
            'recent_tasks' => $tasks->with(['project:id,name,code', 'assignee:id,name'])
                ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
                ->orderBy('due_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
