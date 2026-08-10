<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $query = AuditLog::query()
            ->where('organization_id', $organizationId)
            ->with(['user:id,name,email'])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->query('auditable_type'));
        }

        if (! $request->has('page') && ! $request->has('per_page')) {
            return ApiResponse::success(
                $query->limit(min(max((int) $request->query('limit', 50), 1), 200))->get()
            );
        }

        return ApiResponse::paginated(
            $query->paginate(min(max((int) $request->query('per_page', 15), 1), 100))
        );
    }
}
