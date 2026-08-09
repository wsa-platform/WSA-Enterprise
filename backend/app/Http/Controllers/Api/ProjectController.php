<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organization = $this->organizationModel($request);

        return response()->json(
            $organization->projects()
                ->with('manager:id,name')
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
                ->orderBy('name')
                ->get()
        );
    }
}
