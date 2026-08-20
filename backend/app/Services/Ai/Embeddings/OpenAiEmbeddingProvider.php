<?php

namespace App\Services\Ai\Embeddings;

use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiEmbeddingProvider implements EmbeddingProviderInterface
{
    /** @var list<int> */
    private const RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        private EmbeddingConfig $config,
        private EmbeddingVectorValidator $validator,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        $configured = $this->config->model();

        return str_starts_with($configured, 'mock') ? 'text-embedding-3-small' : $configured;
    }

    public function dimensions(): int
    {
        return $this->config->dimensions();
    }

    public function isAvailable(): bool
    {
        return $this->apiKey() !== '';
    }

    public function embed(string $text): EmbeddingResult
    {
        $results = $this->embedMany([$text]);

        return $results[0];
    }

    /**
     * @param  list<string>  $texts
     * @return list<EmbeddingResult>
     */
    public function embedMany(array $texts): array
    {
        if ($texts === []) {
            return [];
        }
        $this->assertServerConfiguration();
        $started = (int) round(microtime(true) * 1000);
        $payload = [
            'model' => $this->model(),
            'input' => array_values($texts),
            'dimensions' => $this->dimensions(),
        ];
        $attempts = max(1, 1 + $this->config->retryTimes());
        $lastStatus = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->send($payload);
            } catch (Throwable $exception) {
                if ($this->isRetryableException($exception) && $attempt < $attempts) {
                    $this->sleepBeforeRetry($attempt);

                    continue;
                }
                if ($this->isTimeout($exception)) {
                    throw new AiProviderTimeoutException($this->config->timeoutSeconds());
                }
                $this->logFailure(null, $exception);

                throw new EmbeddingException('The embedding provider is temporarily unavailable.');
            }

            $lastStatus = $response->status();
            if ($this->isRetryableStatus($lastStatus) && $attempt < $attempts) {
                $this->sleepBeforeRetry($attempt);

                continue;
            }

            return $this->interpret($response, $started);
        }

        throw new EmbeddingException('The embedding provider is temporarily unavailable.');
    }

    private function assertServerConfiguration(): void
    {
        if ($this->apiKey() === '') {
            throw new EmbeddingException('The embedding provider is not configured.');
        }
        $this->baseUrl();
    }

    /** @param  array<string, mixed>  $payload */
    private function send(array $payload): Response
    {
        return Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout($this->config->timeoutSeconds())
            ->connectTimeout($this->config->connectTimeoutSeconds())
            ->post($this->embeddingsUrl(), $payload);
    }

    /**
     * @return list<EmbeddingResult>
     */
    private function interpret(Response $response, int $started): array
    {
        $status = $response->status();
        if ($status < 200 || $status >= 300) {
            $this->logFailure($status, null, is_array($response->json()) ? $response->json() : null);
            if ($status === 408) {
                throw new AiProviderTimeoutException($this->config->timeoutSeconds());
            }

            throw new EmbeddingException('The embedding provider is temporarily unavailable.');
        }

        $payload = $response->json();
        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            throw new EmbeddingException('The embedding provider returned a malformed response.');
        }

        $rows = $payload['data'];
        usort($rows, static fn ($left, $right): int => ((int) ($left['index'] ?? 0)) <=> ((int) ($right['index'] ?? 0)));
        $duration = max(0, ((int) round(microtime(true) * 1000)) - $started);
        $results = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new EmbeddingException('The embedding provider returned a malformed response.');
            }
            $vector = $this->validator->assert($row['embedding'] ?? null, $this->dimensions());
            $results[] = new EmbeddingResult($vector, $this->model(), $this->dimensions(), $duration);
        }

        if ($results === []) {
            throw new EmbeddingException('The embedding provider returned a malformed response.');
        }

        return $results;
    }

    private function isRetryableException(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException || $this->isTimeout($exception);
    }

    private function isTimeout(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timeout') || str_contains($message, 'timed out');
    }

    private function isRetryableStatus(int $status): bool
    {
        $configured = config('ai.openai.retry_statuses', self::RETRYABLE_STATUSES);

        return in_array($status, is_array($configured) ? $configured : self::RETRYABLE_STATUSES, true);
    }

    private function sleepBeforeRetry(int $attempt): void
    {
        $baseMs = $this->config->retrySleepMs();
        if ($baseMs <= 0) {
            return;
        }
        usleep($baseMs * 1000 * $attempt);
    }

    private function apiKey(): string
    {
        return trim((string) config('ai.openai.api_key', ''));
    }

    private function baseUrl(): string
    {
        $url = rtrim((string) config('ai.openai.base_url', 'https://api.openai.com'), '/');
        $parts = parse_url($url);
        if ($url === '' || $parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider is not configured.');
        }
        if (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider is not configured.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider is not configured.');
        }

        return $url;
    }

    private function embeddingsUrl(): string
    {
        $base = $this->baseUrl();

        return str_ends_with($base, '/v1') ? $base.'/embeddings' : $base.'/v1/embeddings';
    }

    /** @param  array<string, mixed>|null  $body */
    private function logFailure(?int $status, ?Throwable $exception, ?array $body = null): void
    {
        Log::warning('OpenAI embedding request failed', [
            'provider' => 'openai',
            'model' => $this->model(),
            'status' => $status,
            'openai_response_id' => is_string($body['id'] ?? null) ? $body['id'] : null,
            'message' => $exception instanceof Throwable
                ? AiErrorSanitizer::logMessage($exception)
                : 'provider_http_error',
        ]);
    }
}
