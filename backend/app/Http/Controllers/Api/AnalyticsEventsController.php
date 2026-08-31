<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsEventsController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(private AnalyticsAggregationService $analytics) {}

    public function traffic(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $days = min(max((int) $request->query('days', 30), 1), 365);

        return response()->json($this->analytics->overview($this->organization($request), $days));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:64'],
            'source' => ['nullable', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        $event = $this->analytics->recordEvent(
            $this->organization($request),
            $data['event_type'],
            $data['payload'] ?? [],
            $data['source'] ?? null,
        );

        return response()->json($event->only(['id', 'event_type', 'occurred_at']), 201);
    }
}
