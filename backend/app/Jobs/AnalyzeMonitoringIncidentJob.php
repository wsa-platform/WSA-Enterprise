<?php

namespace App\Jobs;

use App\Models\AiRecommendation;
use App\Models\AiRequest;
use App\Models\MonitoringEvent;
use App\Services\Monitoring\MonitoringEventService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeMonitoringIncidentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $monitoringEventId) {}

    public function handle(MonitoringEventService $monitoringEvents): void
    {
        $event = MonitoringEvent::find($this->monitoringEventId);
        if ($event === null || $event->status === MonitoringEvent::STATUS_RESOLVED || $event->organization_id === null) {
            return;
        }

        $summary = sprintf(
            'تحليل تلقائي: مكوّن %s — %s',
            $event->component,
            is_array($event->details) ? ($event->details['message'] ?? 'incident detected') : 'incident detected',
        );

        $aiRequest = AiRequest::create([
            'organization_id' => $event->organization_id,
            'user_id' => null,
            'request_type' => 'monitoring_analysis',
            'source_type' => MonitoringEvent::class,
            'source_id' => $event->id,
            'provider' => config('ai.provider', 'mock'),
            'status' => 'completed',
            'input' => ['incident_id' => $event->id, 'component' => $event->component],
            'output' => ['summary' => $summary],
            'latency_ms' => 0,
        ]);

        AiRecommendation::create([
            'organization_id' => $aiRequest->organization_id,
            'ai_request_id' => $aiRequest->id,
            'type' => 'monitoring.incident',
            'content' => $summary,
            'metadata' => ['monitoring_event_id' => $event->id],
        ]);

        $event->update([
            'lifecycle_stage' => MonitoringEvent::STAGE_ANALYZED,
            'analysis_summary' => $summary,
        ]);
    }
}
