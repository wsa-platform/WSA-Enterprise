<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringLogsController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['monitoring.view', 'access.manage']);
        $organizationId = $this->organization($request);

        $query = SystemLog::query()
            ->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organizationId))
            ->latest('logged_at');

        if ($request->filled('level')) {
            $query->where('level', (string) $request->query('level'));
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%'.$request->query('search').'%');
        }

        return $this->paginateQuery($request, $query);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $data = $request->validate([
            'level' => ['required', 'string', 'in:debug,info,warning,error,critical'],
            'message' => ['required', 'string'],
            'channel' => ['nullable', 'string', 'max:64'],
            'context' => ['nullable', 'array'],
        ]);

        $log = SystemLog::create([
            'organization_id' => $this->organization($request),
            'level' => $data['level'],
            'channel' => $data['channel'] ?? 'admin',
            'message' => $data['message'],
            'context' => $data['context'] ?? null,
            'logged_at' => now(),
        ]);

        return response()->json($log, 201);
    }
}
