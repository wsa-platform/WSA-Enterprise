<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\Ai\AiService;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes a queued AI request record asynchronously.
 */
class ProcessAiRequest implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
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

    public function uniqueId(): string
    {
        return (string) $this->aiRequestId;
    }

    public function handle(AiService $aiService): void
    {
        $record = AiRequest::query()->find($this->aiRequestId);

        if ($record === null || $record->isTerminal()) {
            return;
        }

        $aiService->processRecord($record);
    }

    public function failed(?Throwable $exception): void
    {
        $record = AiRequest::query()->find($this->aiRequestId);

        if ($record === null || $record->isTerminal()) {
            return;
        }

        $safeMessage = $exception
            ? \App\Services\Ai\AiErrorSanitizer::publicMessage($exception)
            : 'Queue worker failed after retries.';

        Log::error('ProcessAiRequest job failed after retries', [
            'ai_request_id' => $this->aiRequestId,
            'organization_id' => $record->organization_id,
            'request_type' => $record->request_type,
            'message' => $exception ? \App\Services\Ai\AiErrorSanitizer::logMessage($exception) : null,
        ]);

        $record->update([
            'status' => 'failed',
            'error_message' => $safeMessage,
        ]);

        app(AuditService::class)->record(
            action: 'ai.request.failed',
            organizationId: $record->organization_id,
            userId: $record->user_id,
            auditable: $record,
            newValues: [
                'request_type' => $record->request_type,
                'error_message' => $record->error_message,
                'source' => 'queue',
            ],
        );

        app(NotificationService::class)->notifyAiFailed(
            $record->organization_id,
            $record->user_id,
            $record->id,
            $record->request_type,
            $record->error_message,
        );
    }
}
