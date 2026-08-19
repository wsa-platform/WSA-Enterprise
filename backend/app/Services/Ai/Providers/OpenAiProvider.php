<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiProvider implements AiProviderInterface
{
    /** @var list<int> */
    private const RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        $configured = trim((string) config('ai.openai.model', ''));

        return $configured !== '' ? $configured : 'gpt-4.1-mini';
    }

    /** @param  array<string, mixed>  $input */
    public function complete(string $requestType, array $input): array
    {
        $input = $this->discardClientOverrides($input);
        $this->assertServerConfiguration();

        $payload = $this->buildRequest($requestType, $input);
        $attempts = $this->maxAttempts();
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
                    throw new AiProviderTimeoutException($this->timeoutSeconds());
                }

                $this->logFailure(null, $exception);

                throw new AiProviderUnavailableException(
                    'openai',
                    502,
                    'The AI provider is temporarily unavailable.',
                );
            }

            $lastStatus = $response->status();

            if ($this->isRetryableStatus($lastStatus) && $attempt < $attempts) {
                $this->sleepBeforeRetry($attempt);
                continue;
            }

            return $this->interpret($requestType, $input, $response);
        }

        throw $this->exceptionForStatus($lastStatus ?? 503);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function discardClientOverrides(array $input): array
    {
        foreach (['api_key', 'apiKey', 'provider', 'model', 'base_url', 'openai_base_url', 'Authorization'] as $key) {
            unset($input[$key]);
        }

        return $input;
    }

    private function assertServerConfiguration(): void
    {
        if ($this->apiKey() === '') {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider is not configured.');
        }

        $this->baseUrl();
        $this->assertConfiguredModelAllowed();
    }

    private function assertConfiguredModelAllowed(): void
    {
        $allowed = config('ai.openai.allowed_models', []);
        if (! is_array($allowed) || $allowed === []) {
            return;
        }

        $allowed = array_values(array_filter(array_map('strval', $allowed)));
        if ($allowed !== [] && ! in_array($this->model(), $allowed, true)) {
            throw new AiProviderUnavailableException('openai', 422, 'The configured AI model is not allowed.');
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function send(array $payload): Response
    {
        return Http::withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeoutSeconds())
            ->connectTimeout($this->connectTimeoutSeconds())
            ->post($this->responsesUrl(), $payload);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function interpret(string $requestType, array $input, Response $response): array
    {
        $status = $response->status();

        if ($status >= 200 && $status < 300) {
            return $this->normalizeSuccess($requestType, $input, $response);
        }

        $this->logFailure($status, null, is_array($response->json()) ? $response->json() : null);

        throw $this->exceptionForStatus($status);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeSuccess(string $requestType, array $input, Response $response): array
    {
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider returned a malformed response.');
        }

        $text = $this->extractText($payload);
        if ($text === '') {
            throw new AiProviderUnavailableException('openai', 502, 'The AI provider returned a malformed response.');
        }

        $sources = $this->extractSources($payload);
        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];
        $tokens = (int) ($usage['total_tokens'] ?? ((int) ($usage['input_tokens'] ?? 0) + (int) ($usage['output_tokens'] ?? 0)));
        $responseModel = is_string($payload['model'] ?? null) && $payload['model'] !== '' ? $payload['model'] : $this->model();
        $finish = is_string($payload['status'] ?? null) && $payload['status'] === 'completed' ? 'stop' : (string) ($payload['status'] ?? 'stop');
        $requestId = is_string($payload['id'] ?? null) ? $payload['id'] : null;

        $typed = match ($requestType) {
            'library_summary', 'library_qa' => [
                'summary' => $text,
                'answer' => $text,
                'sources' => $sources,
            ],
            'assistant' => [
                'reply' => $text,
                'domain' => is_string($input['domain'] ?? null) ? $input['domain'] : null,
                'sources' => $sources,
                'requires_more_information' => false,
            ],
            'diagnosis' => [
                'title' => 'Decision support result',
                'summary' => $text,
                'recommendations' => [],
                'is_decision_support' => true,
            ],
            'training_assistance' => [
                'guidance' => $text,
                'suggestions' => [],
            ],
            default => [
                'summary' => $text,
                'content' => $text,
                'sources' => $sources,
            ],
        };

        return array_merge($typed, [
            'provider' => $this->name(),
            'model' => $responseModel,
            'tokens_used' => $tokens,
            'finish_reason' => $finish,
            'request_id' => $requestId,
            'sources' => $typed['sources'] ?? $sources,
        ]);
    }

    /** @param  array<string, mixed>  $payload */
    private function extractText(array $payload): string
    {
        if (isset($payload['output_text']) && is_string($payload['output_text']) && trim($payload['output_text']) !== '') {
            return trim($payload['output_text']);
        }

        $parts = [];
        foreach ($payload['output'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $content) {
                if (! is_array($content)) {
                    continue;
                }
                $type = (string) ($content['type'] ?? '');
                if (in_array($type, ['output_text', 'text'], true) && isset($content['text']) && is_string($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractSources(array $payload): array
    {
        $sources = [];
        foreach ($payload['output'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ($item['content'] ?? [] as $content) {
                if (! is_array($content)) {
                    continue;
                }
                foreach ($content['annotations'] ?? [] as $annotation) {
                    if (! is_array($annotation)) {
                        continue;
                    }
                    if (($annotation['type'] ?? '') !== 'url_citation') {
                        continue;
                    }
                    $sources[] = [
                        'title' => (string) ($annotation['title'] ?? $annotation['url'] ?? 'citation'),
                        'reference' => $annotation['url'] ?? null,
                    ];
                }
            }
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildRequest(string $requestType, array $input): array
    {
        return [
            'model' => $this->model(),
            'input' => $this->userText($input),
            'instructions' => $this->instructions($requestType, $input),
        ];
    }

    /** @param  array<string, mixed>  $input */
    private function userText(array $input): string
    {
        foreach (['message', 'content', 'query', 'notes', 'question', 'title', 'lesson_title'] as $key) {
            if (isset($input[$key]) && is_string($input[$key]) && trim($input[$key]) !== '') {
                return $input[$key];
            }
        }

        $safe = $input;
        unset(
            $safe['context'],
            $safe['history'],
            $safe['sources'],
            $safe['retrieved_context'],
            $safe['api_key'],
            $safe['provider'],
            $safe['model'],
        );

        return json_encode($safe, JSON_UNESCAPED_UNICODE) ?: 'AI request';
    }

    /** @param  array<string, mixed>  $input */
    private function instructions(string $requestType, array $input): string
    {
        $domain = is_string($input['domain'] ?? null) && $input['domain'] !== '' ? $input['domain'] : 'platform';

        $instructions = 'You are a WSA agricultural decision-support assistant. Request type: '.$requestType.'. Domain: '.$domain.'. Outputs are decision support only and are not authoritative diagnoses.';

        $retrieved = $input['retrieved_context'] ?? null;
        if (is_string($retrieved) && trim($retrieved) !== '') {
            $instructions .= "\n\n".$retrieved;
        }

        return $instructions;
    }

    private function exceptionForStatus(?int $status): AiProviderUnavailableException
    {
        return match (true) {
            in_array($status, [401, 403], true) => new AiProviderUnavailableException('openai', 502, 'The AI provider could not authenticate.'),
            $status === 429 => new AiProviderUnavailableException('openai', 429, 'The AI provider rate limit was exceeded.'),
            in_array($status, [400, 404, 422], true) => new AiProviderUnavailableException('openai', 422, 'The AI provider rejected the request.'),
            default => new AiProviderUnavailableException('openai', 502, 'The AI provider is temporarily unavailable.'),
        };
    }

    private function isRetryableStatus(int $status): bool
    {
        $configured = config('ai.openai.retry_statuses', self::RETRYABLE_STATUSES);

        return in_array($status, is_array($configured) ? $configured : self::RETRYABLE_STATUSES, true);
    }

    private function isRetryableException(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException || $this->isTimeout($exception);
    }

    private function isTimeout(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'timeout')
            || str_contains($message, 'curl error 28');
    }

    private function sleepBeforeRetry(int $attempt): void
    {
        $baseMs = max(0, (int) config('ai.openai.retry_sleep_ms', 200));
        if ($baseMs === 0) {
            return;
        }

        usleep($baseMs * (2 ** max(0, $attempt - 1)) * 1000);
    }

    private function maxAttempts(): int
    {
        return max(1, 1 + (int) config('ai.openai.retry_times', 2));
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('ai.openai.timeout', config('ai.timeout', 30)));
    }

    private function connectTimeoutSeconds(): int
    {
        $configured = (int) config('ai.openai.connect_timeout', 10);

        return max(1, min($configured, $this->timeoutSeconds()));
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

    private function responsesUrl(): string
    {
        $base = $this->baseUrl();

        if (str_ends_with($base, '/v1')) {
            return $base.'/responses';
        }

        return $base.'/v1/responses';
    }

    /** @param  array<string, mixed>|null  $body */
    private function logFailure(?int $status, ?Throwable $exception, ?array $body = null): void
    {
        Log::warning('OpenAI provider request failed', [
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
