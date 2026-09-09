<?php

namespace App\Services\Agriculture\Intelligence\Contracts;

/**
 * Final multi-source answer statuses (ADR-001 / ADR-002).
 */
final class AnswerStatus
{
    public const SCIENTIFIC_VERIFIED = 'SCIENTIFIC_VERIFIED';

    public const WEB_SUPPORTED_SCIENTIFIC_LIMITED = 'WEB_SUPPORTED_SCIENTIFIC_LIMITED';

    public const GENERAL_WEB = 'GENERAL_WEB';

    public const INSUFFICIENT = 'INSUFFICIENT';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SCIENTIFIC_VERIFIED,
            self::WEB_SUPPORTED_SCIENTIFIC_LIMITED,
            self::GENERAL_WEB,
            self::INSUFFICIENT,
        ];
    }
}
