<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\Ai\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Processes a queued AI request record asynchronously.
 * Foundation for future async AI workloads; sync HTTP path remains default.
 */
class ProcessAiRequest implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(public int $aiRequestId)
    {
        $this->tries = max(1, (int) config('ai.queue_tries', 3));
        $this->timeout = max(30, (int) config('ai.timeout', 30) + 30);
        $this->onQueue(config('ai.queue', 'default'));
    }

    public function handle(AiService $aiService): void
    {
        $record = AiRequest::query()->find($this->aiRequestId);

        if ($record === null || $record->status !== 'processing') {
            return;
        }

        $aiService->processRecord($record);
    }
}
