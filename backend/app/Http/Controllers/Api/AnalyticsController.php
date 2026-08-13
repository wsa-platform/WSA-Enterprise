<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\AiQuotaService;
use App\Services\Authorization\PermissionService;
use App\Services\Billing\BillingUsageService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private AiQuotaService $aiQuotaService,
        private BillingUsageService $billingUsageService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $authorizer = app(ServiceOwnershipAuthorizer::class);
        $user = $request->user();
        $orgWide = $user === null || $authorizer->canSupervise($user, $organizationId);

        $farmBase = Farm::query()->where('organization_id', $organizationId);
        $aiBase = AiRequest::query()->where('organization_id', $organizationId);

        if (! $orgWide) {
            $farmBase = $authorizer->scopeAccessibleServices($farmBase, $user, $organizationId);
            $aiBase = $authorizer->scopeAccessibleServices($aiBase, $user, $organizationId);
        }

        $canViewOrgAudit = $user !== null && (
            $authorizer->canSupervise($user, $organizationId)
            || app(PermissionService::class)->userCan($user, $organizationId, 'access.manage')
        );

        return response()->json([
            'organization_id' => $organizationId,
            'generated_at' => now()->toIso8601String(),
            'scope' => $orgWide ? 'organization' : 'owned',
            'users' => $orgWide ? [
                'total' => User::query()
                    ->whereHas('organizations', fn ($query) => $query->whereKey($organizationId))
                    ->count(),
            ] : null,
            'teams' => $orgWide ? [
                'total' => Team::where('organization_id', $organizationId)->count(),
            ] : null,
            'farms' => [
                'total' => (clone $farmBase)->count(),
            ],
            'ai' => [
                'requests_total' => (clone $aiBase)->count(),
                'requests_today' => (clone $aiBase)->whereDate('created_at', now()->toDateString())->count(),
                'by_status' => [
                    'pending' => (clone $aiBase)->where('status', 'pending')->count(),
                    'processing' => (clone $aiBase)->where('status', 'processing')->count(),
                    'completed' => (clone $aiBase)->where('status', 'completed')->count(),
                    'failed' => (clone $aiBase)->where('status', 'failed')->count(),
                    'cancelled' => (clone $aiBase)->where('status', 'cancelled')->count(),
                ],
                'quota' => $this->aiQuotaService->summaryForOrganization($organizationId),
            ],
            'billing_usage' => $this->billingUsageService->summaryForOrganization($organizationId),
            'notifications' => [
                'unread' => AppNotification::where('organization_id', $organizationId)
                    ->where(function ($query) use ($request): void {
                        $query->whereNull('user_id');

                        if ($request->user() !== null) {
                            $query->orWhere('user_id', $request->user()->id);
                        }
                    })
                    ->whereNull('read_at')
                    ->count(),
            ],
            'audit' => $canViewOrgAudit ? [
                'events_24h' => AuditLog::where('organization_id', $organizationId)
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ] : null,
        ]);
    }
}
