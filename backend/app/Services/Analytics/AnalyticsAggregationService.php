<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\PageView;
use App\Models\VisitorLocation;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\DB;

class AnalyticsAggregationService
{
    /** @return array<string, mixed> */
    public function overview(int $organizationId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $events = AnalyticsEvent::where('organization_id', $organizationId)
            ->where('occurred_at', '>=', $since);

        $sessions = VisitorSession::where('organization_id', $organizationId)
            ->where('started_at', '>=', $since);

        $pageViews = PageView::where('organization_id', $organizationId)
            ->where('viewed_at', '>=', $since);

        return [
            'period_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'events_total' => (clone $events)->count(),
            'sessions_total' => (clone $sessions)->count(),
            'page_views_total' => (clone $pageViews)->count(),
            'events_by_type' => (clone $events)
                ->select('event_type', DB::raw('count(*) as total'))
                ->groupBy('event_type')
                ->pluck('total', 'event_type'),
            'top_pages' => (clone $pageViews)
                ->select('path', DB::raw('count(*) as views'))
                ->groupBy('path')
                ->orderByDesc('views')
                ->limit(10)
                ->get(),
            'geo' => VisitorLocation::whereHas('session', fn ($q) => $q
                ->where('organization_id', $organizationId)
                ->where('started_at', '>=', $since))
                ->select('country', 'city', DB::raw('count(*) as sessions'))
                ->groupBy('country', 'city')
                ->orderByDesc('sessions')
                ->limit(20)
                ->get(),
            'traffic' => $this->dailySeries($pageViews, 'viewed_at', $days),
            'charts' => [
                'events_over_time' => $this->dailySeries($events, 'occurred_at', $days),
                'page_views_over_time' => $this->dailySeries($pageViews, 'viewed_at', $days),
            ],
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function recordEvent(int $organizationId, string $eventType, array $payload = [], ?string $source = null): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'organization_id' => $organizationId,
            'event_type' => $eventType,
            'source' => $source,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    /** @return list<array{date: string, count: int}> */
    private function dailySeries($query, string $column, int $days): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'pgsql'
            ? "to_char({$column}, 'YYYY-MM-DD')"
            : "date({$column})";

        $rows = (clone $query)
            ->selectRaw("{$dateExpr} as day, count(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[] = ['date' => $day, 'count' => (int) ($rows[$day] ?? 0)];
        }

        return $series;
    }
}
