<?php

namespace App\Jobs;

use App\Models\WelcomeEvent;
use App\Services\Welcome\WelcomeWorkflowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWelcomeEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $welcomeEventId) {}

    public function handle(WelcomeWorkflowService $workflow): void
    {
        $event = WelcomeEvent::find($this->welcomeEventId);
        if ($event === null) {
            return;
        }

        $workflow->process($event);
    }
}
