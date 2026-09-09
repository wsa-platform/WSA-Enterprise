<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Web;

use App\Contracts\Agriculture\McpToolClientInterface;
use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Mcp\LaravelStdioMcpToolClient;
use App\Services\Agriculture\Intelligence\Normalization\FreeSearchMcpResultNormalizer;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;

/**
 * ADR-001 MCP adapter for free-search-mcp (external process, not WSA Core).
 * Implements the existing Web Search provider contract. Keyless — no API key.
 */
final class FreeSearchMcpAdapter implements WebSearchProviderInterface
{
    public const PROVIDER_ID = 'free_search_mcp';

    public const TOOL_SEARCH = 'search';

    public const TOOL_RESEARCH = 'research';

    public function __construct(
        private McpToolClientInterface $mcpClient,
        private FreeSearchMcpResultNormalizer $normalizer,
        private LaravelStdioMcpToolClient $stdioConfig,
    ) {}

    public function providerId(): string
    {
        return self::PROVIDER_ID;
    }

    public function displayName(): string
    {
        return (string) config('agricultural_intelligence.mcp.free_search.display_name', 'Free Search MCP');
    }

    public function isConfigured(): bool
    {
        if (! filter_var(config('agricultural_intelligence.mcp.free_search.enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        $command = $this->stdioConfig->command();
        if ($command === '' || ! $this->isSafeToken($command)) {
            return false;
        }
        foreach ($this->stdioConfig->arguments() as $arg) {
            if (! $this->isSafeToken($arg)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function search(string $query, int $limit = 10, array $options = []): WebSearchOutcome
    {
        if (! $this->isConfigured()) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_NOT_CONFIGURED,
                error: 'NOT_CONFIGURED',
                observability: ['reason' => 'disabled_or_invalid_command'],
            );
        }

        if (trim($query) === '') {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_EMPTY,
                error: 'empty_query',
            );
        }

        $tool = $this->resolveTool($options);
        $arguments = $this->buildToolArguments($query, $limit, $options, $tool);

        $call = $this->mcpClient->callTool($tool, $arguments);
        if ($call['is_error']) {
            $error = (string) ($call['error'] ?? 'mcp_failure');

            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_FAILED,
                error: $error,
                observability: [
                    'tool' => $tool,
                    'failure_isolated' => true,
                    'command_from_query' => false,
                ],
            );
        }

        $hits = $this->extractHits($call['structured'], $call['text']);
        if ($hits === null) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_FAILED,
                error: 'malformed_response',
                observability: ['tool' => $tool, 'failure_isolated' => true],
            );
        }

        $normalized = $this->normalizer->normalizeMany($hits, $this->providerId());
        $normalized = array_slice($normalized, 0, max(1, min($limit, 20)));
        if ($normalized === []) {
            return new WebSearchOutcome(
                providerId: $this->providerId(),
                status: WebSearchOutcome::STATUS_EMPTY,
                error: 'empty_results',
                observability: ['tool' => $tool, 'result_count' => 0],
            );
        }

        return new WebSearchOutcome(
            providerId: $this->providerId(),
            status: WebSearchOutcome::STATUS_SUCCESS,
            results: $normalized,
            observability: [
                'tool' => $tool,
                'result_count' => count($normalized),
                'family' => 'web',
                'not_scientific' => true,
            ],
        );
    }

    /**
     * Public for contract tests — query is tool-argument data, never argv.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function buildToolArguments(string $query, int $limit, array $options = [], string $tool = self::TOOL_SEARCH): array
    {
        $arguments = [
            'query' => $query,
            'format' => 'json',
        ];
        if ($tool === self::TOOL_RESEARCH) {
            $arguments['question'] = $query;
        }
        if (isset($options['freshness']) && is_string($options['freshness'])) {
            $arguments['freshness'] = $options['freshness'];
        }

        return $arguments;
    }

    public function resolveTool(array $options): string
    {
        $requested = strtolower(trim((string) ($options['tool'] ?? self::TOOL_SEARCH)));

        return $requested === self::TOOL_RESEARCH ? self::TOOL_RESEARCH : self::TOOL_SEARCH;
    }

    public function isSafeToken(string $token): bool
    {
        if ($token === '' || str_contains($token, '..')) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9._:\\/-]+$/', $token);
    }

    /**
     * @param  array<string, mixed>|null  $structured
     * @return list<array<string, mixed>>|null  null = malformed
     */
    public function extractHits(?array $structured, string $text): ?array
    {
        if (is_array($structured)) {
            $fromStructured = $this->hitsFromPayload($structured);
            if ($fromStructured !== null) {
                return $fromStructured;
            }
        }

        $trimmed = trim($text);
        if ($trimmed === '') {
            return is_array($structured) ? null : [];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            $fromJson = $this->hitsFromPayload($decoded);
            if ($fromJson !== null) {
                return $fromJson;
            }
        }

        $fromMarkdown = $this->hitsFromMarkdown($trimmed);
        if ($fromMarkdown !== []) {
            return $fromMarkdown;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>|null
     */
    private function hitsFromPayload(array $payload): ?array
    {
        foreach (['results', 'items', 'organic', 'hits'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $rows = [];
                foreach (array_values($payload[$key]) as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }

                return $rows;
            }
        }

        if (array_is_list($payload) && $payload !== [] && is_array($payload[0] ?? null)) {
            $rows = [];
            foreach ($payload as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        if (isset($payload['title']) || isset($payload['url']) || isset($payload['link'])) {
            return [$payload];
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function hitsFromMarkdown(string $markdown): array
    {
        $hits = [];
        if (preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/', $markdown, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $hits[] = [
                    'title' => $match[1],
                    'url' => $match[2],
                ];
            }
        }

        return $hits;
    }
}
