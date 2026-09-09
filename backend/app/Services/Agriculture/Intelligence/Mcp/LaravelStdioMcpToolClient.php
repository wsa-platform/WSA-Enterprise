<?php

namespace App\Services\Agriculture\Intelligence\Mcp;

use App\Contracts\Agriculture\McpToolClientInterface;
use Laravel\Mcp\Client;
use Laravel\Mcp\Exceptions\ClientException;
use Throwable;

/**
 * laravel/mcp stdio client wrapper. Executable and argv come from trusted config only.
 */
final class LaravelStdioMcpToolClient implements McpToolClientInterface
{
    public function callTool(string $name, array $arguments): array
    {
        $command = $this->command();
        $args = $this->arguments();
        $timeout = $this->timeoutSeconds();

        $client = Client::local($command, $args)->withTimeout($timeout);

        try {
            $result = $client->callTool($name, $arguments);

            return [
                'is_error' => $result->isError,
                'text' => $result->text(),
                'structured' => $result->structuredContent,
                'error' => $result->isError ? 'tool_error' : null,
            ];
        } catch (ClientException $exception) {
            $message = $exception->getMessage();
            $error = 'mcp_failure';
            if (str_contains(strtolower($message), 'timed out')) {
                $error = 'timeout';
            } elseif (str_contains(strtolower($message), 'failed to start process')) {
                $error = 'process_unavailable';
            }

            return [
                'is_error' => true,
                'text' => '',
                'structured' => null,
                'error' => $error,
            ];
        } catch (Throwable) {
            return [
                'is_error' => true,
                'text' => '',
                'structured' => null,
                'error' => 'mcp_failure',
            ];
        } finally {
            if ($client->connected()) {
                $client->disconnect();
            }
        }
    }

    public function command(): string
    {
        return trim((string) config('agricultural_intelligence.mcp.free_search.command', 'uvx'));
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        $raw = config('agricultural_intelligence.mcp.free_search.arguments', 'free-search-mcp');
        if (is_array($raw)) {
            $tokens = array_values(array_filter(array_map('strval', $raw), static fn (string $t): bool => $t !== ''));
        } else {
            $tokens = preg_split('/\s+/', trim((string) $raw)) ?: [];
            $tokens = array_values(array_filter($tokens, static fn (string $t): bool => $t !== ''));
        }

        return $tokens === [] ? ['free-search-mcp'] : $tokens;
    }

    public function timeoutSeconds(): float
    {
        $raw = (int) config('agricultural_intelligence.mcp.free_search.timeout', 30000);
        if ($raw >= 1000) {
            return max(1.0, min(120.0, $raw / 1000));
        }

        return max(1.0, min(120.0, (float) $raw));
    }
}
