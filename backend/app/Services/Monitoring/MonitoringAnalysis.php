<?php

namespace App\Services\Monitoring;

readonly class MonitoringAnalysis
{
    public function __construct(
        public string $likelyCause,
        public string $recommendedAction,
        public bool $safeToAutoRemediate,
        public string $summary,
    ) {}
}
