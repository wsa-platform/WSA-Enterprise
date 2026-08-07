<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = $request->user()->organizations()->firstOrFail();

        return response()->json(
            $organization->projects()
                ->with('manager:id,name')
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
                ->orderBy('name')
                ->get()
        );
    }
}
