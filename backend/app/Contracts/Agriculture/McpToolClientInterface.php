<?php

namespace App\Contracts\Agriculture;

/**
 * Thin MCP tool client contract — stdio/process details stay out of research core.
 *
 * @phpstan-type McpToolCallResult array{
 *     is_error: bool,
 *     text: string,
 *     structured: array<string, mixed>|null,
 *     error: string|null
 * }
 */
interface McpToolClientInterface
{
    /**
     * Invoke a named MCP tool. Arguments are JSON-RPC data only — never shell/argv.
     *
     * @param  array<string, mixed>  $arguments
     * @return McpToolCallResult
     */
    public function callTool(string $name, array $arguments): array;
}
