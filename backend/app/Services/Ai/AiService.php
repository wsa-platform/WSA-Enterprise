<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Models\AiRequest;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function __construct(private AiProviderInterface $provider) {}

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
        $record = AiRequest::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'request_type' => $requestType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'provider' => $this->provider->name(),
            'status' => 'processing',
            'input' => $input,
        ]);

        $started = microtime(true);

        try {
            $output = $this->provider->complete($requestType, $input);
            $record->update([
                'status' => 'completed',
                'output' => $output,
                'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                'tokens_used' => 0,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('AI provider failed', [
                'provider' => $this->provider->name(),
                'request_type' => $requestType,
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
}
