<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function index(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.audit.view');

        $query = AuditLog::withoutGlobalScopes()
            ->whereNull('organization_id')
            ->with(['user:id,name,email'])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        return ApiResponse::paginated(
            $query->paginate(min(max((int) $request->query('per_page', 15), 1), 100))
        );
    }
}
