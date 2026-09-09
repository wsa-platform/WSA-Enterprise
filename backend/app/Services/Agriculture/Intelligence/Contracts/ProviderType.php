<?php

namespace App\Services\Agriculture\Intelligence\Contracts;

/**
 * Capability family for universal agricultural providers.
 */
final class ProviderType
{
    public const SCIENTIFIC = 'scientific';

    public const WEB = 'web';

    public const DISEASE = 'disease';

    public const ENVIRONMENTAL = 'environmental';

    public const MCP = 'mcp';

    public const EXECUTION = 'execution';

    public const FIELD = 'field';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SCIENTIFIC,
            self::WEB,
            self::DISEASE,
            self::ENVIRONMENTAL,
            self::MCP,
            self::EXECUTION,
            self::FIELD,
        ];
    }
}
