<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function updateStatus(Request $request, int $task): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);

        $record = Task::query()
            ->whereKey($task)
            ->whereHas('project', fn ($query) => $query->where('organization_id', $organizationId))
            ->firstOrFail();

        $data = $request->validate([
            'status' => ['required', Rule::in(['todo', 'in_progress', 'blocked', 'done', 'cancelled'])],
        ]);

        $record->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return response()->json($record->fresh(['project:id,name,code', 'assignee:id,name']));
    }
}
