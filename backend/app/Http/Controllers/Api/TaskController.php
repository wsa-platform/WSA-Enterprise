<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $request->user()->organizations()->whereKey($task->project->organization_id)->exists(),
            403
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(['todo', 'in_progress', 'blocked', 'done', 'cancelled'])],
        ]);

        $task->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return response()->json($task->fresh(['project:id,name,code', 'assignee:id,name']));
    }
}
