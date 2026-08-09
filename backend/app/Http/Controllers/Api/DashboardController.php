<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\Task;
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
