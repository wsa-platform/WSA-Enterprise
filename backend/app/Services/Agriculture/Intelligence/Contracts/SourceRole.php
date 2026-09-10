<?php

namespace App\Services\Agriculture\Intelligence\Contracts;

/**
 * Source-role labels for canonical evidence. Access mechanism (MCP/REST/native)
 * is not a source role.
 */
final class SourceRole
{
    public const WEB_SOURCE = 'WEB_SOURCE';

    public const SCIENTIFIC_EVIDENCE = 'SCIENTIFIC_EVIDENCE';

    public const OFFICIAL_AGRICULTURAL_DATA = 'OFFICIAL_AGRICULTURAL_DATA';

    public const ENVIRONMENTAL_DATA = 'ENVIRONMENTAL_DATA';

    public const DIAGNOSTIC_RESULT = 'DIAGNOSTIC_RESULT';

    public const MODEL_RESULT = 'MODEL_RESULT';

    public const CITATION_METADATA = 'CITATION_METADATA';

    public const EXECUTION_RESULT = 'EXECUTION_RESULT';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::WEB_SOURCE,
            self::SCIENTIFIC_EVIDENCE,
            self::OFFICIAL_AGRICULTURAL_DATA,
            self::ENVIRONMENTAL_DATA,
            self::DIAGNOSTIC_RESULT,
            self::MODEL_RESULT,
            self::CITATION_METADATA,
            self::EXECUTION_RESULT,
        ];
    }
}
