<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Stable Stage 6 Plant AI Diagnosis outcome statuses.
 */
final class DiagnosisStatus
{
    public const DIAGNOSED = 'diagnosed';

    public const PROBABLE = 'probable';

    public const UNCERTAIN = 'uncertain';

    public const INSUFFICIENT_IMAGE = 'insufficient_image';

    public const INSUFFICIENT_CONTEXT = 'insufficient_context';

    public const INVALID_INPUT = 'invalid_input';

    public const ANALYSIS_UNAVAILABLE = 'analysis_unavailable';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DIAGNOSED,
            self::PROBABLE,
            self::UNCERTAIN,
            self::INSUFFICIENT_IMAGE,
            self::INSUFFICIENT_CONTEXT,
            self::INVALID_INPUT,
            self::ANALYSIS_UNAVAILABLE,
        ];
    }
}
