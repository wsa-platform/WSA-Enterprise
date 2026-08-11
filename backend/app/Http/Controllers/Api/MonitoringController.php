<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\MonitoringEvent;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\MonitoringEventService;
use App\Support\HealthCheckMessages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function __construct(
        private HealthCheckService $healthChecks,
        private MonitoringEventService $monitoringEvents,
    ) {}

    public function health(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        $checks = $this->healthChecks->runAllChecks();
        $healthy = $this->healthChecks->allChecksHealthy($checks);

        $components = [];
        foreach ($checks as $component => $check) {
            $components[$component] = [
                'status' => ($check['healthy'] ?? false) ? 'healthy' : 'failed',
                'message' => isset($check['message'])
                    ? HealthCheckMessages::sanitize((string) $check['message'])
                    : null,
            ];
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'components' => $components,
        ]);
    }

    public function incidents(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $query = MonitoringEvent::query()
            ->where(function ($builder) use ($organizationId): void {
                $builder->whereNull('organization_id')
                    ->orWhere('organization_id', $organizationId);
            })
            ->latest('detected_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('component')) {
            $query->where('component', (string) $request->query('component'));
        }

        return $this->paginateQuery($request, $query);
    }

    public function resolveIncident(Request $request, MonitoringEvent $monitoringEvent): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        abort_unless(
            $monitoringEvent->organization_id === null || $monitoringEvent->organization_id === $organizationId,
            404,
        );

        abort_if($monitoringEvent->status === MonitoringEvent::STATUS_RESOLVED, 422, 'Incident is already resolved.');

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $event = $this->monitoringEvents->resolve(
            $monitoringEvent,
            $request,
            $data['note'] ?? null,
        );

        return response()->json([
            'id' => $event->id,
            'component' => $event->component,
            'status' => $event->status,
            'lifecycle_stage' => $event->lifecycle_stage,
            'resolved_at' => $event->resolved_at,
        ]);
    }
}
