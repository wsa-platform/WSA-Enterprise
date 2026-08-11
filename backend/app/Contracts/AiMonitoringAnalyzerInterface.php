<?php

namespace App\Contracts;

use App\Models\MonitoringEvent;
use App\Services\Monitoring\MonitoringAnalysis;

interface AiMonitoringAnalyzerInterface
{
    /**
     * Analyze a monitoring incident and recommend safe next steps.
     * Implementations must not execute remediation — analysis only.
     */
    public function analyze(MonitoringEvent $event): MonitoringAnalysis;
}
