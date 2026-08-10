<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Models\AiRequest;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function __construct(
        private AiProviderInterface $provider,
        private AiRequestValidator $validator,
        private AiResponseNormalizer $normalizer,
    ) {}

    public function providerName(): string
    {
        return $this->provider->name();
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
        $validatedInput = $this->validator->validate($requestType, $input);

        $record = AiRequest::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'request_type' => $requestType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'provider' => $this->provider->name(),
            'status' => 'processing',
            'input' => $validatedInput,
        ]);

        return $this->processRecord($record);
    }

    /** Process an existing pending AI request (used by queued jobs). */
    public function processRecord(AiRequest $record): AiRequest
    {
        if ($record->status !== 'processing') {
            return $record;
        }

        $started = microtime(true);
        $timeout = max(1, (int) config('ai.timeout', 30));

        try {
            $output = $this->callWithTimeout($record->request_type, $record->input ?? [], $timeout);
            $normalized = $this->normalizer->normalize($record->request_type, $output);

            $record->update([
                'status' => 'completed',
                'output' => $normalized,
                'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                'tokens_used' => (int) ($output['tokens_used'] ?? 0),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('AI provider failed', [
                'provider' => $this->provider->name(),
                'request_type' => $record->request_type,
                'organization_id' => $record->organization_id,
                'message' => $exception->getMessage(),
            ]);

            $record->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'latency_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        }

        return $record->fresh();
    }

    /** @param  array<string, mixed>  $input */
    private function callWithTimeout(string $requestType, array $input, int $timeoutSeconds): array
    {
        $started = microtime(true);

        $output = $this->provider->complete($requestType, $input);

        if ((microtime(true) - $started) > $timeoutSeconds) {
            throw new \RuntimeException('AI provider exceeded configured timeout.');
        }

        return $output;
    }
}
