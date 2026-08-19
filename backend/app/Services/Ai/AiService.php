<?php

namespace App\Services\Ai;

use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiQuotaExceededException;
use App\Jobs\ProcessAiRequest;
use App\Models\AiRequest;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiService
{
    /** @var list<string> */
    private const PROCESSABLE_STATUSES = ['pending', 'processing'];

    /** @var list<string> */
    private const TERMINAL_STATUSES = ['completed', 'failed', 'cancelled'];

    public function __construct(
        private AiProviderResolver $providerResolver,
        private AiRequestValidator $validator,
        private AiResponseNormalizer $normalizer,
        private AuditService $auditService,
        private AiQuotaService $quotaService,
        private AiUsageRecorder $usageRecorder,
        private NotificationService $notificationService,
    ) {}

    public function providerName(?int $organizationId = null): string
    {
        return $this->providerResolver->providerNameForOrganization($organizationId);
    }

    /** @return array<string, mixed> */
    public function providerDescription(?int $organizationId = null): array
    {
        return $this->providerResolver->describe($organizationId);
    }

    /** @param  array<string, mixed>  $input */
    public function run(
        int $organizationId,
        string $requestType,
        array $input,
        ?int $userId = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): AiRequest {
        $this->quotaService->assertCanDispatch($organizationId, $userId);

        $validatedInput = $this->validator->validate($requestType, $input);
        $provider = $this->providerResolver->forOrganization($organizationId);

        $record = AiRequest::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'owner_user_id' => $userId,
            'request_type' => $requestType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'provider' => $provider->name(),
            'status' => 'processing',
            'input' => $validatedInput,
        ]);

        $this->usageRecorder->recordRequest($organizationId, $record->id);

        $this->auditLifecycle($record, 'ai.request.created', [
            'request_type' => $record->request_type,
            'status' => $record->status,
            'async' => false,
        ]);

        $this->auditLifecycle($record, 'ai.request.started', [
            'request_type' => $record->request_type,
        ]);

        return $this->processRecord($record);
    }

    /**
     * Create a pending AI request and dispatch asynchronous processing.
     *
     * @param  array<string, mixed>  $input
     */
    public function dispatchForProcessing(
        int $organizationId,
        string $requestType,
        array $input,
        ?int $userId = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): AiRequest {
        $this->quotaService->assertCanDispatch($organizationId, $userId);

        $validatedInput = $this->validator->validate($requestType, $input);
        $provider = $this->providerResolver->forOrganization($organizationId);

        $record = AiRequest::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'owner_user_id' => $userId,
            'request_type' => $requestType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'provider' => $provider->name(),
            'status' => 'pending',
            'input' => $validatedInput,
        ]);

        $this->usageRecorder->recordRequest($organizationId, $record->id);

        $this->auditLifecycle($record, 'ai.request.created', [
            'request_type' => $record->request_type,
            'status' => $record->status,
            'async' => true,
        ]);

        $this->auditLifecycle($record, 'ai.request.dispatched', [
            'request_type' => $record->request_type,
            'status' => $record->status,
            'async' => true,
        ]);

        ProcessAiRequest::dispatch($record->id);

        return $record;
    }

    public function cancel(AiRequest $record, ?int $userId = null): AiRequest
    {
        $cancelled = DB::transaction(function () use ($record, $userId): AiRequest {
            /** @var AiRequest|null $locked */
            $locked = AiRequest::query()->whereKey($record->id)->lockForUpdate()->first();

            if ($locked === null) {
                return $record;
            }

            if (in_array($locked->status, self::TERMINAL_STATUSES, true)) {
                return $locked;
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->auditLifecycle($locked->fresh(), 'ai.request.cancelled', [
                'request_type' => $locked->request_type,
                'cancelled_by' => $userId,
            ]);

            return $locked->fresh();
        });

        if ($cancelled->status === 'cancelled') {
            $this->persistUsageSafely($cancelled, 'cancelled');
        }

        return $cancelled;
    }

    /** Process an existing AI request (sync path or queued worker). Idempotent for terminal states. */
    public function processRecord(AiRequest $record): AiRequest
    {
        $errorCategory = null;
        $resolvedModel = null;

        $processed = DB::transaction(function () use ($record, &$errorCategory, &$resolvedModel): AiRequest {
            /** @var AiRequest|null $locked */
            $locked = AiRequest::query()->whereKey($record->id)->lockForUpdate()->first();

            if ($locked === null) {
                return $record;
            }

            if (in_array($locked->status, self::TERMINAL_STATUSES, true)) {
                return $locked;
            }

            if ($locked->status === 'pending') {
                $locked->update(['status' => 'processing']);
                $locked->refresh();
                $this->auditLifecycle($locked, 'ai.request.started', [
                    'request_type' => $locked->request_type,
                ]);
                $this->auditLifecycle($locked, 'ai.request.processing', [
                    'request_type' => $locked->request_type,
                ]);
            }

            if ($locked->status !== 'processing') {
                return $locked;
            }

            $started = microtime(true);
            $timeout = max(1, (int) config('ai.timeout', 30));
            $providerName = $locked->provider ?: 'unknown';

            try {
                $provider = $this->providerResolver->forOrganization($locked->organization_id);
                $providerName = $provider->name();
                $resolvedModel = $provider->model();
                $decision = $this->groundedAnswerPolicy()->prepare(
                    $locked->organization_id,
                    $locked->input ?? [],
                );
                $output = $this->callWithTimeout($provider, $locked->request_type, $decision->providerInput, $timeout);
                if (! is_array($output)) {
                    throw new \RuntimeException('AI provider returned a malformed response.');
                }
                $normalized = $this->normalizer->normalize(
                    $locked->request_type,
                    $output,
                    $provider->name(),
                    $provider->model(),
                );
                $normalized = $this->groundedAnswerPolicy()->applyToOutput($normalized, $decision);

                $locked->update([
                    'status' => 'completed',
                    'output' => $normalized,
                    'provider' => $provider->name(),
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                    'tokens_used' => (int) ($normalized['tokens_used'] ?? $output['tokens_used'] ?? 0),
                ]);

                $this->auditLifecycle($locked->fresh(), 'ai.request.completed', [
                    'request_type' => $locked->request_type,
                    'latency_ms' => $locked->latency_ms,
                ]);

                $this->notificationService->notifyAiCompleted(
                    $locked->organization_id,
                    $locked->user_id,
                    $locked->id,
                    $locked->request_type,
                );
            } catch (\Throwable $exception) {
                $errorCategory = AiErrorSanitizer::category($exception);

                Log::warning('AI provider failed', [
                    'provider' => $providerName,
                    'request_type' => $locked->request_type,
                    'organization_id' => $locked->organization_id,
                    'message' => AiErrorSanitizer::logMessage($exception),
                ]);

                $publicMessage = AiErrorSanitizer::publicMessage($exception);

                $locked->update([
                    'status' => 'failed',
                    'error_message' => $publicMessage,
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                ]);

                $this->auditLifecycle($locked->fresh(), 'ai.request.failed', [
                    'request_type' => $locked->request_type,
                    'error_message' => $publicMessage,
                ]);

                $this->notificationService->notifyAiFailed(
                    $locked->organization_id,
                    $locked->user_id,
                    $locked->id,
                    $locked->request_type,
                    $publicMessage,
                );
            }

            return $locked->fresh();
        });

        $this->persistUsageSafely($processed, $errorCategory, $resolvedModel);

        return $processed;
    }

    private function persistUsageSafely(AiRequest $record, ?string $errorCategory = null, ?string $model = null): void
    {
        try {
            $this->usageRecorder->recordOutcome($record, $errorCategory, $model);
        } catch (\Throwable $exception) {
            Log::warning('AI usage persistence failed', [
                'ai_request_id' => $record->id,
                'organization_id' => $record->organization_id,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }
    }

    /** @param  array<string, mixed>  $input */
    private function callWithTimeout(\App\Contracts\AiProviderInterface $provider, string $requestType, array $input, int $timeoutSeconds): array
    {
        $started = microtime(true);

        $output = $provider->complete($requestType, $input);

        if ((microtime(true) - $started) > $timeoutSeconds) {
            throw new AiProviderTimeoutException($timeoutSeconds);
        }

        return $output;
    }

    private function groundedAnswerPolicy(): AiGroundedAnswerPolicy
    {
        return app(AiGroundedAnswerPolicy::class);
    }

    /** @param  array<string, mixed>|null  $newValues */
    private function auditLifecycle(AiRequest $record, string $action, ?array $newValues = null): void
    {
        $this->auditService->record(
            action: $action,
            organizationId: $record->organization_id,
            userId: $record->user_id,
            auditable: $record,
            newValues: $newValues,
        );
    }
}
